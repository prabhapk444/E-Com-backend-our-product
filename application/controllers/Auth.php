<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Jwt_model');
        $this->load->library('email_library');
        $this->load->helper('response');
         $this->load->helper('url');
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


  public function superadmin_login() {
    $input = json_decode(file_get_contents("php://input"), true);

    if (empty($input['username']) || empty($input['password'])) {
        return error_response("Username & Password required");
    }

    $user = $this->User_model->get_by_username($input['username']);

    if (!$user || !password_verify($input['password'], $user->password)) {
        return unauthorized("Invalid credentials");
    }

    if ($user->role != 1) {
        return unauthorized("Access denied. Not super admin");
    }

    $token = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => $user->role
    ]);

    return success_response("Superadmin login success", [
        'token' => $token,
        'user' => $user
    ]);
}


  public function admin_login() {
    $input = json_decode(file_get_contents("php://input"), true);

    if (empty($input['email']) || empty($input['password'])) {
        return error_response("Email & Password required");
    }

  
    $user = $this->User_model->get_by_email($input['email']);


    if (!$user || !password_verify($input['password'], $user->password)) {
        return unauthorized("Invalid credentials");
    }

   
    if ($user->role != 2) {
        return unauthorized("Access denied. Not admin");
    }


    $token = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => $user->role
    ]);

    return success_response("Admin login successful", [
        'token' => $token,
        'user' => $user
    ]);
}

public function google_register() {
    $input = json_decode(file_get_contents("php://input"), true);
    $token = $input['token'] ?? null;

    if (!$token) return error_response("Token missing");

    // Verify token with Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
    $response = file_get_contents($url);
    $payload = json_decode($response);

    if (!$payload || !isset($payload->email)) {
        return unauthorized("Invalid Google token");
    }

    $email = $payload->email;
    $name = $payload->name ?? explode('@', $email)[0];

    $user = $this->User_model->get_by_email($email);
    if ($user) return conflict("User already exists");

    $data = [
        'name' => $name,
        'email' => $email,
        'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT), 
        'role' => 3
    ];
    $this->User_model->register($data);
    $user = $this->User_model->get_by_email($email);

    $jwt = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => $user->role
    ]);

    return success_response("Google registration success", [
        'token' => $jwt,
        'user' => $user
    ]);
}

public function google_login() {
    $input = json_decode(file_get_contents("php://input"), true);
    $token = $input['token'] ?? null;

    if (!$token) return error_response("Token missing");

    // Verify token with Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
    $response = file_get_contents($url);
    $payload = json_decode($response);

    if (!$payload || !isset($payload->email)) {
        return unauthorized("Invalid Google token");
    }

    $email = $payload->email;
    $name = $payload->name ?? explode('@', $email)[0];

    $user = $this->User_model->get_by_email($email);

    if (!$user) {
        // Register user automatically
        $data = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT), 
            'role' => 3
        ];
        $this->User_model->register($data);
        $user = $this->User_model->get_by_email($email);
    }

    $jwt = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => $user->role
    ]);

    return success_response("Google login success", [
        'token' => $jwt,
        'user' => $user
    ]);
}

   public function forgot_password() {
    $input = json_decode(file_get_contents("php://input"), true);

    $user = $this->User_model->get_by_email($input['email']);
    if (!$user) return not_found("User not found");

    // Generate OTP
    $otp = rand(100000, 999999);
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Save OTP
    $this->User_model->save_reset_token([
        'user_id' => $user->id,
        'token' => $otp, // use token column as OTP
        'expires_at' => $expires
    ]);

    // Send OTP email
    $this->email_library->send_otp_email($user->email, $otp);

    return success_response("OTP sent to email");
}

    // RESET PASSWORD
   public function reset_password() {
    $input = json_decode(file_get_contents("php://input"), true);

    $tokenData = $this->User_model->get_valid_token($input['otp']);

    if (!$tokenData) return error_response("Invalid or expired OTP");

    $password = password_hash($input['password'], PASSWORD_BCRYPT);

    $this->User_model->update_password($tokenData->user_id, $password);
    $this->User_model->mark_token_used($input['otp']);

    return success_response("Password reset successful");
}

  
    public function update_profile() {
    $user = $this->Jwt_model->verify_token();
    if (!$user) return unauthorized("Invalid token");

    $input = json_decode(file_get_contents("php://input"), true);

   $data = [
    'name' => $input['name'],
    'email' => $input['email'],
    'phonenumber' => $input['phonenumber'],
    'place' => $input['place']
];

    $this->db->where('id', $user->uid)->update('users', $data);

    return success_response("Profile updated");
}
}