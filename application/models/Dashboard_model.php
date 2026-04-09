<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

  public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }


    public function get_total_products() {
    
        return $this->db->count_all('products');
    }

    public function get_total_products_with_variants() {
      
        $products = $this->db->count_all('products');
        $variants = $this->db->count_all('product_variants');

        return $products + $variants;
    }

    public function get_total_categories() {
        return $this->db->count_all('categories');
    }

    public function get_total_subcategories() {
        return $this->db->count_all('subcategories');
    }

    public function get_total_feedbacks() {
        return $this->db->count_all('feedbacks');
    }

    public function get_total_orders() {
        return $this->db->count_all('orders');
    }

    public function get_total_reviews() {
        return $this->db->count_all('reviews');
    }

    public function get_total_users() {
        $this->db->where('role', 3);
        return $this->db->count_all_results('users');
    }

  public function get_total_revenue() {
    $this->db->select_sum('total');
    $this->db->where('status !=', 'cancelled'); 
    $query = $this->db->get('orders');

    return (float) ($query->row()->total ?? 0);
}
    // Recent Orders
public function get_recent_orders() {
    $this->db->select('
        id,
        order_id as orderNumber,
        name as customerName,
        total as total,
        created_at
    ');
    $this->db->from('orders');
    $this->db->order_by('created_at', 'DESC'); 
    $this->db->limit(5);

    return $this->db->get()->result();
}

// Order Status Chart
public function get_order_status() {
    $query = $this->db->query("
        SELECT status as name, COUNT(*) as value
        FROM orders
        GROUP BY status
    ");

    $data = [];
    $colors = [
        'delivered' => '#10b981',
        'processing' => '#f59e0b',
        'shipped' => '#3b82f6',
        'pending' => '#ef4444',
         'cancelled'  => '#9ca3af',
    ];

    foreach ($query->result() as $row) {
        $data[] = [
            "name" => ucfirst($row->name),
            "value" => (int)$row->value,
            "color" => $colors[$row->name] ?? '#999'
        ];
    }

    return $data;
}

public function get_monthly_sales() {
    $query = $this->db->query("
        SELECT 
            DATE_FORMAT(created_at, '%b') as month,
            SUM(total) as revenue
        FROM orders
        WHERE status != 'cancelled'
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ");

    return $query->num_rows() ? $query->result() : [];
}

public function get_low_stock_products($limit = 10) {
   $this->db->select('
    p.id as product_id,
    p.name as product_name,
    pv.id as variant_id,
    COALESCE(pv.stock, p.quantity) as stock,
    GROUP_CONCAT(CONCAT(va.name, ": ", va.value) SEPARATOR ", ") as variant_attributes
');
$this->db->from('products p');
$this->db->join('product_variants pv', 'pv.product_id = p.id', 'left');
$this->db->join('variant_attributes va', 'va.variant_id = pv.id', 'left');

// Low stock condition
$this->db->group_start();
    $this->db->where('pv.stock <=', 5);
    $this->db->or_where('p.quantity <=', 5);
$this->db->group_end();

$this->db->group_by('pv.id'); // Needed for GROUP_CONCAT
$this->db->order_by('stock', 'ASC');
$this->db->limit($limit);

return $this->db->get()->result();
}

public function get_monthly_sales_with_orders() {
    $query = $this->db->query("
        SELECT 
            DATE_FORMAT(created_at, '%b') as month,
            SUM(total) as revenue,
            COUNT(*) as orders
        FROM orders
        WHERE status != 'cancelled'
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ");

    $data = [];
    foreach ($query->result() as $row) {
        $data[] = [
            'month' => $row->month,
            'revenue' => (float)$row->revenue,
            'orders' => (int)$row->orders,
        ];
    }

    return $data;
}


}