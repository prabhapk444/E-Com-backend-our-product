<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cors {

   public function enable() {

    $allowed_origins = [
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1:8080',
        'https://shop.thriveboost.in',
        'https://ecombackend.thriveboost.in'
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;


    if ($origin === null) {
        header("Access-Control-Allow-Origin: *");
    }

    elseif (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Vary: Origin");
    }
   
    else {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied.');
    }

    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With, Authorization, Accept");
    header("Access-Control-Allow-Credentials: true");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}


}
