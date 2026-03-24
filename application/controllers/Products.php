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
        $this->load->model('product_model');
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

    private function generate_slug($name, $exclude_id = null) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $name)));
        
        $original_slug = $slug;
        $counter = 1;
        
        while ($this->product_model->slug_exists($slug, $exclude_id)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Upload single image
    private function upload_image($field_name, $subfolder = '') {
        $upload_path = $this->upload_path . $subfolder;
        
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = implode('|', $this->allowed_types);
        $config['max_size'] = 2048; // 2MB
        $config['encrypt_name'] = TRUE;
        $config['remove_spaces'] = TRUE;

        $this->load->library('upload', $config);

        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
            if ($this->upload->do_upload($field_name)) {
                $data = $this->upload->data();
                return $subfolder . $data['file_name'];
            } else {
                log_message('error', 'Upload Error: ' . $this->upload->display_errors());
                return null;
            }
        }
        
        return null;
    }

    // Upload multiple images
    private function upload_images($field_name, $subfolder = '') {
        $upload_path = $this->upload_path . $subfolder;
        
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = implode('|', $this->allowed_types);
        $config['max_size'] = 2048;
        $config['encrypt_name'] = TRUE;
        $config['remove_spaces'] = TRUE;

        $this->load->library('upload', $config);

        $uploaded_files = [];
        
        if (isset($_FILES[$field_name])) {
            $files = $_FILES[$field_name];
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $_FILES['temp_file']['name'] = $files['name'][$i];
                    $_FILES['temp_file']['type'] = $files['type'][$i];
                    $_FILES['temp_file']['tmp_name'] = $files['tmp_name'][$i];
                    $_FILES['temp_file']['error'] = $files['error'][$i];
                    $_FILES['temp_file']['size'] = $files['size'][$i];

                    if ($this->upload->do_upload('temp_file')) {
                        $data = $this->upload->data();
                        $uploaded_files[] = $subfolder . $data['file_name'];
                    }
                }
            }
        }
        
        return $uploaded_files;
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
                $product['image'] = base_url($this->upload_path . $product['image']);
            }
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true) ?: [];
                $product['images'] = array_map(function($img) {
                    return base_url($this->upload_path . $img);
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
            $product['image'] = base_url($this->upload_path . $product['image']);
        }
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true) ?: [];
            $product['images'] = array_map(function($img) {
                return base_url($this->upload_path . $img);
            }, $images);
        }
        
        // Convert variant images
        foreach ($product['variants'] as &$variant) {
            if (!empty($variant['image'])) {
                $variant['image'] = base_url($this->upload_path . $variant['image']);
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
            $product['image'] = base_url($this->upload_path . $product['image']);
        }

        success_response('Product fetched successfully', $product);
    }

    public function featured() {
        $limit = (int)$this->input->get('limit') ?: 10;
        
        $products = $this->product_model->get_featured($limit);
        
        foreach ($products as &$product) {
            $product['variants'] = $this->product_model->get_variants($product['id']);
            $product['categoryId'] = $product['category_id'];
            $product['subcategoryId'] = $product['subcategory_id'];
            
            if (!empty($product['image'])) {
                $product['image'] = base_url($this->upload_path . $product['image']);
            }
        }

        success_response('Featured products fetched successfully', $products);
    }

    // ============================================
    // ADMIN METHODS
    // ============================================

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
                $product['image'] = base_url($this->upload_path . $product['image']);
            }
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true) ?: [];
                $product['images'] = array_map(function($img) {
                    return base_url($this->upload_path . $img);
                }, $images);
            }
            
            // Convert variant images
            foreach ($product['variants'] as &$variant) {
                if (!empty($variant['image'])) {
                    $variant['image'] = base_url($this->upload_path . $variant['image']);
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
        $product['shortDescription'] = $product['short_description'] ?? '';
        
        // Convert image paths to full URLs
        if (!empty($product['image'])) {
            $product['image'] = base_url($this->upload_path . $product['image']);
        }
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true) ?: [];
            $product['images'] = array_map(function($img) {
                return base_url($this->upload_path . $img);
            }, $images);
        }
        
        foreach ($product['variants'] as &$variant) {
            if (!empty($variant['image'])) {
                $variant['image'] = base_url($this->upload_path . $variant['image']);
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

        // Handle JSON input
        $json_data = json_decode(file_get_contents('php://input'), true);
        
        $data = !empty($json_data) ? $json_data : $_POST;

        if (empty($data['name'])) {
            bad_request('Product name is required');
            return;
        }

        $slug = !empty($data['slug']) ? $data['slug'] : $this->generate_slug($data['name']);

        // Handle main product image upload
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_filename = $this->upload_image('image', 'products/');
        }

        // Handle multiple product images
        $images_filenames = [];
        if (isset($_FILES['images'])) {
            $images_filenames = $this->upload_images('images', 'products/gallery/');
        }

        $product_data = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'short_description' => $data['shortDescription'] ?? $data['short_description'] ?? '',
            'category_id' => $data['categoryId'] ?? $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategoryId'] ?? $data['subcategory_id'] ?? null,
            'image' => $image_filename ?? $data['image'] ?? '',
            'images' => !empty($images_filenames) ? json_encode($images_filenames) : (isset($data['images']) ? $data['images'] : ''),
            'featured' => isset($data['featured']) ? (int)$data['featured'] : 0,
            'gst' => $data['gst'] ?? '',
            'weight' => $data['weight'] ?? '',
            'hsn_code' => $data['hsnCode'] ?? $data['hsn_code'] ?? '',
            'note' => $data['note'] ?? '',
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
            foreach ($data['variants'] as $variant) {
                // Handle variant image upload
                $variant_image = null;
                if (isset($variant['image_file']) && !empty($variant['image_file'])) {
                    // Base64 image - save to file
                    $variant_image = $this->save_base64_image($variant['image_file'], 'products/variants/');
                } elseif (isset($variant['image']) && strpos($variant['image'], 'uploads/') === false) {
                    // Existing image path or URL
                    $variant_image = $variant['image'];
                }

                $variant_data = [
                    'product_id' => $product_id,
                    'sku' => $variant['sku'] ?? null,
                    'size' => $variant['size'] ?? ($variant['attribute'] ?? null),
                    'color' => $variant['color'] ?? null,
                    'price' => $variant['price'] ?? 0,
                    'compare_at_price' => $variant['compareAtPrice'] ?? $variant['compare_at_price'] ?? null,
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

        $json_data = json_decode(file_get_contents('php://input'), true);
        $data = !empty($json_data) ? $json_data : $_POST;

        if (!empty($data['name']) && $data['name'] !== $product['name']) {
            $data['slug'] = $this->generate_slug($data['name'], $id);
        }

        // Handle main product image upload
        $image_filename = $product['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Delete old image
            if (!empty($product['image']) && file_exists($this->upload_path . $product['image'])) {
                unlink($this->upload_path . $product['image']);
            }
            $image_filename = $this->upload_image('image', 'products/');
        }

        // Handle multiple product images
        $images_filenames = json_decode($product['images'], true) ?: [];
        if (isset($_FILES['images'])) {
            $new_images = $this->upload_images('images', 'products/gallery/');
            $images_filenames = array_merge($images_filenames, $new_images);
        }

        $product_data = [
            'name' => $data['name'] ?? $product['name'],
            'slug' => $data['slug'] ?? $product['slug'],
            'description' => $data['description'] ?? $product['description'],
            'short_description' => $data['shortDescription'] ?? $data['short_description'] ?? $product['short_description'],
            'category_id' => $data['categoryId'] ?? $data['category_id'] ?? $product['category_id'],
            'subcategory_id' => $data['subcategoryId'] ?? $data['subcategory_id'] ?? $product['subcategory_id'],
            'image' => $image_filename,
            'images' => !empty($images_filenames) ? json_encode($images_filenames) : $product['images'],
            'featured' => isset($data['featured']) ? (int)$data['featured'] : (int)$product['featured'],
            'gst' => $data['gst'] ?? $product['gst'],
            'weight' => $data['weight'] ?? $product['weight'],
            'hsn_code' => $data['hsnCode'] ?? $data['hsn_code'] ?? $product['hsn_code'],
            'note' => $data['note'] ?? $product['note'],
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
                if (!empty($ov['image']) && file_exists($this->upload_path . $ov['image'])) {
                    unlink($this->upload_path . $ov['image']);
                }
            }
            $this->product_model->delete_all_variants($id);
            
            foreach ($data['variants'] as $variant) {
                $variant_image = null;
                if (isset($variant['image_file']) && !empty($variant['image_file'])) {
                    $variant_image = $this->save_base64_image($variant['image_file'], 'products/variants/');
                } elseif (isset($variant['image']) && strpos($variant['image'], 'uploads/') === false) {
                    $variant_image = $variant['image'];
                }

                $variant_data = [
                    'product_id' => $id,
                    'sku' => $variant['sku'] ?? null,
                    'size' => $variant['size'] ?? ($variant['attribute'] ?? null),
                    'color' => $variant['color'] ?? null,
                    'price' => $variant['price'] ?? 0,
                    'compare_at_price' => $variant['compareAtPrice'] ?? $variant['compare_at_price'] ?? null,
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
        if (!empty($product['image']) && file_exists($this->upload_path . $product['image'])) {
            unlink($this->upload_path . $product['image']);
        }
        
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true) ?: [];
            foreach ($images as $img) {
                if (file_exists($this->upload_path . $img)) {
                    unlink($this->upload_path . $img);
                }
            }
        }

        // Delete variant images
        $variants = $this->product_model->get_variants($id);
        foreach ($variants as $variant) {
            if (!empty($variant['image']) && file_exists($this->upload_path . $variant['image'])) {
                unlink($this->upload_path . $variant['image']);
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

    // Save base64 image to file
    private function save_base64_image($base64_string, $subfolder = '') {
        if (empty($base64_string)) {
            return null;
        }

        $upload_path = $this->upload_path . $subfolder;
        
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        // Remove data URL prefix if present
        if (strpos($base64_string, 'data:image/') === 0) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
        }

        $image_data = base64_decode($base64_string);
        
        // Get image extension
        $finfo = finfo_open();
        $mime_type = finfo_buffer($finfo, $image_data, FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        $extension = 'jpg';
        if (strpos($mime_type, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($mime_type, 'gif') !== false) {
            $extension = 'gif';
        } elseif (strpos($mime_type, 'webp') !== false) {
            $extension = 'webp';
        }

        $filename = uniqid() . '.' . $extension;
        $filepath = $upload_path . $filename;

        if (file_put_contents($filepath, $image_data)) {
            return $subfolder . $filename;
        }

        return null;
    }
}
