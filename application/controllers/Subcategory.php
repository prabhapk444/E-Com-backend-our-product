<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subcategory extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Subcategory_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response'); 
    }


    private function check_role($allowed_roles = []) {
        $user = $this->Jwt_model->verify_token();
        if (!$user) return null; 

        if (!in_array($user->role, $allowed_roles)) {
            return false;
        }

        return $user;
    }


    public function index() {
    return $this->get_all();
}

    public function get_all() {
        $data = $this->Subcategory_model->get_all();
        return success_response("Fetched", $data);
    }

  
    public function view($id) {
        $sub = $this->Subcategory_model->get($id);
        if ($sub) {
            return success_response("Fetched", $sub);
        } else {
            return error_response("Subcategory not found");
        }
    }


    public function create() {
        $user = $this->check_role([2]);
        if (!$user) return unauthorized("Access denied");

        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['name']) || empty($input['category_id'])) {
            return error_response("All fields required");
        }

        $exists = $this->db->get_where('subcategories', [
            'name' => $input['name'],
            'category_id' => $input['category_id']
        ])->row();
        if ($exists) return error_response("Subcategory already exists");

        $data = [
            'name' => $input['name'],
            'category_id' => $input['category_id'],
            'createdby' => $user->uid,
            'is_enabled' => 1
        ];

        $id = $this->Subcategory_model->create($data);
        return success_response("Subcategory created", ['id' => $id]);
    }

    public function update($id) {
        $user = $this->check_role([2]);
        if (!$user) return unauthorized("Access denied");

        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['name']) || empty($input['category_id'])) {
            return error_response("All fields required");
        }

   
        $exists = $this->db->where('id !=', $id)
                           ->where('name', $input['name'])
                           ->where('category_id', $input['category_id'])
                           ->get('subcategories')->row();
        if ($exists) return error_response("Subcategory already exists");

        $data = [
            'name' => $input['name'],
            'category_id' => $input['category_id'],
            'updatedby' => $user->uid
        ];

        $this->Subcategory_model->update($id, $data);
        return success_response("Subcategory updated");
    }


    public function delete($id) {
        $user = $this->check_role([2]);
        if (!$user) return unauthorized("Access denied");

        $this->Subcategory_model->delete($id);
        return success_response("Subcategory deleted");
    }

  
    public function toggle_status($id) {
        $user = $this->check_role([2]);
        if (!$user) return unauthorized("Access denied");

        $sub = $this->Subcategory_model->get($id);
        if (!$sub) return error_response("Subcategory not found");

        $newStatus = $sub->is_enabled ? 0 : 1;
        $this->Subcategory_model->update($id, [
            'is_enabled' => $newStatus,
            'updatedby' => $user->uid
        ]);

        return success_response("Status updated", ['newStatus' => $newStatus]);
    }
}