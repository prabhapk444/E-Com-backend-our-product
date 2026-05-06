<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Departments_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get departments with optional search
     * @param string $search
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_departments($search = '', $limit = 10, $offset = 0) {
        $this->db->select('id, name, created_at, updated_at');
        $this->db->from('departments');
        
        if ($search != '') {
            $this->db->like('name', $search);
        }
        
        $this->db->order_by('name', 'ASC');
        $this->db->limit($limit, $offset);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get total count of departments (for pagination)
     * @param string $search
     * @return int
     */
    public function get_departments_count($search = '') {
        $this->db->from('departments');
        
        if ($search != '') {
            $this->db->like('name', $search);
        }
        
        $query = $this->db->get();
        return $query->num_rows();
    }

    /**
     * Get department by ID
     * @param int $id
     * @return array|null
     */
    public function get_department_by_id($id) {
        $this->db->select('id, name, created_at, updated_at');
        $this->db->from('departments');
        $this->db->where('id', $id);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        return null;
    }

    /**
     * Create a new department
     * @param array $data
     * @return bool
     */
    public function create_department($data) {
        $this->db->insert('departments', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Update a department
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_department($id, $data) {
        $this->db->where('id', $id);
        $this->db->update('departments', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete a department
     * @param int $id
     * @return bool
     */
    public function delete_department($id) {
        $this->db->where('id', $id);
        $this->db->delete('departments');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Check if department name exists (excluding current ID if provided)
     * @param string $name
     * @param int $excludeId
     * @return bool
     */
    public function department_name_exists($name, $excludeId = 0) {
        $this->db->from('departments');
        $this->db->where('name', $name);
        
        if ($excludeId > 0) {
            $this->db->where('id !=', $excludeId);
        }
        
        $query = $this->db->get();
        return $query->num_rows() > 0;
    }
}