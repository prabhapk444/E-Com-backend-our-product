<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once FCPATH . 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Jwt_model');
        $this->load->model('Google_oauth');
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

  
    if (!$input || empty($input['email']) || empty($input['password'])) {
        return error_response("Email & Password required");
    }


    $user = $this->User_model->get_by_email($input['email']);

    if (!$user) {
        return unauthorized("Invalid credentials");
    }


    if (!password_verify($input['password'], $user->password)) {
        return unauthorized("Invalid credentials");
    }

  
    if (intval($user->is_enabled) !== 1) {
        return unauthorized("Your account is disabled. Contact admin.");
    }

  
    $token = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => intval($user->role)
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

  
    if (intval($user->role) !== 1) {
        return unauthorized("Access denied. Not super admin");
    }

   
    if (intval($user->is_enabled) !== 1) {
        return unauthorized("Your account is disabled. Contact admin.");
    }

    $token = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => intval($user->role)
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

  
    if (intval($user->role) !== 2) {
        return unauthorized("Access denied. Not admin");
    }

  
    if (intval($user->is_enabled) !== 1) {
        return unauthorized("Your account is disabled. Contact admin.");
    }

    $token = $this->Jwt_model->encode([
        'uid' => $user->id,
        'email' => $user->email,
        'role' => intval($user->role)
    ]);

    return success_response("Admin login successful", [
        'token' => $token,
        'user' => $user
    ]);
}

public function google_register() {
    // Read token from Authorization: Bearer header
    $headers = $this->input->request_headers();
    $auth_header = $headers['Authorization'] ?? null;
    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        return error_response("Token missing");
    }
    $token = $matches[1];

    // Verify using google/apiclient
    $payload = $this->Google_oauth->verify($token);
    if (!$payload || !isset($payload['email'])) {
        return unauthorized("Invalid Google token");
    }

    $email = $payload['email'];
    $name = $payload['name'] ?? explode('@', $email)[0];

    $user = $this->User_model->get_by_email($email);
    if ($user) return conflict("User already exists");

    $data = [
        'name'      => $name,
        'email'     => $email,
        'password'  => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
        'google_id' => $payload['sub'],
        'role'      => 3
    ];
    $this->User_model->register($data);
    $user = $this->User_model->get_by_email($email);

    $jwt = $this->Jwt_model->encode([
        'uid'   => $user->id,
        'email' => $user->email,
        'role'  => intval($user->role)
    ]);

    return success_response("Google registration success", [
        'token' => $jwt,
        'user'  => $user
    ]);
}

public function google_login() {
    // Read token from Authorization: Bearer header (like Lifeboat)
    $headers = $this->input->request_headers();
    $auth_header = $headers['Authorization'] ?? null;
    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        log_message('error', '[GAuth] google_login: Authorization header missing or malformed');
        return error_response("Token missing");
    }
    $token = $matches[1];

    // Verify using google/apiclient (proper audience validation)
    $payload = $this->Google_oauth->verify($token);
    if (!$payload || !isset($payload['email'])) {
        log_message('error', '[GAuth] google_login: Token verification returned no payload');
        return unauthorized("Invalid Google token");
    }

    $email = $payload['email'];
    $name = $payload['name'] ?? explode('@', $email)[0];

    log_message('info', '[GAuth] google_login: Token verified for email=' . $email);

    $user = $this->User_model->get_by_email($email);

    if (!$user) {
        log_message('info', '[GAuth] google_login: New user, registering email=' . $email);
        $data = [
            'name'      => $name,
            'email'     => $email,
            'password'  => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
            'google_id' => $payload['sub'],
            'role'      => 3
        ];
        $this->User_model->register($data);
        $user = $this->User_model->get_by_email($email);
        $this->email_library->send_welcome_email($email, $name);
    } else {
        if (intval($user->is_enabled) !== 1) {
            log_message('error', '[GAuth] google_login: Account disabled for email=' . $email);
            return unauthorized("Your account is disabled. Contact admin.");
        }
        log_message('info', '[GAuth] google_login: Existing user login email=' . $email);
    }

    $jwt = $this->Jwt_model->encode([
        'uid'   => $user->id,
        'email' => $user->email,
        'role'  => intval($user->role)
    ]);

    return success_response("Google login success", [
        'token' => $jwt,
        'user'  => $user
    ]);
}

   public function forgot_password() {
    $input = json_decode(file_get_contents("php://input"), true);

    $user = $this->User_model->get_by_email($input['email']);
    if (!$user) return not_found("User not found");


    $otp = rand(100000, 999999);
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));


    $this->User_model->save_reset_token([
        'user_id' => $user->id,
        'token' => $otp,
        'expires_at' => $expires
    ]);

  
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


