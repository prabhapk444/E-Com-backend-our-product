<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Category_model');
        $this->load->model('Jwt_model');
        $this->load->model('Employee_access_model');
        $this->load->helper('response'); 
    }

    // Helper to check role
    private function check_role($allowed_roles = []) {
        $user = $this->Jwt_model->verify_token();
        if (!$user) return null; // not logged in
        if (!in_array($user->role, $allowed_roles)) return false; 
        return $user;
    }

    private function authorize($moduleKey, $action = 'view') {
        $user = $this->Jwt_model->verify_token();
        if (!$user) return unauthorized("Access denied");
        if (in_array((int)$user->role, [1, 2])) return $user;
        if ((int)$user->role === 4 && $this->Employee_access_model->has_permission($user->employee_id ?? $user->uid, $moduleKey, $action)) return $user;
        return unauthorized("Access denied");
    }

    // Get all categories - role 2 or employee with view permission
    public function get_all() {
        $this->authorize('categories', 'view');
        $data = $this->Category_model->get_all();
        return success_response("Fetched", $data);
    }

    // Create category - only role 2
    public function create() {
        $user = $this->authorize('categories', 'create');
        if (!$user) return unauthorized("Access denied");

        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['name'])) return error_response("Name required");

        if ($this->Category_model->exists($input['name'])) {
            return error_response("Category already exists");
        }

        $data = [
            'name' => $input['name'],
            'createdby' => $user->uid,
            'is_enabled' => 1
        ];

        $this->Category_model->insert($data);

        return success_response("Category created");
    }

    // Update category - only role 2
    public function update($id) {
        $user = $this->authorize('categories', 'edit');
        if (!$user) return unauthorized("Access denied");

        $input = json_decode(file_get_contents("php://input"), true);

        if ($this->Category_model->exists($input['name'], $id)) {
            return error_response("Category already exists");
        }

        $data = [
            'name' => $input['name'],
            'updatedby' => $user->uid
        ];

        $this->Category_model->update($id, $data);

        return success_response("Updated");
    }

    // Delete category - only role 2
    public function delete($id) {
        $user = $this->authorize('categories', 'delete');
        if (!$user) return unauthorized("Access denied");

        $this->Category_model->delete($id);

        return success_response("Deleted");
    }

    // Toggle status - only role 2 or employee with status permission
    public function toggle_status($id) {
        $user = $this->authorize('categories', 'status');
        if (!$user) return unauthorized("Access denied");

        $cat = $this->Category_model->get_by_id($id);
        if (!$cat) return error_response("Not found");

        $newStatus = $cat->is_enabled ? 0 : 1;

        $this->Category_model->update($id, [
            'is_enabled' => $newStatus,
            'updatedby' => $user->uid
        ]);

        return success_response("Status updated", ["newStatus" => $newStatus]);
    }

    private function hasEmployeeStatusPermission($employeeId, $moduleKey) {
        if (!$employeeId) return false;
        $perm = $this->db->get_where('employee_access', [
            'employee_id' => $employeeId,
            'module_key' => $moduleKey,
            'can_status' => 1
        ])->row();
        return (bool)$perm;
    }

}