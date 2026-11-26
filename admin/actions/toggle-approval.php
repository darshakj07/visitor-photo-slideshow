<?php
/**
 * Admin Action: Toggle Photo Approval
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdminAuth();
validateCsrfOrDie();

$photoId = (int) ($_POST['photo_id'] ?? 0);
$returnUrl = $_POST['return_url'] ?? (APP_URL . '/admin/photos.php');

if ($photoId > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE photos SET is_approved = IF(is_approved = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$photoId]);
        setFlashMessage('success', 'Photo display status has been toggled.');
    } catch (Exception $e) {
        error_log("Toggle approval error: " . $e->getMessage());
        setFlashMessage('danger', 'Failed to toggle photo approval status.');
    }
}

redirect($returnUrl);
