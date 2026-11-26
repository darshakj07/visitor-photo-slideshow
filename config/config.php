<?php
/**
 * Global Configuration and Constants
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/environment.php';

// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
define('INCLUDES_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'includes');
define('UPLOADS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Application Settings
define('APP_NAME', (string) env('APP_NAME', 'Visitor Photo Slideshow'));
define('APP_ENV', (string) env('APP_ENV', 'development'));
define('APP_DEBUG', (bool) env('APP_DEBUG', true));

// Base URL calculation (with fallback for CLI testing)
$configuredUrl = env('APP_URL', null);
if ($configuredUrl) {
    define('APP_URL', rtrim((string)$configuredUrl, '/'));
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $protocol . $host . '/visitor-photo-slideshow');
}

// Upload & Validation Constraints
define('MAX_FILE_SIZE_BYTES', (int) env('MAX_FILE_SIZE_MB', 10) * 1024 * 1024); // 10MB
define('MAX_FILES_PER_UPLOAD', (int) env('MAX_FILES_PER_UPLOAD', 10));
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

// Slideshow Defaults
define('DEFAULT_SLIDESHOW_INTERVAL', (int) env('SLIDESHOW_INTERVAL', 8));
define('SLIDESHOW_AUTO_REFRESH_SECONDS', (int) env('SLIDESHOW_AUTO_REFRESH', 30));

// Error reporting based on debug flag
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Session Configuration (Strict, HttpOnly, SameSite)
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}
