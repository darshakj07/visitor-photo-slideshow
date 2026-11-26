<?php
/**
 * Public Slideshow JSON API Provider
 * Streams approved photos from consented visitors
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$db = getDB();

try {
    // 1. Fetch current slideshow interval setting
    $interval = (int) getSlideshowSetting('slideshow_interval', (string) DEFAULT_SLIDESHOW_INTERVAL);
    if ($interval <= 0) {
        $interval = 8;
    }

    $showVisitorNameSetting = (bool) getSlideshowSetting('show_visitor_name', '1');

    // 2. Fetch all approved photos where visitor has granted consent
    // Subquery selects the latest consent status per visitor
    $sql = "
        SELECT 
            p.id, 
            p.storage_path, 
            p.uploaded_at,
            v.name AS visitor_name,
            c.display_name_permitted
        FROM photos p
        INNER JOIN visitors v ON p.visitor_id = v.id
        INNER JOIN (
            SELECT cr1.visitor_id, cr1.consent_status, cr1.display_name_permitted
            FROM consent_records cr1
            INNER JOIN (
                SELECT visitor_id, MAX(id) AS max_id
                FROM consent_records
                GROUP BY visitor_id
            ) cr2 ON cr1.id = cr2.max_id
        ) c ON p.visitor_id = c.visitor_id
        WHERE p.is_approved = 1 
          AND c.consent_status = 'granted'
        ORDER BY p.uploaded_at ASC
    ";

    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();

    $photos = [];
    foreach ($rows as $row) {
        $caption = '';
        if ($showVisitorNameSetting && !empty($row['display_name_permitted'])) {
            $caption = 'Photo by ' . e($row['visitor_name']);
        }

        $photos[] = [
            'id'          => (int) $row['id'],
            'url'         => APP_URL . '/' . $row['storage_path'],
            'caption'     => $caption,
            'uploaded_at' => $row['uploaded_at'],
        ];
    }

    echo json_encode([
        'success'  => true,
        'interval' => $interval,
        'count'    => count($photos),
        'photos'   => $photos,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Slideshow API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to load slideshow photos.',
    ]);
}
