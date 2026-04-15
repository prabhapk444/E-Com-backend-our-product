<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Csrf extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('cookie');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function get_token() {
        header('Content-Type: application/json');
        
        $token = get_csrf_token();
        
        echo json_encode([
            'csrf_token' => $token
        ]);
    }

    public function refresh() {
        header('Content-Type: application/json');
        
        $token = generate_csrf_token();
        
        echo json_encode([
            'csrf_token' => $token
        ]);
    }
}