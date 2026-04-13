<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports_model extends CI_Model {

 public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }


    // Monthly Sales
    public function get_monthly_sales() {
    $query = $this->db->query("
        SELECT 
            DATE_FORMAT(created_at, '%b') as month,
            SUM(total) as revenue,
            COUNT(id) as orders
        FROM orders
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ");

    return $query->result();
}

    // Order Status Count
    public function get_order_status() {
        $query = $this->db->query("
            SELECT 
                status as name,
                COUNT(*) as value
            FROM orders
            GROUP BY status
        ");

        return $query->result();
    }

    // Top Products
  public function get_top_products() {
    $query = $this->db->query("
        SELECT 
            p.id,
            p.name,
            SUM(oi.quantity) as quantity,
            SUM(oi.price * oi.quantity) as revenue
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.status != 'cancelled'
        GROUP BY p.id, p.name
        ORDER BY quantity DESC
        LIMIT 5
    ");

    return $query->num_rows() ? $query->result() : [];
}
}