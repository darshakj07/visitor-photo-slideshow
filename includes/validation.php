<?php
/**
 * Validation Engine (Inputs & Uploaded Media)
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Validate visitor registration inputs
 */
function validateVisitorInput(array $data, bool $requireConsent = true): array
{
    $errors = [];

    // 1. Name validation
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        $errors['name'] = 'Your name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Name must be between 2 and 100 characters.';
    }

    // 2. Email validation
    $email = trim($data['email'] ?? '');
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address.';
    } elseif (strlen($email) > 190) {
        $errors['email'] = 'Email address is too long.';
    }

    // 3. Optional PIN validation (if provided, must be 4 to 8 digits)
    $pin = trim($data['pin'] ?? '');
    if ($pin !== '') {
        if (!preg_match('/^[0-9]{4,8}$/', $pin)) {
            $errors['pin'] = 'Access PIN must be between 4 and 8 numeric digits.';
        }
    }

    // 4. Consent validation
    if ($requireConsent) {
        $consent = !empty($data['consent']);
        if (!$consent) {
            $errors['consent'] = 'You must explicitly consent to displaying your photos in the public slideshow.';
        }
    }

    return $errors;
}

/**
 * Validate uploaded photo files array ($_FILES structure)
 *
 * @param array $files Normalized array of uploaded files
 * @return array Array with ['valid' => bool, 'errors' => array, 'validated_files' => array]
 */
function validateUploadedFiles(array $files): array
{
    $errors = [];
    $validated = [];

    if (empty($files) || count($files) === 0) {
        return [
            'valid' => false,
            'errors' => ['No photos were selected for upload.'],
            'validated_files' => [],
        ];
    }

    if (count($files) > MAX_FILES_PER_UPLOAD) {
        return [
            'valid' => false,
            'errors' => ['You can upload a maximum of ' . MAX_FILES_PER_UPLOAD . ' photos at a time.'],
            'validated_files' => [],
        ];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($files as $idx => $file) {
        $fileNum = $idx + 1;
        $origName = basename($file['name'] ?? "photo_{$fileNum}");

        // 1. Check upload error code
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = "Photo #{$fileNum}: Invalid upload parameter.";
            continue;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "Photo #{$fileNum} ('{$origName}') exceeds server maximum upload size limit.";
                continue 2;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = "Photo #{$fileNum} ('{$origName}') was only partially uploaded. Please try again.";
                continue 2;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = "Photo #{$fileNum}: No file was uploaded.";
                continue 2;
            default:
                $errors[] = "Photo #{$fileNum} ('{$origName}') encountered an unknown upload error (Code {$file['error']}).";
                continue 2;
        }

        // 2. File size threshold (10MB)
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            $errors[] = "Photo #{$fileNum} ('{$origName}') is an empty file.";
            continue;
        }
        if ($size > MAX_FILE_SIZE_BYTES) {
            $maxMb = formatBytes(MAX_FILE_SIZE_BYTES);
            $errors[] = "Photo #{$fileNum} ('{$origName}') exceeds the maximum allowed size of {$maxMb}.";
            continue;
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpPath)) {
            $errors[] = "Photo #{$fileNum} ('{$origName}') failed upload security check (not a valid HTTP POST upload).";
            continue;
        }

        // 3. MIME type verification via finfo (do not trust user extension)
        $mime = $finfo->file($tmpPath);
        if (!$mime || !array_key_exists($mime, ALLOWED_MIME_TYPES)) {
            $errors[] = "Photo #{$fileNum} ('{$origName}') has an unsupported format ({$mime}). Only JPEG, PNG, and WebP are accepted.";
            continue;
        }

        // 4. Genuine image content check via getimagesize()
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            $errors[] = "Photo #{$fileNum} ('{$origName}') is not a valid or readable image bitmap.";
            continue;
        }

        // 5. Generate secure random storage filename
        $extension = ALLOWED_MIME_TYPES[$mime];
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        $validated[] = [
            'original_name' => $origName,
            'stored_name'   => $storedName,
            'tmp_path'      => $tmpPath,
            'mime_type'     => $mime,
            'size'          => $size,
            'width'         => $imageInfo[0] ?? 0,
            'height'        => $imageInfo[1] ?? 0,
        ];
    }

    return [
        'valid'           => (count($errors) === 0),
        'errors'          => $errors,
        'validated_files' => $validated,
    ];
}

/**
 * Normalizes PHP's $_FILES['photos'] array into a clean sequential list of file objects
 */
function normalizeFilesArray(?array $files): array
{
    if (!$files || empty($files['name'])) {
        return [];
    }

    $normalized = [];

    // If single file upload
    if (!is_array($files['name'])) {
        return [$files];
    }

    // Multi-file upload
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        // Skip empty entries
        if (empty($files['name'][$i])) {
            continue;
        }

        $normalized[] = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
    }

    return $normalized;
}
