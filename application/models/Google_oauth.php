<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '../vendor/autoload.php';

use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

class Google_oauth extends CI_Model {

    private $client;

    public function __construct() {
        parent::__construct();
        $this->client = new Google_Client();
        $this->client->setAuthConfig(GOOGLE_CREDENTIALS_PATH);
        $this->client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
        $this->client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
    }

    public function verify($token) {
        return $this->client->verifyIdToken($token);
    }
}
