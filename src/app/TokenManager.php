<?php

namespace App;

class TokenManager {
    private $clientId;
    private $baseUrl;

    public function __construct($clientId, $isProduction = false) {
        $this->clientId = $clientId;
        $this->baseUrl = $isProduction ? 'api.clover.com' : 'apisandbox.dev.clover.com';
    }

    public function refreshToken($refreshToken) {
        $url = "https://{$this->baseUrl}/oauth/v2/refresh";
        
        $data = [
            'client_id' => $this->clientId,
            'refresh_token' => $refreshToken
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Token refresh failed with status code: $httpCode");
        }

        return json_decode($response, true);
    }
} 