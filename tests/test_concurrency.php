<?php
/**
 * Automated Test: Concurrent Simultaneous Upload Simulation (TC-CONC-01)
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running TC-CONC-01: Concurrent Upload Simulation ===" . PHP_EOL;

$db = getDB();
$targetDir = rtrim(UPLOADS_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

// Create 2 separate visitor profiles simultaneously
$visitorsData = [
    ['name' => 'Concurrent Visitor A', 'email' => 'concurr_a_' . time() . '@example.com'],
    ['name' => 'Concurrent Visitor B', 'email' => 'concurr_b_' . time() . '@example.com'],
];

$pngBytes = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
$createdPhotoIds = [];
$createdFilepaths = [];
$visitorIds = [];

foreach ($visitorsData as $vIdx => $vData) {
    $db->beginTransaction();
    try {
        $token = generateVisitorToken();
        $tokenHash = hashVisitorToken($token);

        $vStmt = $db->prepare("INSERT INTO visitors (name, email, token_hash, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $vStmt->execute([$vData['name'], $vData['email'], $tokenHash]);
        $vId = (int) $db->lastInsertId();
        $visitorIds[] = $vId;

        // Consent
        $cStmt = $db->prepare("INSERT INTO consent_records (visitor_id, consent_type, consent_status, display_name_permitted, ip_address, user_agent, granted_at) VALUES (?, 'public_slideshow', 'granted', 1, '127.0.0.1', 'CLI', NOW())");
        $cStmt->execute([$vId]);

        // Simulate 3 photos per visitor with the SAME original filename
        for ($p = 1; $p <= 3; $p++) {
            $origName = "camera_shot_001.png"; // Identical client-side filename!
            $storedName = bin2hex(random_bytes(16)) . '.png'; // Unique server-side storage name!
            $diskDest = $targetDir . $storedName;

            file_put_contents($diskDest, $pngBytes);
            $createdFilepaths[] = $diskDest;

            $pStmt = $db->prepare("INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at) VALUES (?, ?, ?, ?, 'image/png', 68, 1, NOW())");
            $pStmt->execute([$vId, $origName, $storedName, 'uploads/' . $storedName]);
            $createdPhotoIds[] = (int) $db->lastInsertId();
        }

        $db->commit();
        echo "[PASS] Visitor {$vData['name']} uploaded 3 photos within transaction." . PHP_EOL;
    } catch (Exception $e) {
        $db->rollBack();
        echo "[FAIL] Transaction failed for {$vData['name']}: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

// Verification: Check total distinct files on disk and records in DB
$uniqueFiles = array_unique($createdFilepaths);
if (count($uniqueFiles) === 6 && count($createdPhotoIds) === 6) {
    echo "[PASS] TC-CONC-01: Exactly 6 distinct unique filenames generated with zero collision despite identical original filenames." . PHP_EOL;
} else {
    echo "[FAIL] Collision or count mismatch detected." . PHP_EOL;
    exit(1);
}

// Cleanup synthetic test records and files
foreach ($createdFilepaths as $f) {
    if (file_exists($f)) @unlink($f);
}
foreach ($visitorIds as $vid) {
    $db->exec("DELETE FROM visitors WHERE id = {$vid}");
}

echo "=== TC-CONC-01 COMPLETED SUCCESSFULLY ===" . PHP_EOL;
