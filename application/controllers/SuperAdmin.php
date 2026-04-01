<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SuperAdmin extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Jwt_model'); // for token verification
        $this->load->helper('response');
    }

    // Verify token helper
    private function verify_superadmin() {
        $user = $this->Jwt_model->verify_token();
      if (!$user || (int)$user->role !== 1) {
    return null;
}
        return $user;
    }

    // Get all admin users (role = 2)
    public function get_admins() {
        $superadmin = $this->verify_superadmin();
        if (!$superadmin) return unauthorized("Access denied");

        $admins = $this->db->where('role', 2)->get('users')->result();

        // Map is_enabled field for frontend
        $admins = array_map(function($a) {
            return [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'place' => $a->place,
                'createdat' => $a->createdat,
                'isEnabled' => isset($a->is_enabled) ? (bool)$a->is_enabled : true,
            ];
        }, $admins);

        return success_response("Admins fetched", $admins);
    }

    // Get SuperAdmin dashboard stats
    public function get_stats() {
        $superadmin = $this->verify_superadmin();
        if (!$superadmin) return unauthorized("Access denied");

        $totalAdmins = $this->db->where('role', 2)->count_all_results('users');
        $activeAdmins = $this->db->where('role', 2)->where('is_enabled', 1)->count_all_results('users');
        $disabledAdmins = $totalAdmins - $activeAdmins;

        $stats = [
            'totalAdmins' => $totalAdmins,
            'activeAdmins' => $activeAdmins,
            'disabledAdmins' => $disabledAdmins,
            'superAdmin' => 1,
        ];

        return success_response("Dashboard stats fetched", $stats);
    }
}