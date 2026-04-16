<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '../vendor/autoload.php';

use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

class Google_oauth extends CI_Model {

    private $client;
    private $clientId = '105312694935-usp11c8mc5s5nfaf6mk6plt20053g0b8.apps.googleusercontent.com';

    public function __construct() {
        parent::__construct();
        $this->client = new Google_Client();
        $this->client->setAuthConfig(GOOGLE_CREDENTIALS_PATH);
        $this->client->setClientId($this->clientId);
        $this->client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
        $this->client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
        log_message('info', '[GAuth] Client initialized. ClientId from config: ' . $this->client->getClientId());
    }

    public function verify($token) {
        try {
            $payload = $this->client->verifyIdToken($token);
            if ($payload && isset($payload['aud']) && $payload['aud'] !== $this->clientId) {
                log_message('error', '[GAuth] Audience mismatch: ' . $payload['aud'] . ' != ' . $this->clientId);
                return null;
            }
            return $payload;
        } catch (\Exception $e) {
            log_message('error', '[GAuth] Token verification failed: ' . $e->getMessage());
            return null;
        }
    }
}
