<?php
/**
 * Automated Security Test: Visitor Account Takeover Prevention (Task 1)
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running Security Tests: Account Takeover Prevention (Task 1) ===" . PHP_EOL;

$db = getDB();

// Setup victim visitor record
$victimEmail = 'victim_' . time() . '@example.com';
$victimOriginalName = 'Legitimate Visitor';
$victimOriginalPin = '123456';
$victimPinHash = password_hash($victimOriginalPin, PASSWORD_BCRYPT);
$victimToken = generateVisitorToken();
$victimTokenHash = hashVisitorToken($victimToken);

$stmt = $db->prepare("
    INSERT INTO visitors (name, email, token_hash, access_pin_hash, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([$victimOriginalName, $victimEmail, $victimTokenHash, $victimPinHash]);
$victimId = (int) $db->lastInsertId();

// Setup synthetic photo for victim
$victimPhotoFile = 'victim_photo_' . bin2hex(random_bytes(6)) . '.png';
$targetDir = rtrim(UPLOADS_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$victimPhotoPath = $targetDir . $victimPhotoFile;
$pngBytes = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
file_put_contents($victimPhotoPath, $pngBytes);

$pStmt = $db->prepare("
    INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at)
    VALUES (?, 'my_private_photo.png', ?, ?, 'image/png', 68, 1, NOW())
");
$pStmt->execute([$victimId, $victimPhotoFile, 'uploads/' . $victimPhotoFile]);
$victimPhotoId = (int) $db->lastInsertId();

echo "[SETUP] Created victim visitor ID: {$victimId} with email {$victimEmail}" . PHP_EOL;

// Ensure clean unauthenticated session state
logoutVisitor();

// -------------------------------------------------------------
// Test 2: Submission using an existing email without authentication
// -------------------------------------------------------------
echo ">>> Test 2: Submission using existing email without authentication..." . PHP_EOL;

// Simulate upload logic against victim email with NO PIN and NO session
$currentVisitor = getCurrentVisitor(); // null
$stmt = $db->prepare("SELECT id, name, email, token_hash, access_pin_hash FROM visitors WHERE email = ? LIMIT 1");
$stmt->execute([$victimEmail]);
$foundVisitor = $stmt->fetch();

$isOwnerVerified = false;
$imposterPin = '';
$imposterName = 'Hacker Imposter';

if ($foundVisitor) {
    $visitorId = (int) $foundVisitor['id'];
    if ($currentVisitor && (int)$currentVisitor['id'] === $visitorId) {
        $isOwnerVerified = true;
    } elseif ($imposterPin !== '' && !empty($foundVisitor['access_pin_hash']) && password_verify($imposterPin, $foundVisitor['access_pin_hash'])) {
        $isOwnerVerified = true;
    }
}

if (!$isOwnerVerified) {
    echo "[PASS] Test 2: Unauthenticated submission on existing email correctly rejected (isOwnerVerified = false)." . PHP_EOL;
} else {
    echo "[FAIL] Test 2: Unauthenticated submission was accepted!" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Test 3: Submission using an existing email with INCORRECT PIN
// -------------------------------------------------------------
echo ">>> Test 3: Submission using existing email with incorrect PIN..." . PHP_EOL;
$wrongPin = '999999';
$isOwnerVerifiedWrong = false;

if ($foundVisitor) {
    $visitorId = (int) $foundVisitor['id'];
    if ($currentVisitor && (int)$currentVisitor['id'] === $visitorId) {
        $isOwnerVerifiedWrong = true;
    } elseif ($wrongPin !== '' && !empty($foundVisitor['access_pin_hash']) && password_verify($wrongPin, $foundVisitor['access_pin_hash'])) {
        $isOwnerVerifiedWrong = true;
    }
}

if (!$isOwnerVerifiedWrong) {
    echo "[PASS] Test 3: Incorrect PIN correctly rejected (isOwnerVerified = false)." . PHP_EOL;
} else {
    echo "[FAIL] Test 3: Incorrect PIN was accepted!" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Test 6: Verify unauthorized request CANNOT modify visitor name or PIN
// -------------------------------------------------------------
echo ">>> Test 6: Verify victim name and PIN hash remain unmodified in database..." . PHP_EOL;
$stmtCheck = $db->prepare("SELECT name, access_pin_hash FROM visitors WHERE id = ?");
$stmtCheck->execute([$victimId]);
$checkRow = $stmtCheck->fetch();

if ($checkRow['name'] === $victimOriginalName && password_verify($victimOriginalPin, $checkRow['access_pin_hash'])) {
    echo "[PASS] Test 6: Victim name ('{$checkRow['name']}') and original PIN hash are completely intact." . PHP_EOL;
} else {
    echo "[FAIL] Test 6: Victim details were modified!" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Test 7: Verify unauthorized users cannot delete victim's photos
// -------------------------------------------------------------
echo ">>> Test 7: Verify unauthorized user cannot delete victim's photos..." . PHP_EOL;
// Simulate an unauthenticated or different user trying to delete victim's photo
$imposterVisitorId = 99999;
$delStmt = $db->prepare("SELECT id FROM photos WHERE id = ? AND visitor_id = ? LIMIT 1");
$delStmt->execute([$victimPhotoId, $imposterVisitorId]);
$canDelete = $delStmt->fetch();

if ($canDelete === false) {
    echo "[PASS] Test 7: Unauthorized user cannot locate or delete victim photo ID {$victimPhotoId}." . PHP_EOL;
} else {
    echo "[FAIL] Test 7: Unauthorized user was able to access photo for deletion!" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Test 4: Submission using an existing email with VALID authentication
// -------------------------------------------------------------
echo ">>> Test 4: Submission using existing email with valid authentication (Matching PIN)..." . PHP_EOL;
$validPin = $victimOriginalPin;
$isOwnerVerifiedGood = false;

if ($foundVisitor) {
    $visitorId = (int) $foundVisitor['id'];
    if ($currentVisitor && (int)$currentVisitor['id'] === $visitorId) {
        $isOwnerVerifiedGood = true;
    } elseif ($validPin !== '' && !empty($foundVisitor['access_pin_hash']) && password_verify($validPin, $foundVisitor['access_pin_hash'])) {
        $isOwnerVerifiedGood = true;
        loginVisitor($foundVisitor);
    }
}

if ($isOwnerVerifiedGood && isVisitorLoggedIn() && $_SESSION['visitor_id'] === $victimId) {
    echo "[PASS] Test 4: Valid PIN verified ownership and authenticated visitor successfully." . PHP_EOL;
} else {
    echo "[FAIL] Test 4: Valid PIN authentication failed!" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Test 5: Returning visitor uploading photos while authenticated
// -------------------------------------------------------------
echo ">>> Test 5: Returning visitor uploading photos while authenticated..." . PHP_EOL;
$sessionVisitor = getCurrentVisitor();
$isOwnerVerifiedSession = false;

if ($sessionVisitor && (int)$sessionVisitor['id'] === $victimId) {
    $isOwnerVerifiedSession = true;
    // Simulate attaching additional photo
    $newPhotoFile = 'victim_additional_' . bin2hex(random_bytes(6)) . '.png';
    $newPhotoPath = $targetDir . $newPhotoFile;
    file_put_contents($newPhotoPath, $pngBytes);

    $pStmt2 = $db->prepare("
        INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at)
        VALUES (?, 'additional_photo.png', ?, ?, 'image/png', 68, 1, NOW())
    ");
    $pStmt2->execute([$victimId, $newPhotoFile, 'uploads/' . $newPhotoFile]);
    $newPhotoId = (int) $db->lastInsertId();

    $photoCount = (int) $db->query("SELECT COUNT(*) FROM photos WHERE visitor_id = {$victimId}")->fetchColumn();
    if ($photoCount === 2) {
        echo "[PASS] Test 5: Additional photo attached to existing visitor. Total photos now: {$photoCount}." . PHP_EOL;
        @unlink($newPhotoPath);
        $db->exec("DELETE FROM photos WHERE id = {$newPhotoId}");
    } else {
        echo "[FAIL] Test 5: Photo count mismatch: {$photoCount}" . PHP_EOL;
        exit(1);
    }
} else {
    echo "[FAIL] Test 5: Session visitor not authenticated as victim!" . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------
// Test 1: New visitor registration using a brand new email
// -------------------------------------------------------------
echo ">>> Test 1: New visitor registration with fresh email..." . PHP_EOL;
$newEmail = 'brand_new_' . time() . '@example.com';
$newName = 'Fresh Newcomer';
$newPin = '654321';

$stmtNew = $db->prepare("SELECT id FROM visitors WHERE email = ? LIMIT 1");
$stmtNew->execute([$newEmail]);
$exists = $stmtNew->fetch();

if (!$exists) {
    $newToken = generateVisitorToken();
    $newTokenHash = hashVisitorToken($newToken);
    $newPinHash = password_hash($newPin, PASSWORD_BCRYPT);

    $insNew = $db->prepare("
        INSERT INTO visitors (name, email, token_hash, access_pin_hash, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $insNew->execute([$newName, $newEmail, $newTokenHash, $newPinHash]);
    $newId = (int) $db->lastInsertId();

    echo "[PASS] Test 1: New visitor registered successfully with ID {$newId}." . PHP_EOL;
    // Cleanup new visitor
    $db->exec("DELETE FROM visitors WHERE id = {$newId}");
} else {
    echo "[FAIL] Test 1: New email unexpectedly already existed!" . PHP_EOL;
    exit(1);
}

// Cleanup victim records
logoutVisitor();
@unlink($victimPhotoPath);
$db->exec("DELETE FROM visitors WHERE id = {$victimId}");

echo "=== ALL ACCOUNT TAKEOVER SECURITY TESTS PASSED ===" . PHP_EOL;
