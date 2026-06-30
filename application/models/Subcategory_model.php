<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subcategory_model extends CI_Model {

    protected $table = 'subcategories';

    
    public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }

    public function get_all() {
        $this->db->select('s.*, c.name as category_name')
                 ->from('subcategories s')
                 ->join('categories c', 'c.id = s.category_id', 'left')
                 ->order_by('s.id','DESC');
        return $this->db->get()->result();
    }

    public function get_enabled() {
    return $this->db
        ->where('is_enabled', 1)
        ->order_by('id', 'DESC')
        ->get($this->table)
        ->result();
}

    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function toggle_status($id) {
        $sub = $this->db->get_where($this->table, ['id' => $id])->row();
        if($sub){
            $newStatus = $sub->is_enabled ? 0 : 1;
            $this->db->where('id', $id)->update($this->table, ['is_enabled' => $newStatus]);
            return $newStatus;
        }
        return false;
    }

    public function get($id)
{
    return $this->db
        ->where('id', $id)
        ->get($this->table)
        ->row();
}
}