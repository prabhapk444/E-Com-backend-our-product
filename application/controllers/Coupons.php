<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coupons extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Coupon_model');
        $this->load->model('Employee_access_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');
        $this->load->database();
    }

    private function get_current_user() {
        $token = $this->Jwt_model->get_token_from_header();
        if (empty($token)) {
            return null;
        }

        $decoded = $this->Jwt_model->decode(trim($token));
        return $decoded ?: null;
    }

    private function authorize($action = 'view') {
        $user = $this->get_current_user();
        if (!$user) {
            send_error('Unauthorized', 401);
            return null;
        }

        $role = isset($user->role) ? (int)$user->role : null;
        if (in_array($role, [1, 2], true)) {
            return $user;
        }

        if ($role === 4) {
            $employeeId = $user->employee_id ?? $user->uid ?? null;
            if ($this->Employee_access_model->has_permission($employeeId, 'coupons', $action)) {
                return $user;
            }
        }

        send_error('Forbidden: You do not have permission', 403);
        return null;
    }

    private function get_json_input() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function normalize_coupon_data($data) {
        $code = strtoupper(trim($data['code'] ?? ''));
        $discount_type = in_array($data['discount_type'] ?? '', ['percentage', 'fixed'], true) ? $data['discount_type'] : 'percentage';

        return [
            'code' => $code,
            'description' => isset($data['description']) ? trim((string)$data['description']) : null,
            'discount_type' => $discount_type,
            'discount_value' => max(0, (float)($data['discount_value'] ?? 0)),
            'min_order_amount' => max(0, (float)($data['min_order_amount'] ?? 0)),
            'max_discount_amount' => ($data['max_discount_amount'] ?? null) === null || $data['max_discount_amount'] === '' ? null : max(0, (float)$data['max_discount_amount']),
            'usage_limit' => ($data['usage_limit'] ?? null) === null || $data['usage_limit'] === '' ? null : max(0, (int)$data['usage_limit']),
            'starts_at' => $this->normalize_datetime($data['starts_at'] ?? null),
            'expires_at' => $this->normalize_datetime($data['expires_at'] ?? null),
            'is_active' => isset($data['is_active']) ? ((int)$data['is_active'] === 1 ? 1 : 0) : 1,
            'products' => $this->normalize_ids($data['products'] ?? []),
            'categories' => $this->normalize_ids($data['categories'] ?? []),
        ];
    }

    private function normalize_datetime($value) {
        if (!$value) {
            return null;
        }

        $normalized = str_replace('T', ' ', (string)$value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $normalized)) {
            return $normalized;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $normalized) ? $normalized . ':00' : null;
    }

    private function normalize_ids($value) {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    private function code_exists($code, $excludeId = null) {
        $this->db->where('code', strtoupper($code));
        if ($excludeId) {
            $this->db->where('id !=', $excludeId);
        }
        return $this->db->count_all_results('coupons') > 0;
    }

    private function format_coupon($coupon) {
        if (!$coupon) {
            return null;
        }

        $coupon['id'] = (int)$coupon['id'];
        $coupon['discount_value'] = (float)$coupon['discount_value'];
        $coupon['min_order_amount'] = (float)$coupon['min_order_amount'];
        $coupon['max_discount_amount'] = $coupon['max_discount_amount'] === null ? null : (float)$coupon['max_discount_amount'];
        $coupon['usage_limit'] = $coupon['usage_limit'] === null ? null : (int)$coupon['usage_limit'];
        $coupon['used_count'] = (int)$coupon['used_count'];
        $coupon['is_active'] = (int)$coupon['is_active'];
        $coupon['starts_at'] = $coupon['starts_at'] ? str_replace(' ', 'T', $coupon['starts_at']) : null;
        $coupon['expires_at'] = $coupon['expires_at'] ? str_replace(' ', 'T', $coupon['expires_at']) : null;
        $coupon['products'] = $this->Coupon_model->get_coupon_products($coupon['id']);
        $coupon['categories'] = $this->Coupon_model->get_coupon_categories($coupon['id']);

        return $coupon;
    }

    public function index() {
        if (!$this->authorize('view')) {
            return;
        }

        $limit = max(1, (int)($this->input->get('limit') ?: 50));
        $offset = max(0, (int)($this->input->get('offset') ?: 0));
        $search = trim((string)($this->input->get('search') ?: ''));
        $status = $this->input->get('status');
        $is_active = $status !== null && $status !== '' ? (int)$status : null;

        $coupons = array_map([$this, 'format_coupon'], $this->Coupon_model->get_all($limit, $offset, $search, $is_active));
        $total = $this->Coupon_model->get_total($search, $is_active);

        send_success([
            'coupons' => $coupons,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ], 'Coupons fetched successfully');
    }

    public function get($id) {
        if (!$this->authorize('view')) {
            return;
        }

        $coupon = $this->format_coupon($this->Coupon_model->get_by_id($id));
        if (!$coupon) {
            send_error('Coupon not found', 404);
            return;
        }

        send_success($coupon, 'Coupon fetched successfully');
    }

    public function public_coupons() {
        $coupons = array_map([$this, 'format_coupon'], $this->Coupon_model->get_public_active());
        send_success($coupons, 'Active coupons fetched successfully');
    }

    public function create() {
        if (!$this->authorize('create')) {
            return;
        }

        $data = $this->normalize_coupon_data($this->get_json_input());
        if (!$data['code']) {
            send_error('Coupon code is required', 400);
            return;
        }

        if ($this->code_exists($data['code'])) {
            send_error('Coupon code already exists', 409);
            return;
        }

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            send_error('Percentage discount cannot exceed 100', 400);
            return;
        }

        $id = $this->Coupon_model->create($data);
        send_success($this->format_coupon($this->Coupon_model->get_by_id($id)), 'Coupon created successfully', 201);
    }

    public function update($id) {
        if (!$this->authorize('edit')) {
            return;
        }

        if (!$this->Coupon_model->get_by_id($id)) {
            send_error('Coupon not found', 404);
            return;
        }

        $data = $this->normalize_coupon_data($this->get_json_input());
        if (!$data['code']) {
            send_error('Coupon code is required', 400);
            return;
        }

        if ($this->code_exists($data['code'], $id)) {
            send_error('Coupon code already exists', 409);
            return;
        }

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            send_error('Percentage discount cannot exceed 100', 400);
            return;
        }

        $this->Coupon_model->update($id, $data);
        send_success($this->format_coupon($this->Coupon_model->get_by_id($id)), 'Coupon updated successfully');
    }

    public function delete($id) {
        if (!$this->authorize('delete')) {
            return;
        }

        if (!$this->Coupon_model->get_by_id($id)) {
            send_error('Coupon not found', 404);
            return;
        }

        $this->Coupon_model->delete($id);
        send_success(['id' => $id], 'Coupon deleted successfully');
    }

    public function validate() {
        $input = $this->get_json_input();
        $code = isset($input['code']) ? strtoupper(trim((string)$input['code'])) : '';
        $subtotal = isset($input['subtotal']) ? (float)$input['subtotal'] : 0;
        $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

        $result = $this->Coupon_model->validate($code, $subtotal, $items);
        if (!$result['valid']) {
            send_error($result['message'], 400);
            return;
        }

        send_success($result, 'Coupon validated successfully');
    }
}