// Get all admin users (role = 2)
public function get_admins() {
    $user = $this->Jwt_model->verify_token();
    if (!$user || intval($user->role) !== 1) return unauthorized("Access denied");

    $admins = $this->db->where('role', 2)->get('users')->result();
    return success_response("Admins fetched", $admins); 
}

// Create or update admin
public function save_admin() {
    $user = $this->Jwt_model->verify_token();
    if (!$user || $user->role != 1) return unauthorized("Access denied");

    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['id']) && !empty($input['id'])) {
     
        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'phonenumber' => $input['phone'] ?? null,
            'place' => $input['place'] ?? null,
        ];
        if (!empty($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        $this->db->where('id', $input['id'])->update('users', $data);
        return success_response("Admin updated");
    } else {
  
        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => password_hash($input['password'], PASSWORD_BCRYPT),
            'place' => $input['place'] ?? null,
            'role' => 2,
            'createdat' => date('Y-m-d'),
        ];
        $this->db->insert('users', $data);
        return success_response("Admin created");
    }
}


public function toggle_admin_status($id)
{
    $this->load->model('User_model');

    $admin = $this->User_model->get_by_id($id);
    if (!$admin) {
        echo json_encode(['status' => false, 'message' => 'Admin not found']);
        return;
    }

  
    $currentStatus = $admin->is_enabled; 
    $newStatus = $currentStatus ? 0 : 1;

    $updated = $this->User_model->update_status($id, $newStatus);

    if ($updated) {
        echo json_encode(['status' => true, 'message' => 'Status updated', 'newStatus' => $newStatus]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to update status']);
    }
}
// Delete admin
public function delete_admin($id) {
    $user = $this->Jwt_model->verify_token();
    if (!$user || $user->role != 1) return unauthorized("Access denied");

    $this->db->where('id', $id)->where('role', 2)->delete('users');
    return success_response("Admin deleted");
}

// Get all normal users (role = 3)
public function get_users() {
    $user = $this->Jwt_model->verify_token();
    if (!$user || intval($user->role) !== 2) {
        return unauthorized("Access denied");
    }

    $users = $this->db
        ->select('id, name, email, place, phonenumber, createdat, is_enabled')
        ->where('role', 3)
        ->get('users')
        ->result();

    return success_response("Users fetched", $users);
}

public function toggle_user_status($id)
{
    $user = $this->Jwt_model->verify_token();

    // Allow admin + super admin
    if (!$user || !in_array(intval($user->role), [1, 2])) {
        return unauthorized("Access denied");
    }

    $this->load->model('User_model');

    $userData = $this->User_model->get_by_id($id);

    if (!$userData || intval($userData->role) !== 3) {
        return error_response("User not found or invalid role");
    }

    $currentStatus = $userData->is_enabled;
    $newStatus = $currentStatus ? 0 : 1;

    $updated = $this->User_model->update_status($id, $newStatus);

    if ($updated) {
        return success_response("User status updated", [
            'newStatus' => $newStatus
        ]);
    } else {
        return error_response("Failed to update");
    }
}

}