<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Dashboard_model');
        $this->load->model('Jwt_model');
    }


private function verify_admin() {
    $user = $this->Jwt_model->verify_token();

    if (!$user) {
        unauthorized("Token invalid or missing");
    }

    if (!in_array((int)$user->role, [1,2])) {
        forbidden("Only admin allowed");
    }

    return $user;
}

  public function stats() {
    $this->verify_admin();

    $data = [
        "totalRevenue" => (int)$this->Dashboard_model->get_total_revenue(),
        "totalOrders" => (int)$this->Dashboard_model->get_total_orders(),
        "totalUsers" => (int)$this->Dashboard_model->get_total_users(),
        "totalProducts" => (int)$this->Dashboard_model->get_total_products_with_variants(),
        "totalCategories" => (int)$this->Dashboard_model->get_total_categories(),
        "totalSubcategories" => (int)$this->Dashboard_model->get_total_subcategories(),
        "totalReviews" => (int)$this->Dashboard_model->get_total_reviews(),
        "totalFeedback" => (int)$this->Dashboard_model->get_total_feedbacks(),
    ];

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(["status" => true, "data" => $data]));
}

public function orders() {
    $this->verify_admin();

    $data = $this->Dashboard_model->get_recent_orders();

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(["status" => true, "data" => $data]));
}

public function order_status() {
    $this->verify_admin();

    $data = $this->Dashboard_model->get_order_status();

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(["status" => true, "data" => $data]));
}

public function monthly_sales() {
    $this->verify_admin();

    $data = $this->Dashboard_model->get_monthly_sales();

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(["status" => true, "data" => $data]));
}


public function low_stock() {
    $this->verify_admin();

    $data = $this->Dashboard_model->get_low_stock_products();

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            "status" => true,
            "data" => $data
        ]));
}

}