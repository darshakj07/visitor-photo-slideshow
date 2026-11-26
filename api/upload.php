<?php
/**
 * Photo Upload & Visitor Registration API Endpoint
 * Handles atomic concurrent uploads, validation, and database ingestion
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
}

// 1. Validate CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page.'], 403);
}

// 2. Validate Visitor Input
$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$pin = trim($_POST['pin'] ?? '');
$consent = !empty($_POST['consent']);
$displayNamePermitted = !empty($_POST['display_name_permitted']) ? 1 : 0;

$inputErrors = validateVisitorInput([
    'name'    => $name,
    'email'   => $email,
    'pin'     => $pin,
    'consent' => $consent,
], true);

if (!empty($inputErrors)) {
    jsonResponse(['success' => false, 'errors' => array_values($inputErrors)], 422);
}

// 3. Normalize and Validate Files
$rawFiles = normalizeFilesArray($_FILES['photos'] ?? null);
$fileValidation = validateUploadedFiles($rawFiles);

if (!$fileValidation['valid']) {
    jsonResponse(['success' => false, 'errors' => $fileValidation['errors']], 422);
}

$validatedPhotos = $fileValidation['validated_files'];
$db = getDB();
$movedFiles = []; // Track moved files for cleanup in case of rollback

try {
    // 4. Begin Atomic Database Transaction
    $db->beginTransaction();

    // Check if requester is already authenticated in this session
    $currentVisitor = getCurrentVisitor();

    // Check if visitor already exists by email
    $stmt = $db->prepare("SELECT id, name, email, token_hash, access_pin_hash FROM visitors WHERE email = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$email]);
    $visitor = $stmt->fetch();

    $plaintextToken = '';

    if ($visitor) {
        $visitorId = (int) $visitor['id'];
        $isOwnerVerified = false;

        // Verify ownership:
        // Case 1: Requester is already logged in as this exact visitor
        if ($currentVisitor && (int)$currentVisitor['id'] === $visitorId) {
            $isOwnerVerified = true;
        } elseif ($pin !== '' && !empty($visitor['access_pin_hash']) && password_verify($pin, $visitor['access_pin_hash'])) {
            // Case 2: Unauthenticated requester provided matching PIN for this account
            $isOwnerVerified = true;
            loginVisitor($visitor);
        }

        // Reject if ownership cannot be proven (prevents account takeover)
        if (!$isOwnerVerified) {
            $db->rollBack();
            jsonResponse([
                'success'      => false,
                'error'        => 'This email address is already registered. Please <a href="' . APP_URL . '/visitor/access.php" class="alert-link">sign in with your Access Key or PIN</a> to manage or add photos.',
                'redirect_url' => APP_URL . '/visitor/access.php',
            ], 403);
        }

        // Ownership is verified: Never overwrite existing name, token, or PIN
        $updateStmt = $db->prepare("UPDATE visitors SET updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$visitorId]);
    } else {
        // Create new visitor with high-entropy token
        $plaintextToken = generateVisitorToken();
        $tokenHash = hashVisitorToken($plaintextToken);
        $pinHash = ($pin !== '') ? password_hash($pin, PASSWORD_BCRYPT) : null;

        $insertVisitor = $db->prepare("
            INSERT INTO visitors (name, email, token_hash, access_pin_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $insertVisitor->execute([$name, $email, $tokenHash, $pinHash]);
        $visitorId = (int) $db->lastInsertId();

        $visitor = [
            'id'         => $visitorId,
            'name'       => $name,
            'email'      => $email,
            'token_hash' => $tokenHash,
        ];
    }

    // 5. Record Explicit Consent Audit Log
    $consentStmt = $db->prepare("
        INSERT INTO consent_records (visitor_id, consent_type, consent_status, display_name_permitted, ip_address, user_agent, granted_at)
        VALUES (?, 'public_slideshow', 'granted', ?, ?, ?, NOW())
    ");
    $consentStmt->execute([
        $visitorId,
        $displayNamePermitted,
        getClientIP(),
        getClientUserAgent(),
    ]);

    // 6. Move Validated Files and Insert Photo Records
    $photoStmt = $db->prepare("
        INSERT INTO photos (visitor_id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $targetDir = rtrim(UPLOADS_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    foreach ($validatedPhotos as $photo) {
        $destination = $targetDir . $photo['stored_name'];
        $relativeStoragePath = 'uploads/' . $photo['stored_name'];

        if (!move_uploaded_file($photo['tmp_path'], $destination)) {
            throw new RuntimeException("Failed to store file '{$photo['original_name']}' safely on disk.");
        }

        $movedFiles[] = $destination;

        $photoStmt->execute([
            $visitorId,
            $photo['original_name'],
            $photo['stored_name'],
            $relativeStoragePath,
            $photo['mime_type'],
            $photo['size'],
        ]);
    }

    // 7. Commit Database Transaction
    $db->commit();

    // 8. Establish Visitor Session
    loginVisitor($visitor, $plaintextToken);

    setFlashMessage('success', count($validatedPhotos) . ' photos uploaded successfully and added to the public slideshow!');

    jsonResponse([
        'success'      => true,
        'message'      => count($validatedPhotos) . ' photos uploaded successfully!',
        'count'        => count($validatedPhotos),
        'visitor_id'   => $visitorId,
        'access_key'   => $plaintextToken,
        'redirect_url' => APP_URL . '/visitor/dashboard.php',
    ], 200);

} catch (Throwable $e) {
    // Rollback DB transaction
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Disk Cleanup: delete any partially moved files
    foreach ($movedFiles as $filepath) {
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }

    // Check for duplicate email database constraint violation (SQLSTATE 23000 / MySQL error 1062)
    if ($e instanceof PDOException && ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062))) {
        error_log("Duplicate visitor registration caught by database constraint for email: {$email}");
        jsonResponse([
            'success'      => false,
            'error'        => 'This email address is already registered. Please <a href="' . APP_URL . '/visitor/access.php" class="alert-link">sign in with your Access Key or PIN</a> to manage or add photos.',
            'redirect_url' => APP_URL . '/visitor/access.php',
        ], 409);
    }

    error_log("Upload processing failure: " . $e->getMessage());

    $errorMessage = APP_DEBUG 
        ? ("Upload processing error: " . $e->getMessage()) 
        : "Failed to process photos due to a server error. Please try again.";

    jsonResponse(['success' => false, 'error' => $errorMessage], 500);
}
