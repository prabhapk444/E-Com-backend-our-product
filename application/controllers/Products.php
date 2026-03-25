<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Products extends CI_Controller {

    private $upload_path = 'uploads/products/';
    private $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct() {
        parent::__construct();
        $this->load->model('product_model')
        $this->load->model('jwt_model');
        $this->load->helper('response_helper');
        $this->load->database();
        
        // Create upload directory if not exists
        if (!is_dir($this->upload_path)) {
            mkdir($this->upload_path, 0777, true);
        }
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function get_current_user() {
        $token = $this->jwt_model->get_token_from_header();
        if (empty($token)) {
            return null;
        }
        
        $token = trim($token);
        $decoded = $this->jwt_model->decode($token);
        
        if (!$decoded) {
            return null;
        }
        
        return $decoded;
    }

    private function has_permission($required_role = '2') {
        $user = $this->get_current_user();
        
        if (!$user) {
            return ['valid' => false, 'user' => null, 'message' => 'Unauthorized'];
        }

        $user_role = isset($user->role) ? $user->role : (isset($user->role_id) ? $user->role_id : null);
        $user_role = $user_role !== null ? (string)$user_role : null;

        if ($user_role !== $required_role) {
            return ['valid' => false, 'user' => $user, 'message' => 'Forbidden: You do not have permission'];
        }

        return ['valid' => true, 'user' => $user];
    }

  
private function upload_image($field_name, $folder = '')
{
    if (!isset($_FILES[$field_name])) return null;

    $upload_path = FCPATH . $this->upload_path . $folder;

    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0777, true);
    }

    $file_name = time() . '_' . basename($_FILES[$field_name]['name']);
    $target = $upload_path . $file_name;

    if (move_uploaded_file($_FILES[$field_name]['tmp_name'], $target)) {
        return $this->upload_path . $file_name;
    }

    return null;
}

    // ============================================
    // PUBLIC METHODS
    // ============================================

    public function index() {
        $page = (int)$this->input->get('page') ?: 1;
        $limit = (int)$this->input->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;
        $search = $this->input->get('search') ?: '';
        $category_id = $this->input->get('category_id');
        $is_active = $this->input->get('is_active');

        $products = $this->product_model->get_all($limit, $offset, $search, $category_id, $is_active);
        
        foreach ($products as &$product) {
            $product['variants'] = $this->product_model->get_variants($product['id']);
            $product['categoryId'] = $product['category_id'];
            $product['subcategoryId'] = $product['subcategory_id'];
            
            // Convert image paths to full URLs
            if (!empty($product['image'])) {
                $product['image'] = base_url($product['image']);
            }
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true) ?: [];
                $product['images'] = array_map(function($img) {
                    return base_url($img);
                }, $images);
            }
        }

        $total = $this->product_model->get_total($search, $category_id, $is_active);

        success_response('Products fetched successfully', [
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function get($id) {
        $product = $this->product_model->get_by_id($id);
        
        if (!$product) {
            not_found('Product not found');
            return;
        }

        $product['variants'] = $this->product_model->get_variants($id);
        $product['categoryId'] = $product['category_id'];
        $product['subcategoryId'] = $product['subcategory_id'];
        
        // Convert image paths to full URLs
        if (!empty($product['image'])) {
            $product['image'] = base_url($product['image']);
        }
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true) ?: [];
            $product['images'] = array_map(function($img) {
                return base_url($img);
            }, $images);
        }
        
        // Convert variant images
        foreach ($product['variants'] as &$variant) {
            if (!empty($variant['image'])) {
                $variant['image'] = base_url($variant['image']);
            }
        }

        success_response('Product fetched successfully', $product);
    }

    public function view($slug) {
        $product = $this->product_model->get_by_slug($slug);
        
        if (!$product) {
            not_found('Product not found');
            return;
        }

        $product['variants'] = $this->product_model->get_variants($product['id']);
        $product['categoryId'] = $product['category_id'];
        $product['subcategoryId'] = $product['subcategory_id'];
        
        // Convert image paths to full URLs
        if (!empty($product['image'])) {
            $product['image'] = base_url($product['image']);
        }

        success_response('Product fetched successfully', $product);
    }

  

  
    public function get_all_admin() {
        $user = $this->get_current_user();
        if (!$user) {
            unauthorized('Authentication required');
            return;
        }

        $page = (int)$this->input->get('page') ?: 1;
        $limit = (int)$this->input->get('limit') ?: 10;
        $offset = ($page - 1) * $limit;
        $search = $this->input->get('search') ?: '';
        $category_id = $this->input->get('category_id');
        $is_active = $this->input->get('is_active');

        $products = $this->product_model->get_all($limit, $offset, $search, $category_id, $is_active);
        
        foreach ($products as &$product) {
            $product['variants'] = $this->product_model->get_variants($product['id']);
            $product['categoryId'] = $product['category_id'];
            $product['subcategoryId'] = $product['subcategory_id'];
            $product['shortDescription'] = '';
            
            // Convert image paths to full URLs for admin
            if (!empty($product['image'])) {
                $product['image'] = base_url($product['image']);
            }
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true) ?: [];
                $product['images'] = array_map(function($img) {
                    return base_url($img);
                }, $images);
            }
            
            // Convert variant images
            foreach ($product['variants'] as &$variant) {
                if (!empty($variant['image'])) {
                    $variant['image'] = base_url($variant['image']);
                }
            }
        }

        $total = $this->product_model->get_total($search, $category_id, $is_active);

        success_response('Products fetched successfully', [
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function get_admin($id) {
        $user = $this->get_current_user();
        if (!$user) {
            unauthorized('Authentication required');
            return;
        }

        $product = $this->product_model->get_by_id($id);
        
        if (!$product) {
            not_found('Product not found');
            return;
        }

        $product['variants'] = $this->product_model->get_variants($id);
        $product['categoryId'] = $product['category_id'];
        $product['subcategoryId'] = $product['subcategory_id'];
        
        // Convert image paths to full URLs
        if (!empty($product['image'])) {
            $product['image'] = base_url($product['image']);
        }
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true) ?: [];
            $product['images'] = array_map(function($img) {
                return base_url($img);
            }, $images);
        }
        
        foreach ($product['variants'] as &$variant) {
            if (!empty($variant['image'])) {
                $variant['image'] = base_url($variant['image']);
            }
        }

        success_response('Product fetched successfully', $product);
    }

    public function create() {
        $permission = $this->has_permission('2');
        if (!$permission['valid']) {
            if ($permission['message'] === 'Unauthorized') {
                unauthorized('Authentication required');
            } else {
                forbidden($permission['message']);
            }
            return;
        }

        $user = $permission['user'];
        $user_id = isset($user->id) ? $user->id : (isset($user->user_id) ? $user->user_id : null);

       $json_input = file_get_contents('php://input');
$json_data = json_decode($json_input, true);

$data = $json_data;


if (empty($data)) {
    $data = $_POST;
}

if (empty($data) || (!isset($data['name']) && isset($_POST['data']))) {
    $data = json_decode($_POST['data'], true) ?? [];
}
    

        $image_filename = null;
        
        // Handle image upload - only file upload is supported
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_filename = $this->upload_image('image', '');
        }

        // Handle multiple product images
      
        $product_data = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'category_id' => $data['categoryId'] ?? $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategoryId'] ?? $data['subcategory_id'] ?? null,
            'image' => $image_filename ?? '',
            'price' => $data['price'] ?? '',
            'quantity' => $data['quantity'] ?? '',
            'gst' => $data['gst'] ?? '',
            'weight' => $data['weight'] ?? '',
            'hsn_code' => $data['hsnCode'] ?? $data['hsn_code'] ?? '',
            'discount' => $data['discount'] ?? '',
            'is_active' => $data['isActive'] ?? $data['is_active'] ?? '1',
            'created_by' => $user_id
        ];

        $product_id = $this->product_model->create($product_data);

        if (!$product_id) {
            error_response('Failed to create product', 500);
            return;
        }

        // Handle variants
        if (!empty($data['variants']) && is_array($data['variants'])) {
           foreach ($data['variants'] as $index => $variant) {
                // Handle variant image upload
              $variant_image = null;


$variant_key = 'variant_image_' . $index;

if (isset($_FILES[$variant_key]) && $_FILES[$variant_key]['error'] === UPLOAD_ERR_OK) {
    $variant_image = $this->upload_image($variant_key, '');
}
                $variant_data = [
                    'product_id' => $product_id,
                    'sku' => $variant['sku'] ?? null,
                    'Attribute' => $variant['attribute'] ?? ($variant['attribute'] ?? null),
                    'Value' => $variant['value'] ?? null,
                    'price' => $variant['price'] ?? 0,
                    'discount' => $variant['discount'] ?? null,
                    'stock' => $variant['stock'] ?? 0,
                    'image' => $variant_image,
                    'is_active' => $variant['isActive'] ?? $variant['is_active'] ?? '1',
                    'created_by' => $user_id
                ];
                $this->product_model->create_variant($variant_data);
            }
        }

        $product = $this->product_model->get_by_id($product_id);
        $product['variants'] = $this->product_model->get_variants($product_id);
        $product['categoryId'] = $product['category_id'];
        $product['subcategoryId'] = $product['subcategory_id'];

        success_response('Product created successfully', $product, ['id' => $product_id]);
    }

    public function update($id) {
        $permission = $this->has_permission('2');
        if (!$permission['valid']) {
            if ($permission['message'] === 'Unauthorized') {
                unauthorized('Authentication required');
            } else {
                forbidden($permission['message']);
            }
            return;
        }

        $user = $permission['user'];
        $user_id = isset($user->id) ? $user->id : (isset($user->user_id) ? $user->user_id : null);

        $product = $this->product_model->get_by_id($id);
        if (!$product) {
            not_found('Product not found');
            return;
        }

$json_input = file_get_contents('php://input');
$json_data = json_decode($json_input, true);

$data = $json_data;


if (empty($data) && isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
}


if (empty($data)) {
    $data = $_POST;
}

        if (!empty($data['name']) && $data['name'] !== $product['name']) {
           
        }

        // Handle main product image upload
        $image_filename = $product['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Delete old image
           if (!empty($product['image']) && file_exists(FCPATH . $product['image'])) {
    unlink(FCPATH . $product['image']);
}
            $image_filename = $this->upload_image('image', '');
        }

   

        $product_data = [
            'name' => $data['name'] ?? $product['name'],
            'description' => $data['description'] ?? $product['description'],
            'category_id' => $data['categoryId'] ?? $data['category_id'] ?? $product['category_id'],
            'subcategory_id' => $data['subcategoryId'] ?? $data['subcategory_id'] ?? $product['subcategory_id'],
            'image' => $image_filename,
            'price' => $data['price'] ?? $product['price'],
            'quantity' => $data['quantity'] ?? $product['quantity'],
            'gst' => $data['gst'] ?? $product['gst'],
            'weight' => $data['weight'] ?? $product['weight'],
            'hsn_code' => $data['hsnCode'] ?? $data['hsn_code'] ?? $product['hsn_code'],
            'discount' => $data['discount'] ?? $product['discount'],
            'is_active' => $data['isActive'] ?? $data['is_active'] ?? $product['is_active'],
            'updated_by' => $user_id
        ];

        $result = $this->product_model->update($id, $product_data);

        if (!$result) {
            error_response('Failed to update product', 500);
            return;
        }

        if (!empty($data['variants']) && is_array($data['variants'])) {
            // Delete existing variants
            $old_variants = $this->product_model->get_variants($id);
            foreach ($old_variants as $ov) {
               if (!empty($ov['image']) && file_exists(FCPATH . $ov['image'])) {
    unlink(FCPATH . $ov['image']);
}
            }
            $this->product_model->delete_all_variants($id);
            
            foreach ($data['variants'] as $index => $variant) {
               $variant_image = $variant['existing_image'] ?? null;

$variant_key = 'variant_image_' . $index;

if (isset($_FILES[$variant_key]) && $_FILES[$variant_key]['error'] === UPLOAD_ERR_OK) {
    $variant_image = $this->upload_image($variant_key, '');
}
                $variant_data = [
                    'product_id' => $id,
                    'sku' => $variant['sku'] ?? null,
                  'Attribute' => $variant['attribute'] ?? ($variant['attribute'] ?? null),
                    'Value' => $variant['value'] ?? null,
                    'price' => $variant['price'] ?? 0,
                    'discount' => $variant['discount'] ?? null,
                    'stock' => $variant['stock'] ?? 0,
                    'image' => $variant_image,
                    'is_active' => $variant['isActive'] ?? $variant['is_active'] ?? '1',
                    'created_by' => $user_id,
                    'updated_by' => $user_id
                ];
                $this->product_model->create_variant($variant_data);
            }
        }

        $updated_product = $this->product_model->get_by_id($id);
        $updated_product['variants'] = $this->product_model->get_variants($id);
        $updated_product['categoryId'] = $updated_product['category_id'];
        $updated_product['subcategoryId'] = $updated_product['subcategory_id'];

        success_response('Product updated successfully', $updated_product);
    }

    public function delete($id) {
        $permission = $this->has_permission('2');
        if (!$permission['valid']) {
            if ($permission['message'] === 'Unauthorized') {
                unauthorized('Authentication required');
            } else {
                forbidden($permission['message']);
            }
            return;
        }

        $product = $this->product_model->get_by_id($id);
        if (!$product) {
            not_found('Product not found');
            return;
        }

        // Delete product images
       if (!empty($product['image']) && file_exists(FCPATH . $product['image'])) {
    unlink(FCPATH . $product['image']);
}
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true) ?: [];
            foreach ($images as $img) {
                if (file_exists($img)) {
                    unlink($img);
                }
            }
        }

        // Delete variant images
        $variants = $this->product_model->get_variants($id);
        foreach ($variants as $variant) {
            if (!empty($variant['image']) && file_exists($variant['image'])) {
                unlink($variant['image']);
            }
        }

        $result = $this->product_model->delete($id);

        if (!$result) {
            error_response('Failed to delete product', 500);
            return;
        }

        success_response('Product deleted successfully', ['id' => $id]);
    }

    public function toggle_status($id) {
        $permission = $this->has_permission('2');
        if (!$permission['valid']) {
            if ($permission['message'] === 'Unauthorized') {
                unauthorized('Authentication required');
            } else {
                forbidden($permission['message']);
            }
            return;
        }

        $result = $this->product_model->toggle_status($id);

        if (!$result) {
            not_found('Product not found');
            return;
        }

        success_response('Product status updated successfully', $result);
    }

}
