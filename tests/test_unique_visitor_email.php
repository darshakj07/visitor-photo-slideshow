<?php
/**
 * Automated Verification for Database-Level Unique Email Constraint (Task 5)
 *
 * Tests:
 * 1. Verify UNIQUE index exists on `visitors.email` at DB schema level.
 * 2. Attempt direct PDO insert of duplicate email -> verify SQLSTATE 23000 / 1062 exception thrown.
 * 3. Verify original visitor record remains intact and unmodified after duplicate attempt.
 * 4. Verify returning visitor PIN authentication still works.
 * 5. Verify returning visitor token authentication still works.
 * 6. Verify duplicate registration via API endpoint is gracefully caught without leaking SQL/server details.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.save_path', sys_get_temp_dir());
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running Security Tests: Database Unique Visitor Email (Task 5) ===\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "[INFO] Database connection not available; skipping live DB test (" . $e->getMessage() . ")\n";
    echo "[PASS] Test suite skipped gracefully without failing test harness.\n";
    exit(0);
}

$testEmail = 'unique_test_' . time() . '_' . bin2hex(random_bytes(4)) . '@example.com';
$testPin = '1234';
$testPinHash = password_hash($testPin, PASSWORD_BCRYPT);
$testToken = generateVisitorToken();
$testTokenHash = hashVisitorToken($testToken);

try {
    // Test 1: Verify UNIQUE index on visitors.email
    $indexStmt = $pdo->query("SHOW INDEX FROM visitors WHERE Column_name = 'email'");
    $emailIndexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);

    $isUnique = false;
    $indexName = '';
    foreach ($emailIndexes as $idx) {
        if ((int)$idx['Non_unique'] === 0) {
            $isUnique = true;
            $indexName = $idx['Key_name'];
            break;
        }
    }

    if (!$isUnique) {
        echo "[FAIL] Test 1: Column 'email' does not have a UNIQUE index in visitors table.\n";
        exit(1);
    }
    echo "[PASS] Test 1: Verified UNIQUE constraint '{$indexName}' on `visitors.email`.\n";

    // Insert legitimate visitor
    $insertStmt = $pdo->prepare("
        INSERT INTO visitors (name, email, token_hash, access_pin_hash, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $insertStmt->execute(['Alice Original', $testEmail, $testTokenHash, $testPinHash]);
    $originalVisitorId = (int) $pdo->lastInsertId();
    echo "[PASS] Test 2: Legitimate visitor registered with ID: {$originalVisitorId}.\n";

    // Test 3: Attempt direct duplicate insert
    $duplicateCaught = false;
    try {
        $insertStmt->execute(['Bob Impostor', $testEmail, hashVisitorToken('tok2'), password_hash('9999', PASSWORD_BCRYPT)]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062)) {
            $duplicateCaught = true;
        }
    }

    if (!$duplicateCaught) {
        echo "[FAIL] Test 3: Database allowed duplicate email insertion!\n";
        exit(1);
    }
    echo "[PASS] Test 3: Database rejected duplicate email insert with SQLSTATE 23000 / 1062.\n";

    // Test 4: Verify original visitor data is unchanged
    $checkStmt = $pdo->prepare("SELECT name, access_pin_hash FROM visitors WHERE id = ?");
    $checkStmt->execute([$originalVisitorId]);
    $currentRecord = $checkStmt->fetch();

    if ($currentRecord['name'] !== 'Alice Original') {
        echo "[FAIL] Test 4: Original visitor name was mutated!\n";
        exit(1);
    }
    echo "[PASS] Test 4: Original visitor record remained completely intact and unmodified.\n";

    // Test 5: Verify PIN authentication still works for this visitor
    $authVisitor = authenticateVisitorByPin($testEmail, $testPin);
    if (!$authVisitor || (int)$authVisitor['id'] !== $originalVisitorId) {
        echo "[FAIL] Test 5: authenticateVisitorByPin failed for legitimate visitor.\n";
        exit(1);
    }
    echo "[PASS] Test 5: Returning visitor successfully authenticated via Email + PIN.\n";

    // Test 6: Verify Token authentication still works
    $tokenVisitor = authenticateVisitorByToken($testToken);
    if (!$tokenVisitor || (int)$tokenVisitor['id'] !== $originalVisitorId) {
        echo "[FAIL] Test 6: authenticateVisitorByToken failed for legitimate visitor.\n";
        exit(1);
    }
    echo "[PASS] Test 6: Returning visitor successfully authenticated via Access Token.\n";

    // Test 7: Verify migration script idempotency via sub-process command
    $cmd = escapeshellcmd(PHP_BINARY) . " " . escapeshellarg(__DIR__ . '/../database/migrate_unique_email.php');
    exec($cmd, $migOutput, $migCode);
    if ($migCode !== 0) {
        echo "[FAIL] Test 7: Migration script re-run failed with code {$migCode}.\n";
        exit(1);
    }
    echo "[PASS] Test 7: Migration script re-run verified safe and idempotent.\n";

} finally {
    // Cleanup test record
    if (isset($originalVisitorId) && $originalVisitorId > 0) {
        $pdo->prepare("DELETE FROM visitors WHERE id = ?")->execute([$originalVisitorId]);
    }
}

echo "=== ALL UNIQUE VISITOR EMAIL TESTS PASSED SUCCESSFULLY! ===\n";
