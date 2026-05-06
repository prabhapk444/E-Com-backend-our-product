<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Departments extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Departments_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');
    }

    private function auth() {
        $user = $this->Jwt_model->verify_token();
        if (!$user || isset($user->expired)) {
            send_error("Unauthorized", 401);
        }
        return $user;
    }

    // GET all departments
    public function index() {
        $this->auth();

        $search = $this->input->get('search', true);
        $page = max(1, (int)($this->input->get('page', true) ?? 1));
        $perPage = 10;

        $offset = ($page - 1) * $perPage;

        $departments = $this->Departments_model->get_departments($search, $perPage, $offset);
        $total = $this->Departments_model->get_departments_count($search);

        send_success([
            'departments' => $departments,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage
        ], "Departments fetched");
    }

    // GET single department
    public function get_by_id($id) {
        $this->auth();

        $department = $this->Departments_model->get_department_by_id((int)$id);

        if ($department) {
            send_success($department, "Department fetched");
        } else {
            send_error("Department not found", 404);
        }
    }

    // CREATE
    public function create() {
        $this->auth();

        $input = json_decode(file_get_contents('php://input'), true);

        $name = trim($input['name'] ?? '');

        // Manual validation
        if ($name === '') {
            return send_error("Department name is required", 422);
        }

        if (strlen($name) < 2) {
            return send_error("Minimum 2 characters required", 422);
        }

        // Duplicate check
        if ($this->Departments_model->department_name_exists($name)) {
            return send_error("Department name already exists", 409);
        }

        $data = ['name' => $name];

        if ($this->Departments_model->create_department($data)) {
            send_success([], "Department created successfully", 201);
        } else {
            send_error("Failed to create department", 500);
        }
    }

    // UPDATE
    public function update($id) {
        $this->auth();

        $id = (int)$id;

        $existingDept = $this->Departments_model->get_department_by_id($id);
        if (!$existingDept) {
            send_error("Department not found", 404);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $name = trim($input['name'] ?? '');

        // Manual validation
        if ($name === '') {
            return send_error("Department name is required", 422);
        }

        if (strlen($name) < 2) {
            return send_error("Minimum 2 characters required", 422);
        }

        // Duplicate check
        if ($this->Departments_model->department_name_exists($name, $id)) {
            return send_error("Department name already exists", 409);
        }

        $data = ['name' => $name];

        if ($this->Departments_model->update_department($id, $data)) {
            send_success([], "Department updated successfully");
        } else {
            send_error("Failed to update department", 500);
        }
    }

    // DELETE
    public function delete($id) {
        $this->auth();

        $id = (int)$id;

        $existingDept = $this->Departments_model->get_department_by_id($id);
        if (!$existingDept) {
            send_error("Department not found", 404);
        }

        if ($this->Departments_model->delete_department($id)) {
            send_success([], "Department deleted successfully");
        } else {
            send_error("Failed to delete department", 500);
        }
    }
}