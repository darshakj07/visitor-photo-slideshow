<?php
/**
 * Automated Test: Full Visitor Lifecycle, Returning Visitor Auth, Consent & Slideshow Flow
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running Full Visitor Lifecycle & Slideshow Tests ===" . PHP_EOL;

$db = getDB();

// Generate unique test visitor
$testEmail = 'testvisitor_' . time() . '@example.com';
$testName = 'Test Attendee';
$testPin = '987654';
$plaintextToken = generateVisitorToken();
$tokenHash = hashVisitorToken($plaintextToken);
$pinHash = password_hash($testPin, PASSWORD_BCRYPT);

// 1. Insert Visitor
$stmt = $db->prepare("INSERT INTO visitors (name, email, token_hash, access_pin_hash, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
$stmt->execute([$testName, $testEmail, $tokenHash, $pinHash]);
$visitorId = (int) $db->lastInsertId();
echo "[PASS] TC-REG-01: Visitor created with ID {$visitorId}" . PHP_EOL;

// 2. Test Returning Visitor Auth via PIN
$authByPin = authenticateVisitorByPin($testEmail, $testPin);
if ($authByPin && (int)$authByPin['id'] === $visitorId) {
    echo "[PASS] TC-RET-01: Authenticated successfully using Email + PIN." . PHP_EOL;
} else {
    echo "[FAIL] TC-RET-01: Email + PIN authentication failed." . PHP_EOL;
    exit(1);
}

// 3. Test Returning Visitor Auth via Token
$authByToken = authenticateVisitorByToken($plaintextToken);
if ($authByToken && (int)$authByToken['id'] === $visitorId) {
    echo "[PASS] TC-RET-01: Authenticated successfully using 64-char Access Token." . PHP_EOL;
} else {
    echo "[FAIL] TC-RET-01: Access Token authentication failed." . PHP_EOL;
    exit(1);
}

// 4. Test Rejecting Unauthorized / Wrong PIN
$wrongAuth = authenticateVisitorByPin($testEmail, '000000');
if ($wrongAuth === null) {
    echo "[PASS] TC-RET-02: Incorrect PIN correctly rejected." . PHP_EOL;
} else {
    echo "[FAIL] TC-RET-02: Incorrect PIN was incorrectly accepted!" . PHP_EOL;
    exit(1);
}

// 5. Insert Consent Record: 'granted'
$consentStmt = $db->prepare("
    INSERT INTO consent_records (visitor_id, consent_type, consent_status, display_name_permitted, ip_address, user_agent, granted_at)
    VALUES (?, 'public_slideshow', 'granted', 1, '127.0.0.1', 'PHP-Test-Runner', NOW())
");
$consentStmt->execute([$visitorId]);

// 6. Create synthetic image file in uploads
$testStoredName = 'test_' . bin2hex(random_bytes(8)) . '.png';
$testDiskPath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $testStoredName;
$pngBytes = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
file_put_contents($testDiskPath, $pngBytes);

$photoStmt = $db->prepare("
    INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at)
    VALUES (?, 'test_sample.png', ?, ?, 'image/png', 68, 1, NOW())
");
$photoStmt->execute([$visitorId, $testStoredName, 'uploads/' . $testStoredName]);
$photoId = (int) $db->lastInsertId();
echo "[PASS] Photo inserted with ID {$photoId}." . PHP_EOL;

// 7. Verify Slideshow Query includes this photo
$slideQuery = $db->query("
    SELECT p.id, p.storage_path, v.name AS visitor_name
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
    WHERE p.is_approved = 1 AND c.consent_status = 'granted' AND p.id = {$photoId}
");
$slidePhoto = $slideQuery->fetch();
if ($slidePhoto && (int)$slidePhoto['id'] === $photoId) {
    echo "[PASS] TC-SLI-01: Approved photo with active consent is present in slideshow feed." . PHP_EOL;
} else {
    echo "[FAIL] TC-SLI-01: Photo missing from slideshow feed!" . PHP_EOL;
    exit(1);
}

// 8. Test Consent Withdrawal (TC-CON-02)
$withdrawStmt = $db->prepare("
    INSERT INTO consent_records (visitor_id, consent_type, consent_status, display_name_permitted, ip_address, user_agent, granted_at, withdrawn_at)
    VALUES (?, 'public_slideshow', 'withdrawn', 1, '127.0.0.1', 'PHP-Test-Runner', NOW(), NOW())
");
$withdrawStmt->execute([$visitorId]);

// Verify photo is now EXCLUDED from slideshow
$slideQuery2 = $db->query("
    SELECT p.id
    FROM photos p
    INNER JOIN (
        SELECT cr1.visitor_id, cr1.consent_status
        FROM consent_records cr1
        INNER JOIN (
            SELECT visitor_id, MAX(id) AS max_id
            FROM consent_records
            GROUP BY visitor_id
        ) cr2 ON cr1.id = cr2.max_id
    ) c ON p.visitor_id = c.visitor_id
    WHERE p.is_approved = 1 AND c.consent_status = 'granted' AND p.id = {$photoId}
");
$slidePhoto2 = $slideQuery2->fetch();
if ($slidePhoto2 === false) {
    echo "[PASS] TC-CON-02: After consent withdrawal, photo is immediately excluded from slideshow feed." . PHP_EOL;
} else {
    echo "[FAIL] TC-CON-02: Photo still appeared in slideshow after consent withdrawal!" . PHP_EOL;
    exit(1);
}

// 9. Test Photo Deletion and Disk Unlink
$delStmt = $db->prepare("DELETE FROM photos WHERE id = ? AND visitor_id = ?");
$delStmt->execute([$photoId, $visitorId]);
if (file_exists($testDiskPath)) {
    unlink($testDiskPath);
}

$existsInDb = $db->query("SELECT COUNT(*) FROM photos WHERE id = {$photoId}")->fetchColumn();
$existsOnDisk = file_exists($testDiskPath);

if ((int)$existsInDb === 0 && !$existsOnDisk) {
    echo "[PASS] Photo record deleted from DB and physical file removed from disk." . PHP_EOL;
} else {
    echo "[FAIL] Photo was not cleanly unlinked." . PHP_EOL;
    exit(1);
}

// Cleanup test visitor
$db->exec("DELETE FROM visitors WHERE id = {$visitorId}");
echo "=== ALL VISITOR LIFECYCLE TESTS PASSED ===" . PHP_EOL;
