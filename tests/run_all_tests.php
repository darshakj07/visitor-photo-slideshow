<?php
/**
 * Master Test Suite Runner
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

echo "=========================================================" . PHP_EOL;
echo "  VISITOR PHOTO SLIDESHOW - AUTOMATED TEST SUITE RUNNER  " . PHP_EOL;
echo "=========================================================" . PHP_EOL . PHP_EOL;

$testFiles = [
    'test_database.php',
    'test_validation.php',
    'test_visitor_workflow.php',
    'test_admin.php',
    'test_concurrency.php',
];

$passedCount = 0;
$failedCount = 0;

foreach ($testFiles as $file) {
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $file;
    echo ">>> Executing {$file}..." . PHP_EOL;
    $cmd = escapeshellcmd(PHP_BINARY) . " " . escapeshellarg($fullPath);
    exec($cmd, $output, $returnCode);

    foreach ($output as $line) {
        echo "    " . $line . PHP_EOL;
    }

    if ($returnCode === 0) {
        echo "[RESULT] {$file}: PASS" . PHP_EOL . PHP_EOL;
        $passedCount++;
    } else {
        echo "[RESULT] {$file}: FAIL (Exit Code: {$returnCode})" . PHP_EOL . PHP_EOL;
        $failedCount++;
    }
    unset($output);
}

echo "=========================================================" . PHP_EOL;
echo "Test Execution Summary: {$passedCount} Passed, {$failedCount} Failed out of " . count($testFiles) . " test suites." . PHP_EOL;
echo "=========================================================" . PHP_EOL;

exit($failedCount > 0 ? 1 : 0);
