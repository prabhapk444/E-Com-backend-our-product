<?php
class Settings_model extends CI_Model {

    private $table = "settings";

    public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }


    public function get() {
        return $this->db->get($this->table)->row();
    }


    public function save($data) {
        $exists = $this->db->get($this->table)->row();

        if ($exists) {
            $this->db->where('id', $exists->id);
            return $this->db->update($this->table, $data);
        } else {
            return $this->db->insert($this->table, $data);
        }
    }
}