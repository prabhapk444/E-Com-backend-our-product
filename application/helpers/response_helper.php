<?php
defined('BASEPATH') OR exit('No direct script access allowed');


if (!function_exists('send_success')) {
    function send_success($data = [], $message = 'Success', $code = 200) {
        $CI = &get_instance();
        $CI->output
            ->set_content_type('application/json')
            ->set_status_header($code)
            ->set_output(json_encode([
                'status'  => true,
                'message' => $message,
                'data'    => $data
            ]));
    }
}

function success_response($message = 'Success', $data = [], $extra = []) {
    $response = array_merge([
        'status' => true,
        'message' => $message,
        'data' => $data
    ], $extra);

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

function error_response($message = 'Error', $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false,
        'message' => $message
    ]);
    exit;
}

if (!function_exists('send_error')) {
    function send_error($message = 'Error', $code = 400, $data = []) {
        $CI = &get_instance();
        $CI->output
            ->set_content_type('application/json')
            ->set_status_header($code)
            ->set_output(json_encode([
                'status'  => false,
                'message' => $message,
                'data'    => $data
            ]));
    }
}



function bad_request($msg) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => $msg]);
    exit;
}
function unauthorized($msg) {
    echo json_encode(['status' => false, 'message' => $msg]);
    http_response_code(401);
    exit;
}
function forbidden($msg) {
    echo json_encode(['status' => false, 'message' => $msg]);
    http_response_code(403);
    exit;
}
function conflict($msg) {
    echo json_encode(['status' => false, 'message' => $msg]);
    http_response_code(409);
    exit;
}

if (!function_exists('not_found')) {
    function not_found($message = 'Not Found') {
        $CI =& get_instance();
        $CI->output
            ->set_content_type('application/json')
            ->set_status_header(404)
            ->set_output(json_encode(['status' => false, 'message' => $message]))
            ->_display();
        exit;
    }
}

function ok($data = []) {
    http_response_code(200);
    echo json_encode($data);
    exit;
}


if (!function_exists('json_response')) {
    function json_response($data = [], $status_code = 200)
    {
        $CI =& get_instance();
        $CI->output
            ->set_content_type('application/json')
            ->set_status_header($status_code)
            ->set_output(json_encode($data))
            ->_display();
        exit;
    }
}



