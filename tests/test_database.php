<?php
/**
 * Automated Test: Database Connection & Integrity
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Running TC-ENV-01: Database Connectivity ===" . PHP_EOL;

try {
    $db = getDB();
    $version = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
    echo "[PASS] Successfully connected to Database: {$version}" . PHP_EOL;

    // Check tables exist
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['visitors', 'photos', 'consent_records', 'admins', 'slideshow_settings'];
    $diff = array_diff($required, $tables);

    if (empty($diff)) {
        echo "[PASS] All 5 required schema tables exist: " . implode(', ', $tables) . PHP_EOL;
    } else {
        echo "[FAIL] Missing tables: " . implode(', ', $diff) . PHP_EOL;
        exit(1);
    }

    // Check admin exists
    $adminCount = (int) $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($adminCount > 0) {
        echo "[PASS] Default administrator account exists ({$adminCount} admin(s))." . PHP_EOL;
    } else {
        echo "[FAIL] No administrator account found." . PHP_EOL;
        exit(1);
    }

} catch (Exception $e) {
    echo "[FAIL] Database test error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "=== TC-ENV-01 COMPLETED SUCCESSFULLY ===" . PHP_EOL;
