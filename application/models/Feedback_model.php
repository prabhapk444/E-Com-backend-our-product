<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feedback_model extends CI_Model {

    private $table = 'feedbacks';

    public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }


    // Insert feedback (public)
    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    // Get all feedbacks (admin only)
    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get($this->table)->result();
    }

    // Get only enabled feedbacks (frontend)
    public function get_enabled() {
        return $this->db
            ->where('is_enabled', 1)
            ->order_by('id', 'DESC')
            ->get($this->table)
            ->result();
    }

    // Get by ID
    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    // Update feedback (admin)
    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // Toggle status
    public function toggle_status($id, $status) {
        return $this->db->where('id', $id)
            ->update($this->table, ['is_enabled' => $status]);
    }

    public function delete($id) {
    return $this->db->where('id', $id)->delete($this->table);
   }
}