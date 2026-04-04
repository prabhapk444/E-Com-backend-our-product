<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'products';
        $this->variants_table = 'product_variants';
        $this->attributes_table = 'variant_attributes';
    }

    // Get all products with pagination and filters
    public function get_all($limit = 10, $offset = 0, $search = '', $category_id = null, $is_active = null) {
        $this->db->select('p.*, c.name as category_name, sc.name as subcategory_name');
        $this->db->from("{$this->table} as p");
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('subcategories sc', 'sc.id = p.subcategory_id', 'left');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.description', $search);
            $this->db->group_end();
        }

        if ($category_id !== null && $category_id !== '') {
            $this->db->where('p.category_id', $category_id);
        }

        if ($is_active !== null && $is_active !== '') {
            $this->db->where('p.is_active', $is_active);
        }

        $this->db->order_by('p.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    // Get total count
    public function get_total($search = '', $category_id = null, $is_active = null) {
        $this->db->from($this->table);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->or_like('description', $search);
            $this->db->group_end();
        }

        if ($category_id !== null && $category_id !== '') {
            $this->db->where('category_id', $category_id);
        }

        if ($is_active !== null && $is_active !== '') {
            $this->db->where('is_active', $is_active);
        }

        return $this->db->count_all_results();
    }

    // Get single product by ID
    public function get_by_id($id) {
        $this->db->select('p.*, c.name as category_name, sc.name as subcategory_name');
        $this->db->from("{$this->table} as p");
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('subcategories sc', 'sc.id = p.subcategory_id', 'left');
        $this->db->where('p.id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    // Get single product by slug
    public function get_by_slug($slug) {
        $this->db->select('p.*, c.name as category_name, sc.name as subcategory_name');
        $this->db->from("{$this->table} as p");
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('subcategories sc', 'sc.id = p.subcategory_id', 'left');
        $this->db->where('p.slug', $slug);
        $this->db->where('p.is_active', '1');
        $query = $this->db->get();
        return $query->row_array();
    }

    // Get product variants
    public function get_variants($product_id) {
        $this->db->where('product_id', $product_id);
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get($this->variants_table);
        $variants = $query->result_array();
        
        // Get attributes for each variant
        foreach ($variants as &$variant) {
            $this->db->where('variant_id', $variant['id']);
            $attr_query = $this->db->get($this->attributes_table);
            $variant['attributes'] = $attr_query->result_array();
        }
        
        return $variants;
    }

    // Get single variant
    public function get_variant_by_id($id) {
        $this->db->where('id', $id);
        $query = $this->db->get($this->variants_table);
        return $query->row_array();
    }

    // Create product
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    // Update product
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    // Delete product
    public function delete($id) {
        // First delete all variants
        $this->db->where('product_id', $id);
        $this->db->delete($this->variants_table);
        
        // Then delete the product
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    // Toggle product status
    public function toggle_status($id) {
        $product = $this->get_by_id($id);
        if (!$product) {
            return false;
        }

        $new_status = ($product['is_active'] == '1') ? '0' : '1';
        
        $this->db->where('id', $id);
        $this->db->update($this->table, [
            'is_active' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return ['newStatus' => $new_status, 'is_active' => $new_status];
    }

    // Create variant
    public function create_variant($data, $attributes = []) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->variants_table, $data);
        $variant_id = $this->db->insert_id();
        
        // Add attributes if provided
        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $attr) {
                $attr_data = [
                    'variant_id' => $variant_id,
                    'name' => $attr['name'] ?? null,
                    'value' => $attr['value'] ?? null
                ];
                $this->db->insert($this->attributes_table, $attr_data);
            }
        }
        
        return $variant_id;
    }

    // Update variant
    public function update_variant($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        return $this->db->update($this->variants_table, $data);
    }

    // Delete variant
    public function delete_variant($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->variants_table);
    }

    // Delete all variants of a product
    public function delete_all_variants($product_id) {
        // First get all variant IDs
        $this->db->where('product_id', $product_id);
        $variants = $this->db->get($this->variants_table)->result_array();
        
        // Delete all attributes for these variants
        foreach ($variants as $variant) {
            $this->db->where('variant_id', $variant['id']);
            $this->db->delete($this->attributes_table);
        }
        
        // Then delete the variants
        $this->db->where('product_id', $product_id);
        return $this->db->delete($this->variants_table);
    }

  
    // Check if slug exists
    public function slug_exists($slug, $exclude_id = null) {
        $this->db->where('slug', $slug);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    // Get featured products
    public function get_featured($limit = 10) {
        $this->db->where('featured', 1);
        $this->db->where('is_active', '1');
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    // Get products by category
    public function get_by_category($category_id, $limit = 20, $offset = 0) {
        $this->db->where('category_id', $category_id);
        $this->db->where('is_active', '1');
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    // Update stock
    public function update_stock($variant_id, $quantity) {
        $this->db->set('stock', 'stock - ' . (int)$quantity, FALSE);
        $this->db->where('id', $variant_id);
        return $this->db->update($this->variants_table);
    }

    // Get product count by category
    public function get_count_by_category() {
        $this->db->select('category_id, COUNT(*) as count');
        $this->db->where('is_active', '1');
        $this->db->group_by('category_id');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    // ============================================
    // VARIANT ATTRIBUTES METHODS
    // ============================================

    // Create variant attribute
    public function create_variant_attribute($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->attributes_table, $data);
        return $this->db->insert_id();
    }

    // Get attributes for a variant
    public function get_variant_attributes($variant_id) {
        $this->db->where('variant_id', $variant_id);
        $query = $this->db->get($this->attributes_table);
        return $query->result_array();
    }

    // Delete all attributes for a variant
    public function delete_variant_attributes($variant_id) {
        $this->db->where('variant_id', $variant_id);
        return $this->db->delete($this->attributes_table);
    }

    // Get variants with attributes
    public function get_variants_with_attributes($product_id) {
        $variants = $this->get_variants($product_id);
        
        foreach ($variants as &$variant) {
            $variant['attributes'] = $this->get_variant_attributes($variant['id']);
        }
        
        return $variants;
    }
}
