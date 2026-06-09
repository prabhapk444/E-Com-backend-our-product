<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_access_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    private $table = 'employee_access';

    public function get_by_employee($employeeId) {
        return $this->db->where('employee_id', $employeeId)->get($this->table)->result();
    }

    public function get_by_employee_map($employeeId) {
        $rows = $this->db->where('employee_id', $employeeId)->get($this->table)->result();
        $map = [];
        foreach ($rows as $row) {
            $map[$row->module_key] = [
                'view'     => (bool)$row->can_view,
                'create'   => (bool)$row->can_create,
                'edit'     => (bool)$row->can_edit,
                'delete'   => (bool)$row->can_delete,
                'export'   => (bool)$row->can_export,
                'status'   => (bool)$row->can_status,
            ];
        }
        return $map;
    }

    public function save_permissions($employeeId, array $permissions, $updatedBy) {
        $this->db->where('employee_id', $employeeId)->delete($this->table);

        $now = date('Y-m-d H:i:s');
        $data = [];
        foreach ($permissions as $moduleKey => $perms) {
            $data[] = [
                'employee_id' => $employeeId,
                'module_key'  => $moduleKey,
                'can_view'    => !empty($perms['view'])     ? 1 : 0,
                'can_create'  => !empty($perms['create'])   ? 1 : 0,
                'can_edit'    => !empty($perms['edit'])     ? 1 : 0,
                'can_delete'  => !empty($perms['delete'])   ? 1 : 0,
                'can_export'  => !empty($perms['export'])   ? 1 : 0,
                'can_status'  => !empty($perms['status'])   ? 1 : 0,
                'updated_by'  => $updatedBy,
                'updated_at'  => $now,
            ];
        }

        if (!empty($data)) {
            $this->db->insert_batch($this->table, $data);
        }

        return true;
    }
}
