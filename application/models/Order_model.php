<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'orders';
        $this->items_table = 'order_items';
    }

    // Generate unique order ID
    public function generate_order_id() {
        return 'ORD-' . strtoupper(uniqid());
    }

    // Create new order with items
    public function create_order($order_data, $items) {
        // Start transaction
        $this->db->trans_start();

        // Insert order
        $this->db->insert($this->table, $order_data);
        $order_id = $this->db->insert_id();

        // Insert order items
        foreach ($items as $item) {
            $item['order_id'] = $order_id;
            $this->db->insert($this->items_table, $item);
        }

        // Complete transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $order_id;
    }

    // Get order by ID
    public function get_by_id($id) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");
        $this->db->where('o.id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    // Get order by order_id string
    public function get_by_order_id($order_id) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");
        $this->db->where('o.order_id', $order_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    // Get all orders with pagination
    public function get_all($limit = 10, $offset = 0, $status = null, $user_id = null) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");

        if ($status !== null && $status !== '') {
            $this->db->where('o.status', $status);
        }

        if ($user_id !== null && $user_id !== '') {
            $this->db->where('o.user_id', $user_id);
        }

        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result_array();
    }

    // Get total count
    public function get_total($status = null, $user_id = null) {
        $this->db->from($this->table);

        if ($status !== null && $status !== '') {
            $this->db->where('status', $status);
        }

        if ($user_id !== null && $user_id !== '') {
            $this->db->where('user_id', $user_id);
        }

        return $this->db->count_all_results();
    }

    // Update order status
    public function update_status($id, $status, $updated_by = null) {
        $this->db->where('id', $id);
        
        $data = ['status' => $status];
        
        if ($updated_by !== null) {
            $data['updated_by'] = $updated_by;
        }

        return $this->db->update($this->table, $data);
    }

    // Update payment status
    public function update_payment_status($id, $payment_status, $payment_id = null) {
        $this->db->where('id', $id);
        
        $data = ['payment_status' => $payment_status];
        
        if ($payment_id !== null) {
            $data['payment_id'] = $payment_id;
        }

        return $this->db->update($this->table, $data);
    }

    // Get order items
    public function get_items($order_id) {
        $this->db->where('order_id', $order_id);
        $query = $this->db->get($this->items_table);
        return $query->result_array();
    }

    // Delete order (soft delete - just mark as cancelled)
    public function cancel_order($id) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => 'cancelled']);
    }

    // Get orders by user ID
    public function get_by_user($user_id, $limit = 10, $offset = 0) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");
        $this->db->where('o.user_id', $user_id);
        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result_array();
    }

    // Get orders by email (for customer orders)
    public function get_by_email($email, $limit = 10, $offset = 0) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");
        $this->db->where('o.email', $email);
        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result_array();
    }

    // Get total count by email
    public function get_total_by_email($email) {
        $this->db->from($this->table);
        $this->db->where('email', $email);
        return $this->db->count_all_results();
    }

    // Get recent orders
    public function get_recent($limit = 10) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");
        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit);

        $query = $this->db->get();
        return $query->result_array();
    }

    // Get orders by status
    public function get_by_status($status, $limit = 10, $offset = 0) {
        $this->db->select('o.*');
        $this->db->from("{$this->table} as o");
        $this->db->where('o.status', $status);
        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result_array();
    }

    // Get sales stats
    public function get_sales_stats($start_date = null, $end_date = null) {
        $this->db->select('
            COUNT(*) as total_orders,
            SUM(total) as total_sales,
            SUM(CASE WHEN status = "delivered" THEN total ELSE 0 END) as delivered_sales,
            SUM(CASE WHEN status = "cancelled" THEN total ELSE 0 END) as cancelled_sales
        ');
        $this->db->from($this->table);

        if ($start_date !== null) {
            $this->db->where('created_at >=', $start_date);
        }

        if ($end_date !== null) {
            $this->db->where('created_at <=', $end_date);
        }

        $query = $this->db->get();
        return $query->row_array();
    }
}
