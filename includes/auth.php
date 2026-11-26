<?php
/**
 * Authentication & Access Control System
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// ==========================================
// VISITOR AUTHENTICATION & ACCESS FUNCTIONS
// ==========================================

/**
 * Generate a new secure 64-char hexadecimal visitor token
 */
function generateVisitorToken(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Hash visitor token for safe storage at rest
 */
function hashVisitorToken(string $token): string
{
    return hash('sha256', $token);
}

/**
 * Set visitor session upon successful identification
 */
function loginVisitor(array $visitor, string $plaintextToken = ''): void
{
    session_regenerate_id(true);
    $_SESSION['visitor_id'] = (int) $visitor['id'];
    $_SESSION['visitor_name'] = (string) $visitor['name'];
    $_SESSION['visitor_email'] = (string) $visitor['email'];
    if ($plaintextToken !== '') {
        $_SESSION['visitor_token'] = $plaintextToken;
    }
}

/**
 * Check if a visitor is currently logged in via session
 */
function isVisitorLoggedIn(): bool
{
    return !empty($_SESSION['visitor_id']);
}

/**
 * Get currently authenticated visitor array or null
 */
function getCurrentVisitor(): ?array
{
    if (!isVisitorLoggedIn()) {
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, token_hash, created_at, updated_at FROM visitors WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['visitor_id']]);
        $visitor = $stmt->fetch();
        return $visitor ?: null;
    } catch (Exception) {
        return null;
    }
}

/**
 * Authenticate visitor by plaintext token key
 */
function authenticateVisitorByToken(string $plaintextToken): ?array
{
    $tokenHash = hashVisitorToken(trim($plaintextToken));
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, token_hash, access_pin_hash, created_at FROM visitors WHERE token_hash = ? LIMIT 1");
    $stmt->execute([$tokenHash]);
    $visitor = $stmt->fetch();

    if ($visitor) {
        loginVisitor($visitor, $plaintextToken);
        return $visitor;
    }

    return null;
}

/**
 * Authenticate returning visitor by Email + PIN
 */
function authenticateVisitorByPin(string $email, string $pin): ?array
{
    $email = strtolower(trim($email));
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, token_hash, access_pin_hash, created_at FROM visitors WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $visitor = $stmt->fetch();

    if ($visitor && !empty($visitor['access_pin_hash'])) {
        if (password_verify(trim($pin), $visitor['access_pin_hash'])) {
            loginVisitor($visitor);
            return $visitor;
        }
    }

    return null;
}

/**
 * Require visitor authentication or redirect to access page
 */
function requireVisitorAuth(): array
{
    $visitor = getCurrentVisitor();
    if (!$visitor) {
        setFlashMessage('warning', 'Please access your visitor account using your Access Link or PIN.');
        redirect(APP_URL . '/visitor/access.php');
    }
    return $visitor;
}

/**
 * Log out visitor session
 */
function logoutVisitor(): void
{
    unset($_SESSION['visitor_id'], $_SESSION['visitor_name'], $_SESSION['visitor_email'], $_SESSION['visitor_token']);
}

// ==========================================
// ADMIN AUTHENTICATION FUNCTIONS
// ==========================================

/**
 * Check if admin is currently logged in
 */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

/**
 * Get currently authenticated admin array or null
 */
function getCurrentAdmin(): ?array
{
    if (!isAdminLoggedIn()) {
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, name, created_at FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        return $admin ?: null;
    } catch (Exception) {
        return null;
    }
}

/**
 * Attempt admin login
 */
function attemptAdminLogin(string $username, string $password): bool
{
    $username = trim($username);
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password_hash, name FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];
        $_SESSION['admin_name'] = (string) $admin['name'];
        return true;
    }

    return false;
}

/**
 * Require admin authentication or redirect to login page
 */
function requireAdminAuth(): array
{
    $admin = getCurrentAdmin();
    if (!$admin) {
        setFlashMessage('danger', 'You must log in to access the Host / Admin area.');
        redirect(APP_URL . '/admin/login.php');
    }
    return $admin;
}

/**
 * Log out admin
 */
function logoutAdmin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);
}
