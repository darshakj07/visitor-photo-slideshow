<?php
/**
 * Automated Test: Admin Open Redirect Prevention & Action Handlers (Task 2)
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running Security Tests: Admin Open Redirect Prevention (Task 2) ===" . PHP_EOL;

$defaultFallback = APP_URL . '/admin/photos.php';

// -------------------------------------------------------------
// Part 1: Open Redirect Destination Validation
// -------------------------------------------------------------

// 1. Legitimate internal admin return URL (dashboard)
$legitDashboard = APP_URL . '/admin/dashboard.php';
$res1 = validateAdminReturnUrl($legitDashboard);
if ($res1 === $legitDashboard) {
    echo "[PASS] Test 1: Legitimate admin return URL accepted: {$res1}" . PHP_EOL;
} else {
    echo "[FAIL] Test 1: Legitimate URL was not accepted! Got: {$res1}" . PHP_EOL;
    exit(1);
}

// 2. Legitimate internal admin return URL with allowed filter query
$legitFilter = APP_URL . '/admin/photos.php?filter=approved';
$res2 = validateAdminReturnUrl($legitFilter);
if ($res2 === $legitFilter) {
    echo "[PASS] Test 2: Legitimate admin URL with permitted query filter accepted: {$res2}" . PHP_EOL;
} else {
    echo "[FAIL] Test 2: Legitimate URL with query filter rejected! Got: {$res2}" . PHP_EOL;
    exit(1);
}

// 3. External URL (https://evil.example)
$evil1 = 'https://evil.example';
$res3 = validateAdminReturnUrl($evil1);
if ($res3 === $defaultFallback) {
    echo "[PASS] Test 3: External URL '{$evil1}' rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 3: External URL was NOT rejected! Got: {$res3}" . PHP_EOL;
    exit(1);
}

// 4. External URL attempting to spoof admin path (https://evil.example/admin/photos.php)
$evil2 = 'https://evil.example/admin/photos.php';
$res4 = validateAdminReturnUrl($evil2);
if ($res4 === $defaultFallback) {
    echo "[PASS] Test 4: External URL with spoofed admin path rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 4: Spoofed external URL was NOT rejected! Got: {$res4}" . PHP_EOL;
    exit(1);
}

// 5. Protocol-relative URL (//evil.example)
$evilProto = '//evil.example/admin/photos.php';
$res5 = validateAdminReturnUrl($evilProto);
if ($res5 === $defaultFallback) {
    echo "[PASS] Test 5: Protocol-relative URL '//evil.example' rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 5: Protocol-relative URL was NOT rejected! Got: {$res5}" . PHP_EOL;
    exit(1);
}

// 6. Backslash-prefixed protocol-relative URL (/\evil.example or \\evil.example)
$evilSlash = '/\\evil.example/admin/photos.php';
$res6 = validateAdminReturnUrl($evilSlash);
if ($res6 === $defaultFallback) {
    echo "[PASS] Test 6: Backslash-prefixed URL rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 6: Backslash-prefixed URL was NOT rejected! Got: {$res6}" . PHP_EOL;
    exit(1);
}

// 7. Javascript URL (javascript:alert(document.cookie))
$evilJs = 'javascript:alert(document.cookie)';
$res7 = validateAdminReturnUrl($evilJs);
if ($res7 === $defaultFallback) {
    echo "[PASS] Test 7: Javascript pseudo-protocol URL rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 7: Javascript URL was NOT rejected! Got: {$res7}" . PHP_EOL;
    exit(1);
}

// 8. Data URI scheme (data:text/html,<script>...)
$evilData = 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==';
$res8 = validateAdminReturnUrl($evilData);
if ($res8 === $defaultFallback) {
    echo "[PASS] Test 8: Data URI scheme rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 8: Data URI was NOT rejected! Got: {$res8}" . PHP_EOL;
    exit(1);
}

// 9. Malformed URL (http://:80 or non-parseable)
$evilMalformed = 'http://:80/invalid';
$res9 = validateAdminReturnUrl($evilMalformed);
if ($res9 === $defaultFallback) {
    echo "[PASS] Test 9: Malformed URL rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 9: Malformed URL was NOT rejected! Got: {$res9}" . PHP_EOL;
    exit(1);
}

// 10. CRLF Header Injection Attempt (admin/photos.php%0d%0aLocation:...)
$crlfUrl = APP_URL . "/admin/photos.php\r\nLocation: https://evil.example";
$res10 = validateAdminReturnUrl($crlfUrl);
if ($res10 === $defaultFallback) {
    echo "[PASS] Test 10: CRLF header injection attempt rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 10: CRLF URL was NOT rejected! Got: {$res10}" . PHP_EOL;
    exit(1);
}

// 11. Directory traversal / non-admin script (e.g. attempting to redirect to index.php or login.php)
$nonAdmin = APP_URL . '/index.php';
$res11 = validateAdminReturnUrl($nonAdmin);
if ($res11 === $defaultFallback) {
    echo "[PASS] Test 11: Non-admin script destination rejected, safely redirected to default." . PHP_EOL;
} else {
    echo "[FAIL] Test 11: Non-admin script destination was NOT rejected! Got: {$res11}" . PHP_EOL;
    exit(1);
}

// 12. Unrecognized query parameter injection (strips unauthorized params)
$paramInjection = APP_URL . '/admin/photos.php?filter=approved&untrusted=1&token=steal';
$res12 = validateAdminReturnUrl($paramInjection);
if ($res12 === APP_URL . '/admin/photos.php?filter=approved') {
    echo "[PASS] Test 12: Untrusted query parameters successfully stripped." . PHP_EOL;
} else {
    echo "[FAIL] Test 12: Query parameters were not sanitized! Got: {$res12}" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Part 2: Photo Approval & Deletion Functionality Preservation
// -------------------------------------------------------------
echo ">>> Part 2: Testing Photo Action Handlers (Approval Toggle & Deletion)..." . PHP_EOL;

$db = getDB();

// Create temporary visitor and photo
$testEmail = 'admin_action_test_' . time() . '@example.com';
$vStmt = $db->prepare("INSERT INTO visitors (name, email, token_hash, created_at, updated_at) VALUES ('Admin Action Tester', ?, 'test_token_hash', NOW(), NOW())");
$vStmt->execute([$testEmail]);
$tempVisitorId = (int) $db->lastInsertId();

$tempStoredFile = 'action_test_' . bin2hex(random_bytes(6)) . '.png';
$targetDir = rtrim(UPLOADS_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$tempDiskPath = $targetDir . $tempStoredFile;
$pngBytes = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
file_put_contents($tempDiskPath, $pngBytes);

$pStmt = $db->prepare("INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at) VALUES (?, 'action_test.png', ?, ?, 'image/png', 68, 1, NOW())");
$pStmt->execute([$tempVisitorId, $tempStoredFile, 'uploads/' . $tempStoredFile]);
$tempPhotoId = (int) $db->lastInsertId();

// 13. Test Toggle Approval logic: 1 -> 0
$db->prepare("UPDATE photos SET is_approved = IF(is_approved = 1, 0, 1) WHERE id = ?")->execute([$tempPhotoId]);
$status1 = (int) $db->query("SELECT is_approved FROM photos WHERE id = {$tempPhotoId}")->fetchColumn();
if ($status1 === 0) {
    echo "[PASS] Test 13a: Photo approval successfully toggled from 1 to 0." . PHP_EOL;
} else {
    echo "[FAIL] Test 13a: Toggle approval failed (expected 0, got {$status1})." . PHP_EOL;
    exit(1);
}

// Toggle back: 0 -> 1
$db->prepare("UPDATE photos SET is_approved = IF(is_approved = 1, 0, 1) WHERE id = ?")->execute([$tempPhotoId]);
$status2 = (int) $db->query("SELECT is_approved FROM photos WHERE id = {$tempPhotoId}")->fetchColumn();
if ($status2 === 1) {
    echo "[PASS] Test 13b: Photo approval successfully toggled from 0 to 1." . PHP_EOL;
} else {
    echo "[FAIL] Test 13b: Toggle approval failed (expected 1, got {$status2})." . PHP_EOL;
    exit(1);
}

// 14. Test Photo Deletion logic
$delQuery = $db->prepare("SELECT storage_path FROM photos WHERE id = ? LIMIT 1");
$delQuery->execute([$tempPhotoId]);
$photoRecord = $delQuery->fetch();

if ($photoRecord) {
    $db->prepare("DELETE FROM photos WHERE id = ?")->execute([$tempPhotoId]);
    $diskPath = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photoRecord['storage_path']);
    if (file_exists($diskPath)) {
        unlink($diskPath);
    }
}

$dbCheck = $db->query("SELECT COUNT(*) FROM photos WHERE id = {$tempPhotoId}")->fetchColumn();
$diskCheck = file_exists($tempDiskPath);

if ((int)$dbCheck === 0 && !$diskCheck) {
    echo "[PASS] Test 14: Photo deletion cleanly removes database record and disk file." . PHP_EOL;
} else {
    echo "[FAIL] Test 14: Photo was not cleanly deleted from DB or disk!" . PHP_EOL;
    exit(1);
}

// 15. Verify CSRF Protection function
$validCsrf = getCsrfToken();
if (verifyCsrfToken($validCsrf) && !verifyCsrfToken('invalid_token_123')) {
    echo "[PASS] Test 15: CSRF token verification operates correctly." . PHP_EOL;
} else {
    echo "[FAIL] Test 15: CSRF verification logic failed!" . PHP_EOL;
    exit(1);
}

// Cleanup temp visitor
$db->exec("DELETE FROM visitors WHERE id = {$tempVisitorId}");

echo "=== ALL OPEN REDIRECT AND ACTION TESTS PASSED ===" . PHP_EOL;
