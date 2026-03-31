<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


$route['api/register'] = 'auth/register';
$route['api/login'] = 'auth/login';
$route['api/forgot-password'] = 'auth/forgot_password';
$route['api/reset-password'] = 'auth/reset_password';
$route['api/update-profile'] = 'auth/update_profile';
$route['api/superadmin/login'] = 'auth/superadmin_login';
$route['auth/admin_login'] = 'auth/admin_login'; 
$route['api/google-register'] = 'auth/google_register';
$route['api/google-login'] = 'auth/google_login';


$route['auth/get_admins']             = 'auth/get_admins';
$route['auth/save_admin']             = 'auth/save_admin';
$route['auth/delete_admin/(:num)']   = 'auth/delete_admin/$1';
$route['auth/toggle_admin_status/(:num)'] = 'auth/toggle_admin_status/$1';
$route['api/user/toggle/(:num)'] = 'auth/toggle_user_status/$1';
$route['superadmin/admins'] = 'superadmin/get_admins';
$route['superadmin/stats'] = 'superadmin/get_stats';
$route['api/users'] = 'auth/get_users';




$route['api/feedback/submit'] = 'feedback/submit';
$route['api/feedbacks'] = 'feedback/get_all';
$route['api/feedback/update/(:num)'] = 'feedback/update/$1';
$route['api/feedback/toggle/(:num)'] = 'feedback/toggle_status/$1';
$route['api/feedback/enabled'] = 'feedback/get_enabled';
$route['feedback/delete/(:num)'] = 'feedback/delete/$1';




$route['category/get_all'] = 'category/get_all';
$route['category/create'] = 'category/create';
$route['category/update/(:num)'] = 'category/update/$1';
$route['category/delete/(:num)'] = 'category/delete/$1';
$route['category/toggle_status/(:num)'] = 'category/toggle_status/$1';


$route['subcategories'] = 'subcategory/index';
$route['subcategories'] = 'subcategory/get_all';
$route['subcategories/(:num)'] = 'subcategory/view/$1';
$route['subcategories/create'] = 'subcategory/create';
$route['subcategories/update/(:num)'] = 'subcategory/update/$1';
$route['subcategories/delete/(:num)'] = 'subcategory/delete/$1';
$route['subcategories/toggle/(:num)'] = 'subcategory/toggle/$1';





// Public endpoints (no auth required)
$route['products'] = 'products/index';
$route['products/index'] = 'products/index';
$route['products/get/(:num)'] = 'products/get/$1';
$route['products/view/(:any)'] = 'products/view/$1';
$route['products/featured'] = 'products/featured';

// Admin endpoints (auth required)
$route['products/admin'] = 'products/get_all_admin';
$route['products/create'] = 'products/create';
$route['products/update/(:num)'] = 'products/update/$1';
$route['products/delete/(:num)'] = 'products/delete/$1';
$route['products/toggle/(:num)'] = 'products/toggle_status/$1';
$route['products/admin/get/(:num)'] = 'products/get_admin/$1';


// Variant endpoints - Role 2 required
$route['products/variant/create'] = 'products/create_variant';
$route['products/variant/update/(:num)'] = 'products/update_variant/$1';
$route['products/variant/delete/(:num)'] = 'products/delete_variant/$1';




// Order endpoints
// Public - Create order (no token required)
// Supports both /orders and /api/orders for frontend compatibility
$route['orders'] = 'orders/create';
$route['api/orders'] = 'orders/create';
$route['api/orders/create'] = 'orders/create';

// Public - Get order by ID
$route['api/orders/get/(:num)'] = 'orders/get/$1';

// Public - Get order by order_id
$route['api/orders/view/(:any)'] = 'orders/view/$1';

// Customer - Get my orders (auth required)
$route['api/orders/my'] = 'orders/my_orders';

// Customer - Cancel order (auth required)
$route['api/orders/cancel/(:num)'] = 'orders/cancel/$1';

// Admin - Get all orders
$route['api/admin/orders'] = 'orders/index';
$route['api/admin/orders/index'] = 'orders/index';

// Admin - Get recent orders
$route['api/admin/orders/recent'] = 'orders/recent';

// Admin - Update order status
$route['api/admin/orders/status/(:num)'] = 'orders/update_status/$1';

// Admin/Payment - Update payment status
$route['api/admin/orders/payment/(:num)'] = 'orders/update_payment/$1';

// Admin - Get orders by status
$route['api/admin/orders/status/(:any)'] = 'orders/by_status/$1';

// Admin - Get specific order by ID
$route['api/admin/orders/(:num)'] = 'orders/get_admin/$1';

// Admin - Get sales stats
$route['api/admin/orders/stats'] = 'orders/stats';
