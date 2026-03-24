<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category_model extends CI_Model {

    private $table = 'categories';

    public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }
    public function insert($data) {
    return $this->db->insert($this->table, $data);
}

public function update($id, $data) {
    return $this->db->where('id', $id)->update($this->table, $data);
}

public function delete($id) {
    return $this->db->where('id', $id)->delete($this->table);
}

public function get_all() {
    return $this->db->order_by('id', 'DESC')->get($this->table)->result();
}

public function exists($name, $excludeId = null) {
    $this->db->where('name', $name);

    if ($excludeId) {
        $this->db->where('id !=', $excludeId);
    }

    return $this->db->get($this->table)->num_rows() > 0;
}

public function get_by_id($id) {
    return $this->db->where('id', $id)->get($this->table)->row();
}


public function get_enabled() {
    return $this->db
        ->where('is_enabled', 1)
        ->get($this->table)
        ->result();
}
}