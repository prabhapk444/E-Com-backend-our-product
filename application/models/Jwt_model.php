<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php'; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Jwt_model extends CI_Model {
    private $secret_key;
    private $algorithm;
    private $expiration;
    
    public function __construct() {
        parent::__construct();
        $this->secret_key  = JWT_SECRET;
        $this->algorithm   = JWT_ALGORITHM;
        $this->expiration  = JWT_EXPIRATION;
    }

    // Encode token
    public function encode($payload) {
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiration;
        return JWT::encode($payload, $this->secret_key, $this->algorithm);
    }

    // Decode token
    public function decode($token) {
    try {
        $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
        log_message('info', 'Jwt_model::decode() - success');
        return $decoded;
    } catch (Exception $e) {
        log_message('error', 'JWT Decode Error: ' . $e->getMessage());
        return null;
    }
}



    // Verify token
   public function verify_token($token = null) {
    if (empty($token)) {
        $token = $this->get_token_from_header();
    }

    if (empty($token)) {
        log_message('error', 'JWT Verify Error: Missing token');
        return false;
    }

    $token = trim($token); 
    return $this->decode($token);
}

public function get_token_from_header() {
    // Try CI method
    $headers = $this->input->request_headers();
    
    log_message('info', 'Jwt_model get_token_from_header - CI headers: ' . json_encode($headers));

    if (!$headers) {
        // fallback to getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            log_message('info', 'Jwt_model get_token_from_header - getallheaders: ' . json_encode($headers));
        } else {
            $headers = [];
        }
    }

    foreach ($headers as $key => $value) {
        log_message('info', 'Jwt_model get_token_from_header - header: ' . $key . ' = ' . $value);
        if (strtolower($key) === 'authorization') {
            if (preg_match('/Bearer\s(\S+)/', $value, $matches)) {
                return $matches[1];
            }
        }
    }


    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        log_message('info', 'Jwt_model get_token_from_header - SERVER HTTP_AUTHORIZATION: ' . $_SERVER['HTTP_AUTHORIZATION']);
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }

    return null;
}









   public function generate_refresh_token($user_id) {
    $payload = [
        'uid'  => $user_id,
        'type' => 'refresh',
        'iat'  => time(),
        'exp'  => time() + JWT_EXPIRATION   
    ];

    return JWT::encode($payload, $this->secret_key, $this->algorithm);
}



    
    public function get_authenticated_user() {
    $token = $this->get_token_from_header();
    if (!$token) {
        return false;
    }
    return $this->decode($token);
}


}
