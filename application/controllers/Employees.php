<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employees extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Employee_model');
        $this->load->model('Jwt_model');
        $this->load->library('form_validation');
        $this->load->helper('response');
    }

    private function auth() {
        $user = $this->Jwt_model->verify_token();
        if (!$user || isset($user->expired)) {
            send_error("Unauthorized", 401);
        }
        return $user;
    }

    // GET
    public function index() {
        $this->auth();

        $filters = [
            'search' => $this->input->get('search', true),
            'role' => $this->input->get('role', true),
            'department' => $this->input->get('department', true),
        ];

        $data = $this->Employee_model->get_all($filters);

        send_success($data, "Employees fetched");
    }

    // CREATE
    public function store() {
        $user = $this->auth();

        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) {
            return send_error("Invalid input", 400);
        }

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('name', 'Name', 'required|min_length[3]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[employees.email]');
        $this->form_validation->set_rules('phone', 'Phone', 'required');
        $this->form_validation->set_rules('role', 'Role', 'required');
        $this->form_validation->set_rules('department', 'Department', 'required');
        $this->form_validation->set_rules('salary', 'Salary', 'required|numeric');
        $this->form_validation->set_rules('joined_date', 'Joined Date', 'required');

        if (!$this->form_validation->run()) {
            return send_error(validation_errors(), 422);
        }

        $data = [
            'name' => $input['name'] ?? null,
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'role' => $input['role'] ?? null,
            'department' => $input['department'] ?? null,
            'salary' => $input['salary'] ?? null,
            'joined_date' => $input['joined_date'] ?? date('Y-m-d'),
            'status' => 'active',
            'created_by' => $user->uid ?? $user->id ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $id = $this->Employee_model->insert($data);

        send_success(['id' => $id], "Employee created");
    }

    // UPDATE
    public function update($id) {
        $user = $this->auth();

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            return send_error("Invalid input", 400);
        }

        // Basic validation
        if (isset($input['email'])) {
            $existing = $this->Employee_model->get_by_id($id);
            if ($existing && $existing->email !== $input['email']) {
                $emailExists = $this->db->where('email', $input['email'])->get('employees')->row();
                if ($emailExists) {
                    return send_error("Email already exists", 409);
                }
            }
        }

        $input['updated_by'] = $user->uid ?? $user->id ?? null;
        $input['updated_at'] = date('Y-m-d H:i:s');

        $this->Employee_model->update_data($id, $input);

        send_success([], "Employee updated");
    }

    // TOGGLE STATUS
    public function status($id) {
        $user = $this->auth();

        $employee = $this->Employee_model->get_by_id($id);
        if (!$employee) {
            return send_error("Employee not found", 404);
        }

        $newStatus = $employee->status === "active" ? "inactive" : "active";

        $this->Employee_model->update_data($id, [
            "status" => $newStatus,
            "updated_by" => $user->uid ?? $user->id ?? null,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);

        send_success(["status" => $newStatus], "Employee status updated");
    }

    // DELETE
    public function delete($id) {
        $this->auth();

        $employee = $this->Employee_model->get_by_id($id);
        if (!$employee) {
            return send_error("Employee not found", 404);
        }

        $this->Employee_model->delete_data($id);

        send_success([], "Employee deleted");
    }
}