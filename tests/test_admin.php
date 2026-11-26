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

echo "=== ADMIN TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
