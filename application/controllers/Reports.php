<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Reports_model');
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

    // Common Response Format
    private function response($status, $data = [], $message = '') {
        echo json_encode([
            "status" => $status,
            "data" => $data,
            "message" => $message
        ]);
    }

    // Monthly Sales API
    public function monthly_sales() {
        $this->verify_admin();
        $data = $this->Reports_model->get_monthly_sales();
        $this->response(true, $data);
    }

    // Order Status API
    public function order_status() {
           $this->verify_admin();
        $data = $this->Reports_model->get_order_status();

        // Add default colors
        $colors = [
            'delivered' => '#10b981',
            'processing' => '#f59e0b',
            'shipped' => '#3b82f6',
            'pending' => '#ef4444'
        ];

        foreach ($data as &$row) {
            $row->color = $colors[strtolower($row->name)] ?? '#999';
        }

        $this->response(true, $data);
    }

    // Top Products API
    public function top_products() {
           $this->verify_admin();
        $data = $this->Reports_model->get_top_products();
        $this->response(true, $data);
    }
}