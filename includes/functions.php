<?php
/**
 * Utility Functions & Output Helpers
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * Escape output for safe HTML rendering (XSS prevention)
 */
function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return standardized JSON response and exit
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Format bytes into human-readable string (KB, MB)
 */
function formatBytes(int $bytes, int $precision = 1): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Redirect to a given URL
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Validate and sanitize an admin return URL to prevent Open Redirection.
 * Strictly restricts destinations to known internal admin scripts (dashboard.php, photos.php, visitors.php)
 * and permitted query parameters. Rejects external schemes, hosts, protocol-relative, and javascript URLs.
 *
 * @param string|null $url The user-submitted return URL
 * @param string $default Safe fallback destination (defaults to admin photos page)
 * @return string Safe, reconstructed internal admin URL
 */
function validateAdminReturnUrl(?string $url, string $default = ''): string
{
    $fallback = ($default !== '') ? $default : (APP_URL . '/admin/photos.php');

    if ($url === null || trim($url) === '') {
        return $fallback;
    }

    $raw = trim($url);

    // 1. Block control characters, CR, LF (header injection / CRLF defense)
    if (preg_match('/[\x00-\x1F\x7F]/', $raw)) {
        return $fallback;
    }

    // 2. Block protocol-relative or backslash-prefixed URLs (//evil.example, /\evil.example, \\evil.example)
    if (str_starts_with($raw, '//') || str_starts_with($raw, '/\\') || str_starts_with($raw, '\\\\')) {
        return $fallback;
    }

    // 3. Block dangerous pseudo-protocols (javascript:, data:, vbscript:, file:)
    if (preg_match('/^(javascript|data|vbscript|file):/i', $raw)) {
        return $fallback;
    }

    $parsed = @parse_url($raw);
    if ($parsed === false || !is_array($parsed)) {
        return $fallback;
    }

    // 4. If scheme or host is present, it must strictly match the application host and scheme
    if (isset($parsed['host'])) {
        $appHost = (string) parse_url(APP_URL, PHP_URL_HOST);
        $appPort = parse_url(APP_URL, PHP_URL_PORT);
        $appScheme = (string) parse_url(APP_URL, PHP_URL_SCHEME);

        if (strcasecmp($parsed['host'], $appHost) !== 0) {
            return $fallback;
        }

        if (isset($parsed['scheme']) && strcasecmp($parsed['scheme'], $appScheme) !== 0) {
            return $fallback;
        }

        if (isset($parsed['port']) && $appPort !== null && $parsed['port'] !== $appPort) {
            return $fallback;
        }
    }

    // 5. Validate the path component
    $path = $parsed['path'] ?? '';
    if ($path === '' || !str_contains($path, '/admin/')) {
        return $fallback;
    }

    $scriptName = basename($path);
    $allowedScripts = ['dashboard.php', 'photos.php', 'visitors.php'];
    if (!in_array($scriptName, $allowedScripts, true)) {
        return $fallback;
    }

    // 6. Reconstruct the verified internal URL
    $cleanUrl = APP_URL . '/admin/' . $scriptName;

    // 7. Validate and sanitize permitted query parameters (e.g. ?filter=all|approved|hidden)
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $queryParams);
        if (isset($queryParams['filter']) && is_string($queryParams['filter'])) {
            $filter = strtolower(trim($queryParams['filter']));
            if (in_array($filter, ['all', 'approved', 'hidden'], true)) {
                $cleanUrl .= '?filter=' . urlencode($filter);
            }
        }
    }

    return $cleanUrl;
}

/**
 * Set a session flash message
 */
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash messages
 */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Get sanitized Client IP Address
 */
function getClientIP(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '127.0.0.1';
    }
    return $ip;
}

/**
 * Get sanitized Client User Agent
 */
function getClientUserAgent(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    return substr(trim($ua), 0, 255);
}

/**
 * Fetch a slideshow setting from DB with fallback
 */
function getSlideshowSetting(string $key, string $default = ''): string
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM slideshow_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? (string)$val : $default;
    } catch (Exception) {
        return $default;
    }
}
