<?php
/**
 * Visitor Photo Deletion Handler
 * Securely unlinks the physical photo and removes the database record
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/visitor/dashboard.php');
}

validateCsrfOrDie();
$visitor = requireVisitorAuth();
$visitorId = (int) $visitor['id'];
$photoId = (int) ($_POST['photo_id'] ?? 0);

if ($photoId <= 0) {
    setFlashMessage('danger', 'Invalid photo ID.');
    redirect(APP_URL . '/visitor/dashboard.php');
}

$db = getDB();

try {
    // 1. Fetch photo and verify ownership
    $stmt = $db->prepare("SELECT id, stored_filename, storage_path FROM photos WHERE id = ? AND visitor_id = ? LIMIT 1");
    $stmt->execute([$photoId, $visitorId]);
    $photo = $stmt->fetch();

    if (!$photo) {
        setFlashMessage('danger', 'Photo not found or you do not have permission to delete it.');
        redirect(APP_URL . '/visitor/dashboard.php');
    }

    // 2. Delete database record
    $delStmt = $db->prepare("DELETE FROM photos WHERE id = ? AND visitor_id = ?");
    $delStmt->execute([$photoId, $visitorId]);

    // 3. Remove physical file from uploads directory
    $filePath = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photo['storage_path']);
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    setFlashMessage('success', 'Photo was permanently deleted from your gallery and slideshow.');
} catch (Exception $e) {
    error_log("Photo deletion error: " . $e->getMessage());
    setFlashMessage('danger', 'An error occurred while deleting the photo.');
}

redirect(APP_URL . '/visitor/dashboard.php');
