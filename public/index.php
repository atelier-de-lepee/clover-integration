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
        if (!isset($_SESSION['refresh_token'])) {
            throw new Exception("No refresh token available");
        }

        $config = Config::getOAuth();
        $tokenManager = new TokenManager($config['client_id'], $config['is_sandbox']);
        $newTokens = $tokenManager->refreshToken($_SESSION['refresh_token']);
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
    $_SESSION['access_token'] = $tokens['access_token'];
    $_SESSION['refresh_token'] = $tokens['refresh_token'];
    $_SESSION['access_token_expiration'] = $tokens['access_token_expiration'];
}
