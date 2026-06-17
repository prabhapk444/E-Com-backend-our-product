<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_access extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Employee_access_model');
        $this->load->model('Employee_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');

        header("Content-Type: application/json");
    }

    private function auth() {
        $user = $this->Jwt_model->verify_token();
        if (!$user || isset($user->expired)) {
            send_error("Unauthorized", 401);
        }
        if (!in_array((int)$user->role, [1, 2])) {
            send_error("Forbidden", 403);
        }
        return $user;
    }

    private function updated_by($user) {
        return $user->uid ?? $user->id ?? null;
    }

    // GET /employee_access
    public function index() {
        $user = $this->auth();

        $filters = [
            'search' => $this->input->get('search', true),
            'role' => $this->input->get('role', true),
            'department' => $this->input->get('department', true),
            'status' => $this->input->get('status', true),
        ];

        $employees = $this->Employee_access_model->get_all_with_employees($filters);
        $items = [];

        foreach ($employees as $employee) {
            $items[] = [
                'id' => (int)$employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
                'department' => $employee->department,
                'status' => $employee->status,
                'last_login_at' => $employee->last_login_at,
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
                'permissions' => $this->Employee_access_model->get_by_employee_map($employee->id),
                'summary' => $this->Employee_access_model->get_employee_access_summary($employee->id),
            ];
        }

        send_success($items, "Employee access fetched");
    }

    // POST /employee_access/employee/:id
    public function create_employee($employeeId) {
        $user = $this->auth();

        $employee = $this->Employee_model->get_by_id($employeeId);
        if (!$employee) {
            return send_error("Employee not found", 404);
        }

        $this->Employee_access_model->delete_employee_permissions($employeeId);
        $this->Employee_access_model->create_default_permissions($employeeId, $this->updated_by($user));
        $permissions = $this->Employee_access_model->get_by_employee_map($employeeId);

        send_success([
            'employee_id' => (int)$employee->id,
            'employee_name' => $employee->name,
            'permissions' => $permissions,
        ], "Employee access initialized");
    }

    // GET /employee_access/employee/:id
    public function employee($employeeId) {
        $this->auth();

        $employee = $this->Employee_model->get_by_id($employeeId);
        if (!$employee) {
            return send_error("Employee not found", 404);
        }

        $permissions = $this->Employee_access_model->get_by_employee_map($employeeId);

        send_success([
            'employee_id' => (int)$employee->id,
            'employee_name' => $employee->name,
            'permissions' => $permissions,
        ], "Permissions fetched");
    }

    // PUT /employee_access/employee/:id
    public function update_employee($employeeId) {
        $user = $this->auth();

        $employee = $this->Employee_model->get_by_id($employeeId);
        if (!$employee) {
            return send_error("Employee not found", 404);
        }

        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['permissions']) || !is_array($input['permissions'])) {
            return send_error("Invalid input, permissions array required", 400);
        }

        $updatedBy = $this->updated_by($user);
        $this->Employee_access_model->save_permissions($employeeId, $input['permissions'], $updatedBy);

        $updated = $this->Employee_access_model->get_by_employee_map($employeeId);

        send_success([
            'employee_id' => (int)$employee->id,
            'employee_name' => $employee->name,
            'permissions' => $updated,
        ], "Permissions updated");
    }

    // DELETE /employee_access/employee/:id
    public function delete_employee($employeeId) {
        $user = $this->auth();

        $employee = $this->Employee_model->get_by_id($employeeId);
        if (!$employee) {
            return send_error("Employee not found", 404);
        }

        $this->Employee_access_model->delete_employee_permissions($employeeId);

        send_success([
            'employee_id' => (int)$employee->id,
            'employee_name' => $employee->name,
        ], "Employee access revoked");
    }
}
