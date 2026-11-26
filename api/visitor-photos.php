<?php
/**
 * Visitor Photos API Endpoint
 * Provides JSON list of photos for the currently authenticated visitor
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$visitor = getCurrentVisitor();
if (!$visitor) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized visitor session.'], 401);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at 
        FROM photos 
        WHERE visitor_id = ? 
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([(int) $visitor['id']]);
    $photos = $stmt->fetchAll();

    $formatted = array_map(function ($p) {
        return [
            'id'                => (int) $p['id'],
            'original_filename' => $p['original_filename'],
            'url'               => APP_URL . '/' . $p['storage_path'],
            'file_size'         => (int) $p['file_size'],
            'is_approved'       => (bool) $p['is_approved'],
            'uploaded_at'       => $p['uploaded_at'],
        ];
    }, $photos);

    jsonResponse([
        'success' => true,
        'count'   => count($formatted),
        'photos'  => $formatted,
    ]);
} catch (Exception $e) {
    error_log("Visitor photos API error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Database error.'], 500);
}
