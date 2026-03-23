<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feedback extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Feedback_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');
    }

    // ----------------- PUBLIC: SUBMIT -----------------
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
            'createdby' => 1 // ✅ always 1
        ];

        $this->Feedback_model->insert($data);

        return success_response("Feedback submitted successfully");
    }

    // ----------------- ADMIN: VIEW -----------------
    public function get_all() {
        $user = $this->Jwt_model->verify_token();

        if (!$user || intval($user->role) !== 2) {
            return unauthorized("Access denied");
        }

        $data = $this->Feedback_model->get_all();
        return success_response("Feedbacks fetched", $data);
    }

    // ----------------- ADMIN: UPDATE -----------------
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

    // ----------------- ADMIN: TOGGLE -----------------
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

    // ----------------- PUBLIC: SHOW ENABLED -----------------
    public function get_enabled() {
        $data = $this->Feedback_model->get_enabled();
        return success_response("Feedbacks fetched", $data);
    }
}