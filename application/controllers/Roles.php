<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends CI_Controller {


    public function __construct()
    {
        parent::__construct();

        $this->load->model('Role_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');

        header("Content-Type: application/json");
    }

private function check_role($allowed_roles = []) {
    $user = $this->Jwt_model->verify_token();

    if (!$user) {
        unauthorized('Invalid token');
    }

    if (!empty($allowed_roles) && !in_array($user->role, $allowed_roles)) {
        forbidden('Access denied');
    }

    return $user;
}


    // GET all roles
    public function index()
    {
        $this->check_role();

        $roles = $this->Role_model->get_all();

        send_success($roles, 'Roles fetched successfully');
    }

    // CREATE role
    public function create()
    {
        $this->check_role();

        $data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['name']) || empty(trim($data['name']))) {
    send_error('Role name is required');
}

        $insert = [
            'name' => trim($data['name'])
        ];

        $id = $this->Role_model->insert($insert);

        send_success(['id' => $id], 'Role created successfully');
    }

    // UPDATE role
    public function update($id)
    {
        $this->check_role();

        $data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['name']) || empty(trim($data['name']))) {
    send_error('Role name is required');
}

        $exists = $this->Role_model->find($id);

        if (!$exists) {
            not_found('Role not found');
        }

        $this->Role_model->update($id, [
            'name' => trim($data['name'])
        ]);

        send_success([], 'Role updated successfully');
    }

    // DELETE role
    public function delete($id)
    {
        $this->check_role();

        $exists = $this->Role_model->find($id);

        if (!$exists) {
            not_found('Role not found');
        }

        $this->Role_model->delete($id);

        send_success([], 'Role deleted successfully');
    }
}