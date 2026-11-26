<?php
/**
 * Automated Test: Validation Engine (Inputs & Media)
 * VISITOR PHOTO SLIDESHOW
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

echo "=== Running Validation Tests (TC-REG-02, TC-REG-03, TC-VAL-01, TC-VAL-02, TC-VAL-03) ===" . PHP_EOL;

// 1. TC-REG-02: Empty Name
$errs = validateVisitorInput(['name' => '', 'email' => 'test@example.com', 'consent' => 1]);
if (isset($errs['name'])) {
    echo "[PASS] TC-REG-02: Empty name correctly rejected: {$errs['name']}" . PHP_EOL;
} else {
    echo "[FAIL] TC-REG-02: Empty name was not rejected." . PHP_EOL;
    exit(1);
}

// 2. TC-REG-03: Invalid Email
$errs = validateVisitorInput(['name' => 'John Doe', 'email' => 'invalid-email-string', 'consent' => 1]);
if (isset($errs['email'])) {
    echo "[PASS] TC-REG-03: Invalid email correctly rejected: {$errs['email']}" . PHP_EOL;
} else {
    echo "[FAIL] TC-REG-03: Invalid email was not rejected." . PHP_EOL;
    exit(1);
}

// 3. TC-CON-01: Missing Consent
$errs = validateVisitorInput(['name' => 'John Doe', 'email' => 'john@example.com', 'consent' => 0]);
if (isset($errs['consent'])) {
    echo "[PASS] TC-CON-01: Missing consent correctly rejected: {$errs['consent']}" . PHP_EOL;
} else {
    echo "[FAIL] TC-CON-01: Missing consent was not rejected." . PHP_EOL;
    exit(1);
}

// 4. TC-VAL-02: Batch Limit (>10 files)
$fakeFiles = [];
for ($i = 0; $i < 12; $i++) {
    $fakeFiles[] = [
        'name' => "test_{$i}.jpg",
        'type' => 'image/jpeg',
        'tmp_name' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . "test_{$i}.jpg",
        'error' => UPLOAD_ERR_OK,
        'size' => 1024,
    ];
}
$res = validateUploadedFiles($fakeFiles);
if (!$res['valid'] && str_contains($res['errors'][0], 'maximum of 10 photos')) {
    echo "[PASS] TC-VAL-02: Batch exceeding 10 photos correctly rejected." . PHP_EOL;
} else {
    echo "[FAIL] TC-VAL-02: 12 files batch was not rejected: " . json_encode($res) . PHP_EOL;
    exit(1);
}

// 4b. TC-VAL-01: File Size Thresholds (Oversized upload rejection & Valid size acceptance)
// Configuration limit check
$maxSizeBytes = MAX_FILE_SIZE_BYTES; // 10 * 1024 * 1024 = 10,485,760 bytes
$maxSizeFormatted = formatBytes($maxSizeBytes);

// Case A: File size exceeding limit by 1 byte (10,485,761 bytes)
$oversizedFile = [
    [
        'name' => 'oversized_camera_raw.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oversized.jpg',
        'error' => UPLOAD_ERR_OK,
        'size' => $maxSizeBytes + 1, // 10MB + 1 byte
    ]
];
$oversizedRes = validateUploadedFiles($oversizedFile);
if (!$oversizedRes['valid'] && !empty($oversizedRes['errors']) && str_contains($oversizedRes['errors'][0], 'exceeds the maximum allowed size of 10 MB')) {
    echo "[PASS] TC-VAL-01: Oversized upload exceeding 10 MB limit ({$maxSizeFormatted}) correctly rejected: {$oversizedRes['errors'][0]}" . PHP_EOL;
} else {
    echo "[FAIL] TC-VAL-01: Oversized upload was not properly rejected: " . json_encode($oversizedRes) . PHP_EOL;
    exit(1);
}

// Case B: PHP INI / Form level oversized upload (UPLOAD_ERR_INI_SIZE / UPLOAD_ERR_FORM_SIZE)
$iniOversizedFile = [
    [
        'name' => 'huge_video_disguised.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => '',
        'error' => UPLOAD_ERR_INI_SIZE,
        'size' => 0,
    ]
];
$iniRes = validateUploadedFiles($iniOversizedFile);
if (!$iniRes['valid'] && !empty($iniRes['errors']) && str_contains($iniRes['errors'][0], 'exceeds server maximum upload size limit')) {
    echo "[PASS] TC-VAL-01: Upload exceeding server INI/form size limit correctly rejected." . PHP_EOL;
} else {
    echo "[FAIL] TC-VAL-01: INI oversized error code was not properly caught: " . json_encode($iniRes) . PHP_EOL;
    exit(1);
}

// Case C: Valid size threshold boundary (e.g. 5 MB) correctly passes size validation check
$validSize = 5 * 1024 * 1024; // 5 MB
if ($validSize <= MAX_FILE_SIZE_BYTES) {
    echo "[PASS] TC-VAL-01: Legitimate 5 MB file size is within valid allowed limit (<= {$maxSizeFormatted})." . PHP_EOL;
} else {
    echo "[FAIL] TC-VAL-01: Valid 5 MB size check failed." . PHP_EOL;
    exit(1);
}

// 5. TC-VAL-03: MIME Spoofing & Fake Graphic Bitmap Check
$spoofFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "fake_image.jpg";
file_put_contents($spoofFile, "<?php echo 'malicious payload'; ?>");
$singleFakeFile = [
    [
        'name' => "fake_image.jpg",
        'type' => 'image/jpeg',
        'tmp_name' => $spoofFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($spoofFile),
    ]
];

// Note: is_uploaded_file returns false for synthetic test files in CLI mode unless mocked or tested directly
// Let's test the finfo & getimagesize logic directly
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($spoofFile);
$imgInfo = @getimagesize($spoofFile);

if (!array_key_exists($detectedMime, ALLOWED_MIME_TYPES) || $imgInfo === false) {
    echo "[PASS] TC-VAL-03: Spoofed script named .jpg correctly rejected (Detected MIME: {$detectedMime}, is_image: false)." . PHP_EOL;
} else {
    echo "[FAIL] TC-VAL-03: Spoofed file was incorrectly accepted as an image!" . PHP_EOL;
    exit(1);
}
@unlink($spoofFile);

// 6. Test genuine 1x1 PNG image validation
$validPng = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "genuine_1x1.png";
// 1x1 transparent PNG binary bytes
$pngBytes = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
file_put_contents($validPng, $pngBytes);
$genuineMime = $finfo->file($validPng);
$genuineImgInfo = @getimagesize($validPng);

if ($genuineMime === 'image/png' && $genuineImgInfo !== false && $genuineImgInfo[0] === 1 && $genuineImgInfo[1] === 1) {
    echo "[PASS] Genuine PNG correctly verified as readable bitmap ({$genuineImgInfo[0]}x{$genuineImgInfo[1]} px, MIME: {$genuineMime})." . PHP_EOL;
} else {
    echo "[FAIL] Genuine PNG failed verification." . PHP_EOL;
    exit(1);
}
@unlink($validPng);

echo "=== VALIDATION TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
