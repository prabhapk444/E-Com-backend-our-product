<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Orders extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('order_model');
        $this->load->model('jwt_model');
        $this->load->helper('response_helper');
        $this->load->library('email_library');
        $this->load->database();
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function get_current_user() {
        try {
            $token = $this->jwt_model->get_token_from_header();
            
            if (empty($token)) {
                log_message('info', 'get_current_user() - no token in header');
                return null;
            }
            
            $token = trim($token);
            $decoded = $this->jwt_model->decode($token);
            
            if (!$decoded) {
                log_message('error', 'get_current_user() - failed to decode token');
                return null;
            }
            
            // Convert to array for logging
            $decoded_arr = json_decode(json_encode($decoded), true);
            log_message('info', 'get_current_user() - decoded: ' . json_encode($decoded_arr));
            
            return $decoded;
        } catch (Exception $e) {
            log_message('error', 'get_current_user() exception: ' . $e->getMessage());
            return null;
        }
    }

    private function has_permission($required_role = null) {
        $user = $this->get_current_user();
        
        if (!$user) {
            return ['valid' => false, 'user' => null, 'message' => 'Unauthorized'];
        }

        if ($required_role !== null) {
            $user_role = isset($user->role) ? $user->role : (isset($user->role_id) ? $user->role_id : null);
            $user_role = $user_role !== null ? (string)$user_role : null;

            if ($user_role !== $required_role) {
                return ['valid' => false, 'user' => $user, 'message' => 'Forbidden: You do not have permission'];
            }
        }

        return ['valid' => true, 'user' => $user];
    }

    // Send order notification email to admin
    private function send_admin_notification($order, $items) {
        // Build items HTML
        $items_html = '';
        foreach ($items as $item) {
            $variant = !empty($item['variant_name']) ? $item['variant_name'] : '-';
            $items_html .= "
            <tr>
                <td style=\"padding: 10px; border-bottom: 1px solid #eee;\">{$item['product_name']}</td>
                <td style=\"padding: 10px; border-bottom: 1px solid #eee;\">{$variant}</td>
                <td style=\"padding: 10px; border-bottom: 1px solid #eee; text-align: center;\">{$item['quantity']}</td>
                <td style=\"padding: 10px; border-bottom: 1px solid #eee; text-align: right;\">₹" . number_format($item['price'], 2) . "</td>
                <td style=\"padding: 10px; border-bottom: 1px solid #eee; text-align: right;\">₹" . number_format($item['price'] * $item['quantity'], 2) . "</td>
            </tr>";
        }

        $subject = 'New Order Received - ' . $order['order_id'];
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
                .order-info { background: #f9fafb; padding: 20px; margin: 20px 0; border-radius: 8px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background: #4f46e5; color: white; padding: 12px; text-align: left; }
                .total-row { font-weight: bold; background: #f3f4f6; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h1 style=\"margin: 0;\">New Order Received!</h1>
                    <p style=\"margin: 5px 0 0 0;\">Order ID: <strong>{$order['order_id']}</strong></p>
                </div>
                
                <div class=\"order-info\">
                    <h3 style=\"margin-top: 0;\">Customer Details</h3>
                    <p><strong>Name:</strong> {$order['name']}</p>
                    <p><strong>Email:</strong> {$order['email']}</p>
                    <p><strong>Phone:</strong> {$order['phone']}</p>
                    <p><strong>Address:</strong> {$order['address']}, {$order['city']}, {$order['state']} - {$order['pincode']}</p>
                </div>
                
                <h3>Order Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variant</th>
                            <th style=\"text-align: center;\">Qty</th>
                            <th style=\"text-align: right;\">Price</th>
                            <th style=\"text-align: right;\">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                        <tr class=\"total-row\">
                            <td colspan=\"4\" style=\"padding: 10px; text-align: right;\">Subtotal:</td>
                            <td style=\"padding: 10px; text-align: right;\">₹" . number_format($order['subtotal'], 2) . "</td>
                        </tr>
                        <tr>
                            <td colspan=\"4\" style=\"padding: 10px; text-align: right;\">CGST:</td>
                            <td style=\"padding: 10px; text-align: right;\">₹" . number_format($order['cgst'], 2) . "</td>
                        </tr>
                        <tr>
                            <td colspan=\"4\" style=\"padding: 10px; text-align: right;\">SGST:</td>
                            <td style=\"padding: 10px; text-align: right;\">₹" . number_format($order['sgst'], 2) . "</td>
                        </tr>
                        <tr class=\"total-row\">
                            <td colspan=\"4\" style=\"padding: 10px; text-align: right;\">Total:</td>
                            <td style=\"padding: 10px; text-align: right; font-size: 18px; color: #4f46e5;\">₹" . number_format($order['total'], 2) . "</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class=\"order-info\">
                    <p><strong>Payment Method:</strong> " . ucfirst($order['payment_method']) . "</p>
                    <p><strong>Payment Status:</strong> " . ucfirst($order['payment_status']) . "</p>
                </div>
                
                <div class=\"footer\">
                    <p>This is an automated notification from your E-com store.</p>
                </div>
            </div>
        </body>
        </html>";

        // Send email to admin
        $result = $this->email_library->send(ADMIN_EMAIL, $subject, $body);
        
        // Log the result (but don't fail the order if email fails)
        if (!$result['status']) {
            log_message('error', 'Admin order notification email failed: ' . $result['message']);
        }
        
        return $result;
    }

    // ============================================
    // PUBLIC ENDPOINTS
    // ============================================

    // Create new order (public - no token required)
    public function create() {
        // Debug: Log that we reached this function
        log_message('info', 'Orders::create() called');
        
        // Get JSON input
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        log_message('info', 'Orders::create() input data: ' . print_r($data, true));

        if (!$data) {
            // Try form input
            $data = $_POST;
        }

        // Support both formats: direct fields or customer wrapper
        $customer = isset($data['customer']) ? $data['customer'] : $data;
        
        // Extract customer fields (support both formats)
        $name = isset($customer['name']) ? $customer['name'] : (isset($data['name']) ? $data['name'] : '');
        $email = isset($customer['email']) ? $customer['email'] : (isset($data['email']) ? $data['email'] : '');
        $phone = isset($customer['phone']) ? $customer['phone'] : (isset($data['phone']) ? $data['phone'] : '');
        $address = isset($customer['address']) ? $customer['address'] : (isset($data['address']) ? $data['address'] : '');
        $city = isset($customer['city']) ? $customer['city'] : (isset($data['city']) ? $data['city'] : '');
        $state = isset($customer['state']) ? $customer['state'] : (isset($data['state']) ? $data['state'] : '');
        $pincode = isset($customer['pincode']) ? $customer['pincode'] : (isset($data['pincode']) ? $data['pincode'] : '');

        // Validate required fields
        $required_fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'items'];
        $field_values = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'items' => isset($data['items']) ? $data['items'] : null
        ];
        
        foreach ($required_fields as $field) {
            if (empty($field_values[$field])) {
                send_error("Missing required field: $field", 400);
                return;
            }
        }

        // Validate items
        if (!is_array($data['items']) || count($data['items']) === 0) {
            send_error("Order must have at least one item", 400);
            return;
        }

        // No user_id - guest checkout
        $user_id = null;

        // Calculate totals (use frontend values or calculate)
        $subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0;
        $items_data = [];
        
        foreach ($data['items'] as $item) {
            // Support both frontend formats: productId/productName and product_id/product_name
            $product_id = isset($item['productId']) ? $item['productId'] : (isset($item['product_id']) ? $item['product_id'] : null);
            $product_name = isset($item['productName']) ? $item['productName'] : (isset($item['product_name']) ? $item['product_name'] : 'Product');
            $variant_id = isset($item['variantId']) ? $item['variantId'] : (isset($item['variant_id']) ? $item['variant_id'] : null);
            $variant_name = isset($item['variantName']) ? $item['variantName'] : (isset($item['variant_name']) ? $item['variant_name'] : '');
            
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $item_total = $price * $quantity;
           

            $items_data[] = [
                'product_id' => $product_id,
                'product_name' => $product_name,
                'variant_id' => $variant_id,
                'variant_name' => $variant_name,
                'quantity' => $quantity,
                'price' => $price
            ];
        }

        // Calculate tax (use frontend values or default to 0)
        $cgst = isset($data['cgst']) ? floatval($data['cgst']) : 0;
        $sgst = isset($data['sgst']) ? floatval($data['sgst']) : 0;
        $total = isset($data['total']) ? floatval($data['total']) : ($subtotal + $cgst + $sgst);

        // Payment method and status
        $payment_method = isset($data['paymentMethod']) ? $data['paymentMethod'] : 'cod';
        $payment_status = isset($data['paymentStatus']) ? $data['paymentStatus'] : ($payment_method === 'online' ? 'pending' : 'cod');
        $payment_id = isset($data['paymentId']) ? $data['paymentId'] : null;

        // Prepare order data (without user_id - not in original table schema)
        $order_data = [
            'order_id' => $this->order_model->generate_order_id(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'subtotal' => $subtotal,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'total' => $total,
            'payment_method' => $payment_method,
            'payment_id' => $payment_id,
            'payment_status' => $payment_status,
            'status' => 'pending'
        ];

        // Create order
        $order_id = $this->order_model->create_order($order_data, $items_data);

        if ($order_id) {
            $order = $this->order_model->get_by_id($order_id);
            $items = $this->order_model->get_items($order_id);
            
            // Send email notification to admin
            $this->send_admin_notification($order, $items);
            
            send_success($order, 'Order created successfully', 201);
        } else {
            send_error('Failed to create order', 500);
        }
    }

    // Get order by ID (for customers - no token required for public check)
    public function get($id) {
        $order = $this->order_model->get_by_id($id);
        
        if (!$order) {
            send_error('Order not found', 404);
            return;
        }

        // Get order items
        $items = $this->order_model->get_items($id);
        
        $order['items'] = $items;
        
        send_success($order, 'Order retrieved successfully');
    }

    // Get order by order_id string
    public function view($order_id) {
        $order = $this->order_model->get_by_order_id($order_id);
        
        if (!$order) {
            send_error('Order not found', 404);
            return;
        }

        // Get order items
        $items = $this->order_model->get_items($order['id']);
        
        $order['items'] = $items;
        
        send_success($order, 'Order retrieved successfully');
    }

    // Get current user's orders (by email - no user_id in table)
    public function my_orders() {
        try {
            $user = $this->get_current_user();
            
            if (!$user) {
                send_error('Unauthorized', 401);
                return;
            }

            $email = isset($user->email) ? $user->email : null;
            
            if (!$email) {
                send_error('Invalid user', 400);
                return;
            }

            $limit = $this->input->get('limit') ?? 10;
            $offset = $this->input->get('offset') ?? 0;

            $orders = $this->order_model->get_by_email($email, $limit, $offset);
            
            // Get items for each order
            foreach ($orders as &$order) {
                $order['items'] = $this->order_model->get_items($order['id']);
            }

            $total = $this->order_model->get_total_by_email($email);

            send_success([
                'orders' => $orders,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ], 'Orders retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'my_orders() exception: ' . $e->getMessage());
            send_error('Server error: ' . $e->getMessage(), 500);
        }
    }

    // ============================================
    // ADMIN ENDPOINTS (Role 2 - Admin)
    // ============================================

    // Get all orders (admin - role 1, 2, or 3)
    public function index() {
        $permission = $this->has_permission('1');
        
        // If not super admin, check for admin role
        if (!$permission['valid']) {
            $permission = $this->has_permission('2');
            // If not admin, check for manager/vendor role
            if (!$permission['valid']) {
                $permission = $this->has_permission('3');
                if (!$permission['valid']) {
                    send_error($permission['message'], 403);
                    return;
                }
            }
        }

        $limit = $this->input->get('limit') ?? 10;
        $offset = $this->input->get('offset') ?? 0;
        $status = $this->input->get('status') ?? null;

        $orders = $this->order_model->get_all($limit, $offset, $status);
        
        // Get items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->order_model->get_items($order['id']);
        }

        $total = $this->order_model->get_total($status);

        send_success([
            'orders' => $orders,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ], 'Orders retrieved successfully');
    }

    // Get recent orders (admin - role 1, 2, or 3)
    public function recent() {
        $permission = $this->has_permission('1');
        
        // If not super admin, check for admin role
        if (!$permission['valid']) {
            $permission = $this->has_permission('2');
            // If not admin, check for manager/vendor role
            if (!$permission['valid']) {
                $permission = $this->has_permission('3');
                if (!$permission['valid']) {
                    send_error($permission['message'], 403);
                    return;
                }
            }
        }

        $limit = $this->input->get('limit') ?? 10;
        
        $orders = $this->order_model->get_recent($limit);
        
        // Get items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->order_model->get_items($order['id']);
        }

        send_success($orders, 'Recent orders retrieved successfully');
    }

    // Update order status (admin - role 1, 2, or 3)
    public function update_status($id) {
        $permission = $this->has_permission('1');
        
        // If not super admin, check for admin role
        if (!$permission['valid']) {
            $permission = $this->has_permission('2');
            // If not admin, check for manager/vendor role
            if (!$permission['valid']) {
                $permission = $this->has_permission('3');
                if (!$permission['valid']) {
                    send_error($permission['message'], 403);
                    return;
                }
            }
        }

        // Get JSON input
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            $data = $_POST;
        }

        $status = isset($data['status']) ? $data['status'] : null;

        if (!$status) {
            send_error('Status is required', 400);
            return;
        }

        // Validate status
        $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $valid_statuses)) {
            send_error('Invalid status', 400);
            return;
        }

        $user = $permission['user'];
        $updated_by = isset($user->id) ? $user->id : null;

        $result = $this->order_model->update_status($id, $status, $updated_by);

        if ($result) {
            $order = $this->order_model->get_by_id($id);
            send_success($order, 'Order status updated successfully');
        } else {
            send_error('Failed to update order status', 500);
        }
    }

    // Update payment status
    public function update_payment($id) {
        // This can be called by payment gateway webhook
        
        // Get JSON input
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            $data = $_POST;
        }

        $payment_status = isset($data['payment_status']) ? $data['payment_status'] : null;
        $payment_id = isset($data['payment_id']) ? $data['payment_id'] : null;

        if (!$payment_status) {
            send_error('Payment status is required', 400);
            return;
        }

        // Validate payment status
        $valid_statuses = ['pending', 'paid', 'failed', 'cod'];
        
        if (!in_array($payment_status, $valid_statuses)) {
            send_error('Invalid payment status', 400);
            return;
        }

        $result = $this->order_model->update_payment_status($id, $payment_status, $payment_id);

        if ($result) {
            $order = $this->order_model->get_by_id($id);
            send_success($order, 'Payment status updated successfully');
        } else {
            send_error('Failed to update payment status', 500);
        }
    }

    // Admin - Get order by ID
    public function get_admin($id) {
        $permission = $this->has_permission('1');
        
        // If not super admin, check for admin role
        if (!$permission['valid']) {
            $permission = $this->has_permission('2');
            // If not admin, check for manager/vendor role
            if (!$permission['valid']) {
                $permission = $this->has_permission('3');
                if (!$permission['valid']) {
                    send_error($permission['message'], 403);
                    return;
                }
            }
        }

        $order = $this->order_model->get_by_id($id);
        
        if (!$order) {
            send_error('Order not found', 404);
            return;
        }

        // Get order items
        $items = $this->order_model->get_items($id);
        
        $order['items'] = $items;
        
        send_success($order, 'Order retrieved successfully');
    }

    // Cancel order (by email - no user_id in table)
    public function cancel($id) {
        $user = $this->get_current_user();
        
        // Check if user is authenticated
        if (!$user) {
            send_error('Unauthorized', 401);
            return;
        }
        
        // Check if order exists
        $order = $this->order_model->get_by_id($id);
        
        if (!$order) {
            send_error('Order not found', 404);
            return;
        }

        // Get user info
        $user_email = isset($user->email) ? $user->email : null;
        $user_role = isset($user->role) ? $user->role : (isset($user->role_id) ? $user->role_id : null);
        
        // Allow if user is owner (by email) OR admin (role 1 or 2)
        $is_admin = ($user_role === '1' || $user_role === '2');
        $is_owner = ($user_email && isset($order['email']) && strtolower($order['email']) === strtolower($user_email));
        
        if (!$is_admin && !$is_owner) {
            send_error('Unauthorized to cancel this order', 403);
            return;
        }

        // Check if order can be cancelled
        if (in_array($order['status'], ['shipped', 'delivered', 'cancelled'])) {
            send_error('Order cannot be cancelled in current status', 400);
            return;
        }

        $result = $this->order_model->cancel_order($id);

        if ($result) {
            $order = $this->order_model->get_by_id($id);
            send_success($order, 'Order cancelled successfully');
        } else {
            send_error('Failed to cancel order', 500);
        }
    }

    // Get sales statistics (admin - role 1 or 2)
    public function stats() {
        $permission = $this->has_permission('1');
        
        // If not super admin, check for admin role
        if (!$permission['valid']) {
            $permission = $this->has_permission('2');
            if (!$permission['valid']) {
                send_error($permission['message'], 403);
                return;
            }
        }

        $start_date = $this->input->get('start_date') ?? null;
        $end_date = $this->input->get('end_date') ?? null;

        $stats = $this->order_model->get_sales_stats($start_date, $end_date);

        send_success($stats, 'Sales statistics retrieved successfully');
    }

    // Get orders by status (admin - role 1 or 2)
    public function by_status($status) {
        $permission = $this->has_permission('1');
        
        // If not super admin, check for admin role
        if (!$permission['valid']) {
            $permission = $this->has_permission('2');
            if (!$permission['valid']) {
                send_error($permission['message'], 403);
                return;
            }
        }

        $limit = $this->input->get('limit') ?? 10;
        $offset = $this->input->get('offset') ?? 0;

        $orders = $this->order_model->get_by_status($status, $limit, $offset);
        
        // Get items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->order_model->get_items($order['id']);
        }

        send_success($orders, 'Orders retrieved successfully');
    }
}
