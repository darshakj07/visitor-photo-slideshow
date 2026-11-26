<?php
/**
 * Admin Action: Permanent Photo Deletion
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
$returnUrl = validateAdminReturnUrl($_POST['return_url'] ?? null, APP_URL . '/admin/photos.php');

if ($photoId > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT storage_path FROM photos WHERE id = ? LIMIT 1");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch();

        if ($photo) {
            $delStmt = $db->prepare("DELETE FROM photos WHERE id = ?");
            $delStmt->execute([$photoId]);

            $diskPath = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photo['storage_path']);
            if (file_exists($diskPath)) {
                @unlink($diskPath);
            }
            setFlashMessage('success', 'Photo was permanently deleted by host.');
        } else {
            setFlashMessage('warning', 'Photo was not found.');
        }
    } catch (Exception $e) {
        error_log("Admin delete photo error: " . $e->getMessage());
        setFlashMessage('danger', 'Failed to delete photo.');
    }
}

redirect($returnUrl);
