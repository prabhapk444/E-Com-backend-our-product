<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feedback extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Feedback_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');
    }

    private function check_role($allowed_roles = []) {
    $user = $this->Jwt_model->verify_token();
    if (!$user) return null; 
    if (!in_array(intval($user->role), $allowed_roles)) return false;
    return $user;
}

 
    public function submit() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input['name']) ||
            empty($input['phone']) ||
            empty($input['message']) ||
            empty($input['rating'])
        ) {
            return error_response("Required fields missing");
        }

        $data = [
            'name' => $input['name'],
            'phone' => $input['phone'],
            'place' => $input['place'] ?? null,
            'message' => $input['message'],
            'rating' => intval($input['rating']),
            'createdby' => 1 
        ];

        $this->Feedback_model->insert($data);

        return success_response("Feedback submitted successfully");
    }


    public function get_all() {
        $user = $this->Jwt_model->verify_token();

        if (!$user || intval($user->role) !== 2) {
            return unauthorized("Access denied");
        }

        $data = $this->Feedback_model->get_all();
        return success_response("Feedbacks fetched", $data);
    }

  
    public function update($id) {
        $user = $this->Jwt_model->verify_token();

        if (!$user || intval($user->role) !== 2) {
            return unauthorized("Access denied");
        }

        $input = json_decode(file_get_contents("php://input"), true);

        $data = [
            'name' => $input['name'],
            'phone' => $input['phone'],
            'place' => $input['place'],
            'message' => $input['message'],
            'rating' => intval($input['rating']),
            'updatedby' => $user->uid
        ];

        $this->Feedback_model->update($id, $data);

        return success_response("Feedback updated");
    }

  
    public function toggle_status($id) {
        $user = $this->Jwt_model->verify_token();

        if (!$user || intval($user->role) !== 2) {
            return unauthorized("Access denied");
        }

        $feedback = $this->Feedback_model->get_by_id($id);

        if (!$feedback) {
            return error_response("Feedback not found");
        }

        $newStatus = $feedback->is_enabled ? 0 : 1;

        $this->Feedback_model->toggle_status($id, $newStatus);

        return success_response("Status updated", [
            'newStatus' => $newStatus
        ]);
    }

   
    public function get_enabled() {
        $data = $this->Feedback_model->get_enabled();
        return success_response("Feedbacks fetched", $data);
    }

    public function delete($id) {
    $user = $this->Jwt_model->verify_token();

    if (!$user || intval($user->role) !== 2) {
        return unauthorized("Access denied");
    }

    $feedback = $this->Feedback_model->get_by_id($id);

    if (!$feedback) {
        return error_response("Feedback not found");
    }

    $this->Feedback_model->delete($id);

    return success_response("Feedback deleted successfully");
}

public function get_latest_enabled() {
    $data = $this->Feedback_model->get_latest_enabled(4);
    return success_response("Latest feedbacks fetched", $data);
}
}