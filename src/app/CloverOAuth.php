<?php

namespace App;

class CloverOAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $isSandbox;
    
    // Base URLs for different environments
    private $sandboxBaseUrl = 'apisandbox.dev.clover.com';
    private $productionBaseUrl = 'www.clover.com';
    private $productionApiBaseUrl = 'api.clover.com';
    
    public function __construct($clientId, $clientSecret, $redirectUri, $isSandbox = true) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->isSandbox = $isSandbox;
    }
    
    /**
     * Step 1: Generate the authorization URL for merchant consent
     */
    public function getAuthorizationUrl() {
        $baseUrl = $this->isSandbox ? $this->sandboxBaseUrl : $this->productionBaseUrl;
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri
        ]);
        
        return "https://{$baseUrl}/oauth/v2/authorize?{$params}";
    }
    
    /**
     * Step 2: Exchange authorization code for tokens
     */
    public function getAccessToken($authCode) {
        $baseUrl = $this->isSandbox ? $this->sandboxBaseUrl : $this->productionApiBaseUrl;
        $url = "https://{$baseUrl}/oauth/v2/token";
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $authCode
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Step 3: Refresh expired access token using refresh token
     */
    public function refreshAccessToken($refreshToken) {
        $baseUrl = $this->isSandbox ? $this->sandboxBaseUrl : $this->productionApiBaseUrl;
        $url = "https://{$baseUrl}/oauth/v2/refresh";
        
        $data = [
            'client_id' => $this->clientId,
            'refresh_token' => $refreshToken
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        return json_decode($response, true);
    }
}