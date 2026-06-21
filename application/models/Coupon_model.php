<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coupon_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'coupons';
        $this->products_table = 'coupon_products';
        $this->categories_table = 'coupon_categories';
    }

    public function get_all($limit = 50, $offset = 0, $search = '', $status = null) {
        $this->db->from($this->table);
        if ($search) {
            $this->db->group_start();
            $this->db->like('code', $search);
            $this->db->or_like('description', $search);
            $this->db->group_end();
        }
        if ($status !== null && $status !== '') {
            $this->db->where('is_active', $status);
        }
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        return $this->db->get()->result_array();
    }

    public function get_public_active() {
        $now = date('Y-m-d H:i:s');
        $this->db->from($this->table)
            ->where('is_active', 1)
            ->group_start()
                ->where('starts_at IS NULL')
                ->or_where('starts_at <=', $now)
            ->group_end()
            ->group_start()
                ->where('expires_at IS NULL')
                ->or_where('expires_at >=', $now)
            ->group_end()
            ->order_by('created_at', 'DESC')
            ->limit(20);

        return $this->db->get()->result_array();
    }

    public function get_total($search = '', $status = null) {
        $this->db->from($this->table);
        if ($search) {
            $this->db->group_start();
            $this->db->like('code', $search);
            $this->db->or_like('description', $search);
            $this->db->group_end();
        }
        if ($status !== null && $status !== '') {
            $this->db->where('is_active', $status);
        }
        return $this->db->count_all_results();
    }

    public function get_by_id($id) {
        $coupon = $this->db->where('id', $id)->get($this->table)->row_array();
        if ($coupon) {
            $coupon['products'] = $this->get_coupon_products($id);
            $coupon['categories'] = $this->get_coupon_categories($id);
        }
        return $coupon;
    }

    public function get_by_code($code) {
        $code = strtoupper(trim($code));
        $coupon = $this->db->where('code', $code)->get($this->table)->row_array();
        if ($coupon) {
            $coupon['products'] = $this->get_coupon_products($coupon['id']);
            $coupon['categories'] = $this->get_coupon_categories($coupon['id']);
        }
        return $coupon;
    }

    public function get_coupon_products($coupon_id) {
        return $this->db->select('cp.product_id, p.name')->from($this->products_table . ' cp')->join('products p', 'p.id = cp.product_id', 'left')->where('coupon_id', $coupon_id)->get()->result_array();
    }

    public function get_coupon_categories($coupon_id) {
        return $this->db->select('cc.category_id, c.name')->from($this->categories_table . ' cc')->join('categories c', 'c.id = cc.category_id', 'left')->where('coupon_id', $coupon_id)->get()->result_array();
    }

    public function create($data) {
        $data['code'] = strtoupper(trim($data['code']));
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $products = $data['products'] ?? [];
        $categories = $data['categories'] ?? [];
        unset($data['products'], $data['categories']);

        $this->db->insert($this->table, $data);
        $coupon_id = $this->db->insert_id();
        $this->save_products($coupon_id, $products);
        $this->save_categories($coupon_id, $categories);
        return $coupon_id;
    }

    public function update($id, $data) {
        $data['code'] = strtoupper(trim($data['code']));
        $data['updated_at'] = date('Y-m-d H:i:s');
        $products = $data['products'] ?? [];
        $categories = $data['categories'] ?? [];
        unset($data['products'], $data['categories']);

        $this->db->where('id', $id)->update($this->table, $data);
        $this->save_products($id, $products);
        $this->save_categories($id, $categories);
        return $id;
    }

    public function delete($id) {
        $this->db->where('id', $id)->delete($this->table);
    }

    public function save_products($coupon_id, $product_ids) {
        $ids = array_filter(array_map('intval', (array)$product_ids));
        $this->db->where('coupon_id', $coupon_id)->delete($this->products_table);
        foreach ($ids as $product_id) {
            $this->db->insert($this->products_table, ['coupon_id' => $coupon_id, 'product_id' => $product_id]);
        }
    }

    public function save_categories($coupon_id, $category_ids) {
        $ids = array_filter(array_map('intval', (array)$category_ids));
        $this->db->where('coupon_id', $coupon_id)->delete($this->categories_table);
        foreach ($ids as $category_id) {
            $this->db->insert($this->categories_table, ['coupon_id' => $coupon_id, 'category_id' => $category_id]);
        }
    }

    public function increment_usage($coupon_id) {
        if (!$coupon_id) return false;
        $this->db->set('used_count', 'used_count + 1', FALSE);
        return $this->db->where('id', $coupon_id)->update('coupons');
    }

    public function validate($code, $subtotal, $items = []) {
        $code = strtoupper(trim($code));
        if (!$code) return ['valid' => false, 'message' => 'Coupon code is required'];

        $coupon = $this->get_by_code($code);
        if (!$coupon) return ['valid' => false, 'message' => 'Coupon code not found'];
        if ((int)$coupon['is_active'] !== 1) return ['valid' => false, 'message' => 'Coupon is inactive'];

        $now = date('Y-m-d H:i:s');
        if ($coupon['starts_at'] && $coupon['starts_at'] > $now) return ['valid' => false, 'message' => 'Coupon is not active yet'];
        if ($coupon['expires_at'] && $coupon['expires_at'] < $now) return ['valid' => false, 'message' => 'Coupon has expired'];
        if ($coupon['usage_limit'] !== null && $coupon['usage_limit'] !== '' && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) return ['valid' => false, 'message' => 'Coupon usage limit reached'];

        if ((float)$subtotal < (float)$coupon['min_order_amount']) return ['valid' => false, 'message' => 'Minimum order amount is ₹' . number_format((float)$coupon['min_order_amount'], 2)];

        if (!$this->applies_to_items($coupon, $items)) return ['valid' => false, 'message' => 'Coupon does not apply to selected products'];

        $discount = $this->calculate_discount($coupon, $subtotal);
        return [
            'valid' => true,
            'message' => 'Coupon applied successfully',
            'coupon' => $coupon,
            'discount' => $discount,
            'id' => $coupon['id'],
            'code' => $coupon['code'],
        ];
    }

    public function applies_to_items($coupon, $items) {
        if (empty($items)) return true;

        $product_ids = array_map('intval', array_column($items, 'productId'));
        if (empty($product_ids)) {
            $product_ids = array_map('intval', array_column($items, 'product_id'));
        }

        $product_rows = $this->db->select('id, category_id')->where_in('id', $product_ids)->get('products')->result_array();
        if (empty($product_rows)) return false;

        $product_ids = array_map('intval', array_column($product_rows, 'id'));
        $category_ids = array_map('intval', array_column($product_rows, 'category_id'));

        $coupon_products = $this->get_coupon_products($coupon['id']);
        $coupon_categories = $this->get_coupon_categories($coupon['id']);

        if (!empty($coupon_products) || !empty($coupon_categories)) {
            $allowed_product_ids = !empty($coupon_products) ? array_map('intval', array_column($coupon_products, 'product_id')) : [];
            $allowed_category_ids = !empty($coupon_categories) ? array_map('intval', array_column($coupon_categories, 'category_id')) : [];

            $matchesProduct = empty($allowed_product_ids) || count(array_intersect($product_ids, $allowed_product_ids)) > 0;
            $matchesCategory = empty($allowed_category_ids) || count(array_intersect($category_ids, $allowed_category_ids)) > 0;

            if (!empty($allowed_product_ids) && !empty($allowed_category_ids)) {
                return $matchesProduct || $matchesCategory;
            }

            if (!empty($allowed_product_ids)) return $matchesProduct;
            if (!empty($allowed_category_ids)) return $matchesCategory;
        }

        return true;
    }

    public function calculate_discount($coupon, $subtotal) {
        $subtotal = max(0, (float)$subtotal);
        $value = (float)$coupon['discount_value'];
        if ($coupon['discount_type'] === 'fixed') {
            $discount = $value;
        } else {
            $discount = ($subtotal * $value) / 100;
        }
        if ($coupon['max_discount_amount'] !== null && $coupon['max_discount_amount'] !== '') {
            $discount = min($discount, (float)$coupon['max_discount_amount']);
        }
        return round(min($discount, $subtotal), 2);
    }
}
