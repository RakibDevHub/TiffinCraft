<?php
require_once __DIR__ . '/../helpers/Env.php';

// Load ENV
Env::load(__DIR__ . '/../../.env');

// === Core App Config ===
define('APP_NAME', $_ENV['APP_NAME'] ?? 'TiffinCraft');
define('BASE_URL', $_ENV['BASE_URL'] ?? 'https://tiffincraft.test');
define('DEBUG', ($_ENV['APP_ENV'] ?? 'production') === 'development');

define('BASE_PATH', dirname(__DIR__, levels: 2));
define('UPLOADS_DIR', BASE_PATH . '/public/uploads');

// === Mail Defaults ===
define('MAIL_FROM_ADDRESS', 'rakibdevhub@gmail.com');
define('MAIL_FROM_NAME', 'TiffinCraft');


// === SSLCommerz ===
define('SSLCOMMERZ_LIVE', $_ENV['SSLCOMMERZ_LIVE '] ?? false);
define('SSLCOMMERZ_STORE_ID', $_ENV['SSLCOMMERZ_STORE_ID'] ?? 'tiffi6899ad31bafdc');
define('SSLCOMMERZ_STORE_PASSWORD', $_ENV['SSLCOMMERZ_STORE_PASSWORD'] ?? 'tiffi6899ad31bafdc@ssl');

// === Timezone ===
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka');

// === Session Security ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);


// === Error Settings ===
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
}

// === Session Security ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
