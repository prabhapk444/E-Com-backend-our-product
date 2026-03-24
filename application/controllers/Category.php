<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Category_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response'); 
    }

    // Helper to check role
    private function check_role($allowed_roles = []) {
        $user = $this->Jwt_model->verify_token();
        if (!$user) return null; // not logged in
        if (!in_array($user->role, $allowed_roles)) return false; 
        return $user;
    }

    // Get all categories - everyone can view
    public function get_all() {
        $data = $this->Category_model->get_all();
        return success_response("Fetched", $data);
    }

    // Create category - only role 2
    public function create() {
        $user = $this->check_role([2]);
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
        $user = $this->check_role([2]);
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
        $user = $this->check_role([2]);
        if (!$user) return unauthorized("Access denied");

        $this->Category_model->delete($id);

        return success_response("Deleted");
    }

    // Toggle status - only role 2
    public function toggle_status($id) {
        $user = $this->check_role([2]);
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

}