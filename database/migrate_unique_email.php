<?php
/**
 * Safe Database Migration: Enforce Unique Visitor Email Constraint
 *
 * Pre-checks:
 * 1. Verifies database connectivity.
 * 2. Checks if duplicate emails exist in the visitors table.
 *    If duplicates exist: aborts migration safely without modifying any records.
 * 3. Inspects current indexes on the visitors table:
 *    - If idx_unique_email already exists, reports already up-to-date.
 *    - Drops old non-unique idx_email index if present.
 *    - Adds UNIQUE KEY idx_unique_email (email).
 *
 * Usage:
 *   php database/migrate_unique_email.php
 */

declare(strict_types=1);

// Restrict execution exclusively to the CLI environment
if (PHP_SAPI !== 'cli' && php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 Forbidden: Database migration scripts can only be executed via the PHP command-line interface (CLI).\n";
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.save_path', sys_get_temp_dir());
}

require_once __DIR__ . '/../config/database.php';

echo "=========================================================\n";
echo "  DATABASE MIGRATION: ENFORCE UNIQUE VISITOR EMAIL       \n";
echo "=========================================================\n\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "[ABORT] Could not connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 1: Pre-flight check for duplicate emails
echo "1. Performing pre-flight audit for duplicate visitor emails...\n";
$stmt = $pdo->query("SELECT email, COUNT(*) as cnt FROM visitors GROUP BY email HAVING cnt > 1");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicates)) {
    echo "\n[ERROR - MIGRATION ABORTED]: Found duplicate emails in visitors table:\n";
    foreach ($duplicates as $row) {
        echo "   - Email: {$row['email']} (Count: {$row['cnt']})\n";
    }
    echo "\nPer project policy, records will NOT be silently deleted or altered.\n";
    echo "Please resolve these duplicate records before applying this migration.\n";
    exit(1);
}

echo "   [OK] No duplicate emails found. Proceeding.\n\n";

// Step 2: Inspect existing indexes on visitors table
echo "2. Inspecting current indexes on 'visitors' table...\n";
$indexStmt = $pdo->query("SHOW INDEX FROM visitors");
$existingIndexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);

$hasUniqueEmail = false;
$hasOldNonUniqueEmail = false;

foreach ($existingIndexes as $idx) {
    if ($idx['Column_name'] === 'email') {
        if ((int)$idx['Non_unique'] === 0) {
            $hasUniqueEmail = true;
            echo "   - Found existing UNIQUE constraint: {$idx['Key_name']} on `email`\n";
        } else {
            $hasOldNonUniqueEmail = true;
            echo "   - Found non-unique index: {$idx['Key_name']} on `email`\n";
        }
    }
}

if ($hasUniqueEmail) {
    echo "\n[INFO] visitors table already possesses a UNIQUE email constraint.\n";
    echo "No further schema changes needed.\n";
    exit(0);
}

// Step 3: Apply the UNIQUE constraint
echo "\n3. Applying database migration...\n";

// Drop non-unique idx_email if it exists
if ($hasOldNonUniqueEmail) {
    echo "   - Dropping legacy non-unique index `idx_email`...\n";
    $pdo->exec("ALTER TABLE visitors DROP INDEX idx_email");
}

echo "   - Adding UNIQUE KEY `idx_unique_email` (`email`)...\n";
$pdo->exec("ALTER TABLE visitors ADD UNIQUE KEY `idx_unique_email` (`email`)");

echo "\n4. Verifying newly applied index structure...\n";
$verifyStmt = $pdo->query("SHOW INDEX FROM visitors WHERE Column_name = 'email'");
$applied = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

$verifiedUnique = false;
foreach ($applied as $idx) {
    if ((int)$idx['Non_unique'] === 0) {
        $verifiedUnique = true;
        echo "   [CONFIRMED] Key `{$idx['Key_name']}` on column `{$idx['Column_name']}` is UNIQUE (Non_unique: 0).\n";
    }
}

if ($verifiedUnique) {
    echo "\n[SUCCESS] Migration completed successfully. Email uniqueness is strictly enforced at database level.\n";
    exit(0);
} else {
    echo "\n[ERROR] Migration verification failed.\n";
    exit(1);
}
