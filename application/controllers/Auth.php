<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Jwt_model');
        $this->load->library('email_library');
        $this->load->helper('response');
    }

    // REGISTER
    public function register() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['email']) || empty($input['password']) || empty($input['name'])) {
            return error_response("Required fields missing");
        }

        // Check existing user
        if ($this->User_model->get_by_email($input['email'])) {
            return conflict("Email already exists");
        }

        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'phonenumber' => $input['phone'] ?? null,
            'place' => $input['place'] ?? null,
            'password' => password_hash($input['password'], PASSWORD_BCRYPT),
            'role' => 3
        ];

        $this->User_model->register($data);

        // Send welcome email
        $this->email_library->send_welcome_email($input['email'], $input['name']);

        return success_response("User registered successfully");
    }

    // LOGIN
    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);

        $user = $this->User_model->get_by_email($input['email']);

        if (!$user || !password_verify($input['password'], $user->password)) {
            return unauthorized("Invalid credentials");
        }

        $token = $this->Jwt_model->encode([
            'uid' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        return success_response("Login successful", [
            'token' => $token,
            'user' => $user
        ]);
    }

    // FORGOT PASSWORD (SEND LINK)
    public function forgot_password() {
        $input = json_decode(file_get_contents("php://input"), true);

        $user = $this->User_model->get_by_email($input['email']);
        if (!$user) return not_found("User not found");

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->User_model->save_reset_token([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expires
        ]);

        $reset_link = base_url("reset-password?token=" . $token);

        $this->email_library->send_password_reset($user->email, $reset_link);

        return success_response("Reset link sent to email");
    }

    // RESET PASSWORD
    public function reset_password() {
        $input = json_decode(file_get_contents("php://input"), true);

        $tokenData = $this->User_model->get_valid_token($input['token']);
        if (!$tokenData) return error_response("Invalid or expired token");

        $password = password_hash($input['password'], PASSWORD_BCRYPT);

        $this->User_model->update_password($tokenData->user_id, $password);
        $this->User_model->mark_token_used($input['token']);

        return success_response("Password reset successful");
    }

    // PROFILE (JWT PROTECTED)
    public function profile() {
        $user = $this->Jwt_model->verify_token();

        if (!$user) return unauthorized("Invalid token");

        $data = $this->User_model->get_by_id($user->uid);

        return success_response("Profile fetched", $data);
    }
}