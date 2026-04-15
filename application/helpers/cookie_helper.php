<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('set_secure_cookie')) {
    function set_secure_cookie($name, $value, $expiry = 86400, $path = '/', $domain = '', $secure = NULL, $httponly = NULL, $samesite = 'Strict') {
        if ($secure === NULL) {
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }
        if ($httponly === NULL) {
            $httponly = TRUE;
        }
        
        $options = [
            'expires' => time() + $expiry,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ];
        
        return setcookie($name, $value, $options);
    }
}

if (!function_exists('set_auth_cookie')) {
    function set_auth_cookie($token, $expiry = 86400) {
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($domain, 'localhost') !== false || strpos($domain, '127.0.0.1') !== false) {
            $domain = '';
        } else {
            $domain = '.' . ltrim($domain, 'www.');
        }
        
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        
        $options = [
            'expires' => time() + $expiry,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => TRUE,
          'samesite' => 'Lax'
        ];
        
        return setcookie('auth_token', $token, $options);
    }
}

if (!function_exists('get_auth_cookie')) {
    function get_auth_cookie() {
        return isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
    }
}

if (!function_exists('delete_auth_cookie')) {
    function delete_auth_cookie() {
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($domain, 'localhost') !== false || strpos($domain, '127.0.0.1') !== false) {
            $domain = '';
        } else {
            $domain = '.' . ltrim($domain, 'www.');
        }
        
        setcookie('auth_token', '', time() - 3600, '/', $domain, isset($_SERVER['HTTPS']), TRUE);
        setcookie('auth_token', '', time() - 3600, '/', '', FALSE);
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
}

if (!function_exists('set_csrf_cookie')) {
    function set_csrf_cookie($regenerate = FALSE) {
        $existing = $_SESSION['csrf_token'] ?? null;
        
        if (!$existing || $regenerate) {
            return generate_csrf_token();
        }
        
        $tokenAge = time() - ($_SESSION['csrf_token_time'] ?? 0);
        if ($tokenAge > 7200) {
            return generate_csrf_token();
        }
        
        return $existing;
    }
}

if (!function_exists('get_csrf_token')) {
    function get_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return generate_csrf_token();
        }
        
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('get_csrf_token_header')) {
    function get_csrf_token_header() {
        $token = get_csrf_token();
        return $token;
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $stored = $_SESSION['csrf_token'] ?? null;
        if (!$stored || !$token) {
            return FALSE;
        }
        
        if (!hash_equals($stored, $token)) {
            return FALSE;
        }
        
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        if (time() - $tokenTime > 7200) {
            return FALSE;
        }
        
        return TRUE;
    }
}
