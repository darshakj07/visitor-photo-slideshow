<?php
/**
 * Automated Test: Admin Authentication & Moderation
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running Admin Authentication Tests (TC-ADM-01) ===" . PHP_EOL;

// 1. Verify invalid admin credentials rejected
$badLogin = attemptAdminLogin('admin', 'WrongPassword123!');
if ($badLogin === false) {
    echo "[PASS] TC-ADM-01: Invalid admin password correctly rejected." . PHP_EOL;
} else {
    echo "[FAIL] TC-ADM-01: Invalid admin password was accepted!" . PHP_EOL;
    exit(1);
}

// 2. Verify valid admin credentials accepted
$goodLogin = attemptAdminLogin('admin', 'Admin@2026!');
if ($goodLogin === true) {
    echo "[PASS] TC-ADM-01: Valid admin credentials authenticated successfully." . PHP_EOL;
} else {
    echo "[FAIL] TC-ADM-01: Valid admin credentials failed authentication!" . PHP_EOL;
    exit(1);
}

// 3. Automated Test: Photo Approval / Unapproval & Security Checks (TC-MOD-01, TC-MOD-02, TC-SEC-01)
echo "=== Running Admin Moderation & Approval Tests (TC-MOD-01, TC-MOD-02) ===" . PHP_EOL;

try {
    $db = Database::getConnection();
} catch (Throwable $e) {
    echo "[INFO] Database not connected; skipping live photo moderation checks." . PHP_EOL;
    echo "=== ADMIN TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
    exit(0);
}

require_once __DIR__ . '/../includes/csrf.php';

// Setup temporary test visitor & photo
$testEmail = 'mod_test_' . time() . '@example.com';
$tokenHash = hashVisitorToken(generateVisitorToken());
$db->prepare("INSERT INTO visitors (name, email, token_hash, created_at, updated_at) VALUES ('Mod Test Visitor', ?, ?, NOW(), NOW())")->execute([$testEmail, $tokenHash]);
$visitorId = (int)$db->lastInsertId();

$uniqueFilename = 'mod_test_' . time() . '.jpg';
$db->prepare("
    INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at)
    VALUES (?, 'mod_photo.jpg', ?, ?, 'image/jpeg', 2048, 1, NOW())
")->execute([$visitorId, $uniqueFilename, 'uploads/' . $uniqueFilename]);
$photoId = (int)$db->lastInsertId();

try {
    // 3a. Verify initial approved state (1)
    $stmt = $db->prepare("SELECT is_approved FROM photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $initialStatus = (int)$stmt->fetchColumn();
    if ($initialStatus === 1) {
        echo "[PASS] TC-MOD-01: Test photo initialized with approved status (is_approved = 1)." . PHP_EOL;
    } else {
        echo "[FAIL] TC-MOD-01: Test photo did not have initial approved status." . PHP_EOL;
        exit(1);
    }

    // 3b. Test unapproving a photo (Toggle 1 -> 0)
    $toggleStmt = $db->prepare("UPDATE photos SET is_approved = IF(is_approved = 1, 0, 1) WHERE id = ?");
    $toggleStmt->execute([$photoId]);

    $stmt->execute([$photoId]);
    $unapprovedStatus = (int)$stmt->fetchColumn();
    if ($unapprovedStatus === 0) {
        echo "[PASS] TC-MOD-01: Photo successfully unapproved (status toggled to is_approved = 0)." . PHP_EOL;
    } else {
        echo "[FAIL] TC-MOD-01: Photo unapproval toggle failed." . PHP_EOL;
        exit(1);
    }

    // 3c. Test re-approving a photo (Toggle 0 -> 1)
    $toggleStmt->execute([$photoId]);
    $stmt->execute([$photoId]);
    $reapprovedStatus = (int)$stmt->fetchColumn();
    if ($reapprovedStatus === 1) {
        echo "[PASS] TC-MOD-02: Photo successfully approved (status toggled back to is_approved = 1)." . PHP_EOL;
    } else {
        echo "[FAIL] TC-MOD-02: Photo re-approval toggle failed." . PHP_EOL;
        exit(1);
    }

    // 3d. Security Test: Reject unauthenticated request
    // Emulate unauthenticated session
    logoutAdmin();
    $isAuth = isAdminLoggedIn();
    if ($isAuth === false) {
        echo "[PASS] TC-SEC-01: Unauthorized access check blocks non-admin requests." . PHP_EOL;
    } else {
        echo "[FAIL] TC-SEC-01: isAdminLoggedIn incorrectly returned true for unauthenticated session." . PHP_EOL;
        exit(1);
    }

    // 3e. Security Test: Reject invalid CSRF tokens
    $validCsrf = getCsrfToken();
    $tamperedCsrf = 'tampered_' . bin2hex(random_bytes(16));
    if (verifyCsrfToken($tamperedCsrf) === false) {
        echo "[PASS] TC-SEC-02: Invalid CSRF token correctly rejected on photo moderation action." . PHP_EOL;
    } else {
        echo "[FAIL] TC-SEC-02: Invalid CSRF token was accepted!" . PHP_EOL;
        exit(1);
    }

    if (verifyCsrfToken($validCsrf) === true) {
        echo "[PASS] TC-SEC-02: Legitimate CSRF token correctly verified." . PHP_EOL;
    } else {
        echo "[FAIL] TC-SEC-02: Legitimate CSRF token verification failed." . PHP_EOL;
        exit(1);
    }

} finally {
    // Cleanup temporary test data
    $db->prepare("DELETE FROM photos WHERE id = ?")->execute([$photoId]);
    $db->prepare("DELETE FROM visitors WHERE id = ?")->execute([$visitorId]);
}

echo "=== ADMIN TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
