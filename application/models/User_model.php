<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {



    private $table = 'users';


    public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }



    // Register User
    public function register($data) {
        return $this->db->insert($this->table, $data);
    }

    // Get user by email
    public function get_by_email($email) {
        return $this->db->where('email', $email)->get($this->table)->row();
    }

    public function get_by_username($username) {
    return $this->db->where('name', $username)->get($this->table)->row();
}

    // Get user by ID
    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    // Update password
    public function update_password($user_id, $password) {
        return $this->db->where('id', $user_id)
                        ->update($this->table, ['password' => $password]);
    }

    // Save reset token
    public function save_reset_token($data) {
        return $this->db->insert('password_reset_tokens', $data);
    }

    // Get valid token
    public function get_valid_token($token) {
        return $this->db->where('token', $token)
                        ->where('used', 0)
                        ->where('expires_at >', date('Y-m-d H:i:s'))
                        ->get('password_reset_tokens')
                        ->row();
    }

    // Mark token used
    public function mark_token_used($token) {
        return $this->db->where('token', $token)
                        ->update('password_reset_tokens', ['used' => 1]);
    }
}