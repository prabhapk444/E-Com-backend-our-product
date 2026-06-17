<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_access_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    private $table = 'employee_access';
    private $actions = [
        'view' => 'can_view',
        'create' => 'can_create',
        'edit' => 'can_edit',
        'delete' => 'can_delete',
        'export' => 'can_export',
        'status' => 'can_status',
    ];

    public function get_all_with_employees($filters = []) {
        $this->db->select('employees.id, employees.name, employees.email, employees.role, employees.department, employees.status, employees.last_login_at, employees.created_at, employees.updated_at')
            ->from('employees')
            ->join($this->table, 'employees.id = employee_access.employee_id', 'left')
            ->group_by('employees.id');

        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('employees.name', $filters['search'])
                ->or_like('employees.email', $filters['search'])
                ->or_like('employees.role', $filters['search'])
                ->or_like('employees.department', $filters['search'])
                ->group_end();
        }

        if (!empty($filters['role'])) {
            $this->db->where('employees.role', $filters['role']);
        }

        if (!empty($filters['department'])) {
            $this->db->where('employees.department', $filters['department']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('employees.status', $filters['status']);
        }

        return $this->db->order_by('employees.id', 'DESC')->get()->result();
    }

    public function get_by_employee($employeeId) {
        return $this->db->where('employee_id', $employeeId)->get($this->table)->result();
    }

    public function get_by_employee_map($employeeId) {
        $rows = $this->db->where('employee_id', $employeeId)->get($this->table)->result();
        $map = [];
        foreach ($rows as $row) {
            $map[$row->module_key] = $this->row_to_permissions($row);
        }
        return $map;
    }

    public function get_employee_access_summary($employeeId) {
        $rows = $this->get_by_employee($employeeId);
        $summary = [
            'total_modules' => 0,
            'granted_modules' => 0,
            'status_modules' => 0,
        ];

        foreach ($rows as $row) {
            $perms = $this->row_to_permissions($row);
            $summary['total_modules']++;
            if (!empty($perms['view'])) {
                $summary['granted_modules']++;
            }
            if (!empty($perms['status'])) {
                $summary['status_modules']++;
            }
        }

        return $summary;
    }

    private function row_to_permissions($row) {
        return [
            'view'     => (bool)$row->can_view,
            'create'   => (bool)$row->can_create,
            'edit'     => (bool)$row->can_edit,
            'delete'   => (bool)$row->can_delete,
            'export'   => (bool)$row->can_export,
            'status'   => (bool)$row->can_status,
        ];
    }

    public function has_permission($employeeId, $moduleKey, $action = 'view') {
        if (!$employeeId) return false;
        $column = $this->actions[$action] ?? $this->actions['view'];

        $row = $this->db->select($column)
            ->where('employee_id', $employeeId)
            ->where('module_key', $moduleKey)
            ->get($this->table)
            ->row();

        return $row ? (bool)$row->$column : false;
    }

    public function create_default_permissions($employeeId, $updatedBy) {
        $modules = ['dashboard', 'categories', 'subcategories', 'products', 'orders', 'users', 'employees', 'employee_roles', 'employee_access', 'departments', 'reports', 'feedback', 'reviews', 'settings'];
        $now = date('Y-m-d H:i:s');
        $data = [];

        foreach ($modules as $module) {
            $data[] = [
                'employee_id' => $employeeId,
                'module_key' => $module,
                'can_view' => 0,
                'can_create' => 0,
                'can_edit' => 0,
                'can_delete' => 0,
                'can_export' => 0,
                'can_status' => 0,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            $this->db->insert_batch($this->table, $data);
        }

        return true;
    }

    public function save_permissions($employeeId, array $permissions, $updatedBy) {
        $this->db->where('employee_id', $employeeId)->delete($this->table);

        $now = date('Y-m-d H:i:s');
        $data = [];
        foreach ($permissions as $moduleKey => $perms) {
            if (!is_array($perms)) continue;

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

    public function delete_employee_permissions($employeeId) {
        return $this->db->where('employee_id', $employeeId)->delete($this->table);
    }
}
