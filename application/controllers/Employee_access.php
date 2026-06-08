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
        return $user;
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

        $updatedBy = $user->uid ?? $user->id ?? null;
        $this->Employee_access_model->save_permissions($employeeId, $input['permissions'], $updatedBy);

        $updated = $this->Employee_access_model->get_by_employee_map($employeeId);

        send_success([
            'employee_id' => (int)$employee->id,
            'employee_name' => $employee->name,
            'permissions' => $updated,
        ], "Permissions updated");
    }
}
