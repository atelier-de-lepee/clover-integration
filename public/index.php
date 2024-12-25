<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\CloverOAuth;
use App\TokenManager;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Add Config class
class Config {
    private static array $oauth;

    public static function init(): void {
        self::$oauth = [
            'client_id' => $_ENV['CLOVER_CLIENT_ID'],
            'client_secret' => $_ENV['CLOVER_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['CLOVER_REDIRECT_URI'],
            'is_sandbox' => $_ENV['CLOVER_IS_SANDBOX']
        ];
    }

    public static function getOAuth(): array {
        return self::$oauth;
    }
}

// Initialize config after loading env
Config::init();

// Get the request URI and remove query string
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Basic routing
switch ($request_uri) {
    case '/':
        // Home page
        echo "<h1>Welcome to the Clover OAuth App</h1>";
        echo "<ul>";
        echo "<li><a href='/auth'>Start OAuth Flow (/auth)</a></li>";
        echo "<li>OAuth Callback (/callback)</li>";
        echo "<li><a href='/refresh'>Refresh Token (/refresh)</a></li>";
        echo "</ul>";
        break;

    case '/auth':
        handleOAuth();
        break;

    case '/callback':
        handleCallback();
        break;

    case '/refresh':
        handleTokenRefresh();
        break;

    default:
        header("HTTP/1.0 404 Not Found");
        echo "404 - Page not found";
        break;
}

// OAuth handling function
function handleOAuth() {
    try {
        $oauth = new CloverOAuth(...array_values(Config::getOAuth()));
        $authUrl = $oauth->getAuthorizationUrl();
        header("Location: $authUrl");
        exit;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Callback handling function
function handleCallback() {
    try {
        // Log the callback data
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'GET' => $_GET,
            'POST' => $_POST,
            'SERVER' => [
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ]
        ];
        
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(
            $logDir . '/callback.log',
            json_encode($logData, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND
        );

        $oauth = new CloverOAuth(...array_values(Config::getOAuth()));

        if (isset($_GET['code'])) {
            $tokens = $oauth->getAccessToken($_GET['code']);
            storeTokens($tokens);
            
            echo "<pre>";
            echo "Authorization successful!\n\n";
            echo "Access Token: " . $tokens['access_token'] . "\n";
            echo "Expires at: " . date('Y-m-d H:i:s', $tokens['access_token_expiration']) . "\n";
            echo "Refresh Token: " . $tokens['refresh_token'] . "\n";
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Add new function to handle token refresh
function handleTokenRefresh() {
    try {
        $storageDir = __DIR__ . '/../storage/tokens';
        $refreshTokenPath = $storageDir . '/refresh_token.txt';
        
        if (!file_exists($refreshTokenPath)) {
            throw new Exception("No refresh token available");
        }

        $refreshToken = file_get_contents($refreshTokenPath);
        $config = Config::getOAuth();
        $tokenManager = new TokenManager($config['client_id'], $config['is_sandbox']);
        $newTokens = $tokenManager->refreshToken($refreshToken);
        storeTokens($newTokens);

        echo json_encode([
            'success' => true,
            'expires_at' => date('Y-m-d H:i:s', $newTokens['access_token_expiration'])
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// New helper function to store tokens
function storeTokens($tokens) {
    $storageDir = __DIR__ . '/../storage/tokens';
    
    // Ensure storage directory exists
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }
    
    // Store tokens in separate files
    file_put_contents($storageDir . '/access_token.txt', $tokens['access_token']);
    file_put_contents($storageDir . '/refresh_token.txt', $tokens['refresh_token']);
    file_put_contents($storageDir . '/expiration.txt', $tokens['access_token_expiration']);
}
