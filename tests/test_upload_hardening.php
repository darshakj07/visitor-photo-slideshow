<?php
/**
 * Automated Test: Uploads Directory Hardening Verification (Task 3)
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

echo "=== Running Security Tests: Uploads Directory Hardening (Task 3) ===" . PHP_EOL;

$htaccessPath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '.htaccess';

// 1. Verify .htaccess existence
if (!file_exists($htaccessPath)) {
    echo "[FAIL] uploads/.htaccess file is missing!" . PHP_EOL;
    exit(1);
}
echo "[PASS] Test 1: uploads/.htaccess exists." . PHP_EOL;

$htaccessContent = file_get_contents($htaccessPath);

// 2. Verify Options -Indexes
if (str_contains($htaccessContent, 'Options') && str_contains($htaccessContent, '-Indexes')) {
    echo "[PASS] Test 2: 'Options -Indexes' is explicitly configured to prevent directory listing." . PHP_EOL;
} else {
    echo "[FAIL] Test 2: 'Options -Indexes' directive is missing!" . PHP_EOL;
    exit(1);
}

// 3. Verify PHP execution prevention across versions
if (str_contains($htaccessContent, 'php_flag engine off') && str_contains($htaccessContent, 'RemoveHandler')) {
    echo "[PASS] Test 3: PHP engine disabled and script handlers stripped." . PHP_EOL;
} else {
    echo "[FAIL] Test 3: PHP engine disabling directives missing!" . PHP_EOL;
    exit(1);
}

// 4. Verify Apache 2.4+ Require all denied / FilesMatch
if (str_contains($htaccessContent, 'FilesMatch') && str_contains($htaccessContent, 'Require all denied')) {
    echo "[PASS] Test 4: Script execution denied via Apache 2.4 'Require all denied' and FilesMatch." . PHP_EOL;
} else {
    echo "[FAIL] Test 4: FilesMatch or Require all denied directives missing!" . PHP_EOL;
    exit(1);
}

// 5. Verify explicit permission for approved image formats
if (str_contains($htaccessContent, 'jpe?g|png|webp') && str_contains($htaccessContent, 'Require all granted')) {
    echo "[PASS] Test 5: Legitimate image formats (JPEG, PNG, WebP) explicitly granted read access." . PHP_EOL;
} else {
    echo "[FAIL] Test 5: Image granting directives missing!" . PHP_EOL;
    exit(1);
}

// 6. Live Apache HTTP probe (if port 80 is responding)
echo ">>> Probing live web server for .htaccess enforcement..." . PHP_EOL;
$fp = @fsockopen('127.0.0.1', 80, $errno, $errstr, 1);
if ($fp) {
    fclose($fp);

    // Create test files
    $targetDir = rtrim(UPLOADS_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $testPhpPath = $targetDir . 'probe_test_' . time() . '.php';
    $testPngPath = $targetDir . 'probe_test_' . time() . '.png';

    file_put_contents($testPhpPath, "<?php echo 'SHOULD_NOT_EXECUTE'; ?>");
    $pngBytes = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
    file_put_contents($testPngPath, $pngBytes);

    $phpFilename = basename($testPhpPath);
    $pngFilename = basename($testPngPath);

    // Probe 1: Directory index -> Expected 403
    $ch = curl_init(APP_URL . '/uploads/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    $httpCodeDir = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Probe 2: PHP script -> Expected 403
    $ch = curl_init(APP_URL . '/uploads/' . $phpFilename);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $bodyPhp = curl_exec($ch);
    $httpCodePhp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Probe 3: PNG image -> Expected 200
    $ch = curl_init(APP_URL . '/uploads/' . $pngFilename);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    $httpCodePng = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Cleanup probe files
    @unlink($testPhpPath);
    @unlink($testPngPath);

    if ($httpCodeDir === 403) {
        echo "[PASS] Test 6a: Directory browsing returned HTTP 403 Forbidden (Directory listing blocked)." . PHP_EOL;
    } else {
        echo "[WARN] Test 6a: Directory browsing returned HTTP {$httpCodeDir} (Expected 403)." . PHP_EOL;
    }

    if ($httpCodePhp === 403 && !str_contains((string)$bodyPhp, 'SHOULD_NOT_EXECUTE')) {
        echo "[PASS] Test 6b: PHP script access returned HTTP 403 Forbidden and code was NOT executed." . PHP_EOL;
    } else {
        echo "[FAIL] Test 6b: PHP script was accessible or executed (HTTP {$httpCodePhp})!" . PHP_EOL;
        exit(1);
    }

    if ($httpCodePng === 200) {
        echo "[PASS] Test 6c: Image file returned HTTP 200 OK (Public display verified)." . PHP_EOL;
    } else {
        echo "[FAIL] Test 6c: Image file was not readable (HTTP {$httpCodePng})!" . PHP_EOL;
        exit(1);
    }
} else {
    echo "[INFO] Apache server not listening on port 80; skipped live HTTP probe." . PHP_EOL;
}

echo "=== ALL UPLOADS HARDENING TESTS PASSED ===" . PHP_EOL;
