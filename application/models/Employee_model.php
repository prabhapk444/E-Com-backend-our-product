<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_model extends CI_Model {


 public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }

    private $table = 'employees';

    public function get_all($filters = []) {
        $this->db->from($this->table);

        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('name', $filters['search'])
                ->or_like('email', $filters['search'])
                ->or_like('phone', $filters['search'])
            ->group_end();
        }

        if (!empty($filters['role'])) {
            $this->db->where('role', $filters['role']);
        }

        if (!empty($filters['department'])) {
            $this->db->where('department', $filters['department']);
        }

        return $this->db->order_by('id', 'DESC')->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function insert($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_data($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete_data($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function get_by_email($email) {
        return $this->db->where('email', $email)->get($this->table)->row();
    }

    public function update_password($employeeId, $password) {
        return $this->db->where('id', $employeeId)->update($this->table, ['password' => $password]);
    }

    public function save_reset_token($data) {
        $this->db->insert('password_reset_tokens', $data);
    }

    public function get_valid_token($token) {
        return $this->db->where('token', $token)
            ->where('used', 0)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('password_reset_tokens')
            ->row();
    }

    public function mark_token_used($token) {
        return $this->db->where('token', $token)->update('password_reset_tokens', ['used' => 1]);
    }
}