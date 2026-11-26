<?php
/**
 * Visitor Personal Dashboard & Photo Management
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Require visitor login
$visitor = requireVisitorAuth();
$visitorId = (int) $visitor['id'];
$db = getDB();

// Handle Consent Status Updates (Grant / Withdraw)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    validateCsrfOrDie();

    if ($_POST['action'] === 'toggle_consent') {
        $newStatus = ($_POST['consent_status'] === 'granted') ? 'granted' : 'withdrawn';
        $displayName = !empty($_POST['display_name_permitted']) ? 1 : 0;

        $stmt = $db->prepare("
            INSERT INTO consent_records (visitor_id, consent_type, consent_status, display_name_permitted, ip_address, user_agent, granted_at, withdrawn_at)
            VALUES (?, 'public_slideshow', ?, ?, ?, ?, NOW(), " . ($newStatus === 'withdrawn' ? 'NOW()' : 'NULL') . ")
        ");
        $stmt->execute([
            $visitorId,
            $newStatus,
            $displayName,
            getClientIP(),
            getClientUserAgent(),
        ]);

        if ($newStatus === 'withdrawn') {
            setFlashMessage('warning', 'Consent withdrawn. Your photos have been removed from the public slideshow stream.');
        } else {
            setFlashMessage('success', 'Consent granted! Your approved photos are now active in the public slideshow.');
        }
        redirect(APP_URL . '/visitor/dashboard.php');
    }
}

// Fetch Latest Consent Record
$consentStmt = $db->prepare("
    SELECT consent_status, display_name_permitted, granted_at, withdrawn_at 
    FROM consent_records 
    WHERE visitor_id = ? 
    ORDER BY id DESC 
    LIMIT 1
");
$consentStmt->execute([$visitorId]);
$currentConsent = $consentStmt->fetch();
$isConsentActive = $currentConsent && ($currentConsent['consent_status'] === 'granted');

// Fetch All Photos for this visitor
$photoStmt = $db->prepare("
    SELECT id, original_filename, stored_filename, storage_path, mime_type, file_size, is_approved, uploaded_at 
    FROM photos 
    WHERE visitor_id = ? 
    ORDER BY uploaded_at DESC
");
$photoStmt->execute([$visitorId]);
$photos = $photoStmt->fetchAll();

$pageTitle = 'My Photo Gallery';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <!-- Profile & Access Bar -->
    <div class="custom-card p-4 mb-4">
        <div class="row align-items-center gy-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; font-size: 1.5rem;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold font-outfit mb-0 text-dark"><?= e($visitor['name']) ?></h3>
                        <div class="text-muted small">
                            <i class="fa-solid fa-envelope me-1"></i> <?= e($visitor['email']) ?> &bull; 
                            <i class="fa-solid fa-clock me-1"></i> Member since <?= date('M d, Y', strtotime($visitor['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="<?= APP_URL ?>/visitor/register.php" class="btn btn-primary rounded-pill px-3 shadow-sm me-2">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload More Photos
                </a>
                <a href="<?= APP_URL ?>/slideshow/index.php" target="_blank" class="btn btn-warning rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-tv me-1"></i> Slideshow
                </a>
            </div>
        </div>

        <!-- Private Access Key Share Box -->
        <?php if (!empty($_SESSION['visitor_token'])): ?>
            <div class="mt-4 p-3 bg-light rounded-3 border">
                <div class="row align-items-center gy-2">
                    <div class="col-lg-8">
                        <span class="badge bg-success-subtle text-success fw-semibold mb-1">
                            <i class="fa-solid fa-key me-1"></i> Your Private Access Key
                        </span>
                        <p class="mb-0 small text-muted">
                            Bookmark or save this private link to access your photo gallery anytime without a password:
                        </p>
                        <input type="text" id="privateMagicLink" class="form-control form-control-sm font-monospace mt-1" 
                               readonly value="<?= APP_URL ?>/visitor/access.php?key=<?= e($_SESSION['visitor_token']) ?>">
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-copy-target="privateMagicLink">
                            <i class="fa-solid fa-copy me-1"></i> Copy Access Link
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Main Column: Photos Grid -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold font-outfit mb-0">
                    <i class="fa-solid fa-images text-primary me-2"></i> Your Photo Gallery 
                    <span class="badge bg-secondary-subtle text-secondary fs-6 fw-normal ms-2"><?= count($photos) ?> photos</span>
                </h4>
            </div>

            <?php if (empty($photos)): ?>
                <div class="custom-card p-5 text-center text-muted">
                    <i class="fa-solid fa-image fa-3x text-secondary mb-3 opacity-50"></i>
                    <h5 class="fw-semibold">No photos found in your gallery</h5>
                    <p class="small text-muted mb-4">You have not uploaded any photos yet, or all your photos were deleted.</p>
                    <a href="<?= APP_URL ?>/visitor/register.php" class="btn btn-primary rounded-pill px-4">
                        <i class="fa-solid fa-plus me-1"></i> Upload Your First Photo
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($photos as $p): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="custom-card h-100 overflow-hidden d-flex flex-column">
                                <div class="position-relative bg-dark" style="aspect-ratio: 4/3;">
                                    <img src="<?= APP_URL . '/' . e($p['storage_path']) ?>" 
                                         alt="<?= e($p['original_filename']) ?>" 
                                         class="w-100 h-100" style="object-fit: cover;">
                                    
                                    <!-- Status Badge Overlay -->
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <?php if (!$isConsentActive): ?>
                                            <span class="badge bg-warning text-dark shadow-sm">
                                                <i class="fa-solid fa-ban me-1"></i> Consent Off
                                            </span>
                                        <?php elseif ($p['is_approved']): ?>
                                            <span class="badge bg-success shadow-sm">
                                                <i class="fa-solid fa-circle-check me-1"></i> In Slideshow
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary shadow-sm">
                                                <i class="fa-solid fa-eye-slash me-1"></i> Hidden
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Delete Button Form -->
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <form action="<?= APP_URL ?>/visitor/delete-photo.php" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to permanently delete this photo? This cannot be undone.');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="photo_id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow" 
                                                    style="width: 28px; height: 28px;" title="Delete this photo">
                                                <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="p-3 mt-auto">
                                    <div class="text-truncate fw-semibold small text-dark" title="<?= e($p['original_filename']) ?>">
                                        <?= e($p['original_filename']) ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center text-muted small mt-1">
                                        <span><?= formatBytes((int)$p['file_size']) ?></span>
                                        <span><?= date('M d, H:i', strtotime($p['uploaded_at'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column: Privacy & Consent Management -->
        <div class="col-lg-4">
            <div class="custom-card p-4 mb-4">
                <h5 class="fw-bold font-outfit mb-3 text-dark">
                    <i class="fa-solid fa-shield-halved text-success me-2"></i> Consent Settings
                </h5>

                <div class="p-3 rounded-3 mb-3 <?= $isConsentActive ? 'bg-success-subtle border border-success-subtle text-success' : 'bg-warning-subtle border border-warning-subtle text-dark' ?>">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid <?= $isConsentActive ? 'fa-circle-check fs-5 text-success' : 'fa-triangle-exclamation fs-5 text-warning' ?>"></i>
                        <h6 class="fw-bold mb-0">
                            <?= $isConsentActive ? 'Public Display Enabled' : 'Public Display Paused' ?>
                        </h6>
                    </div>
                    <p class="small mb-0 opacity-85">
                        <?= $isConsentActive 
                            ? 'Your approved photos are currently eligible to be displayed on the public event screens.' 
                            : 'Consent has been withdrawn. None of your photos will appear in the public slideshow.' ?>
                    </p>
                </div>

                <!-- Toggle Consent Form -->
                <form action="<?= APP_URL ?>/visitor/dashboard.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_consent">

                    <?php if ($isConsentActive): ?>
                        <input type="hidden" name="consent_status" value="withdrawn">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="permName" name="display_name_permitted" value="1" 
                                   <?= ($currentConsent['display_name_permitted'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="permName">
                                Allow my name to appear on photo captions
                            </label>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 rounded-3" 
                                onclick="return confirm('Withdraw consent? All your photos will immediately be taken off the public slideshow.');">
                            <i class="fa-solid fa-ban me-1"></i> Withdraw Consent
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="consent_status" value="granted">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="permName" name="display_name_permitted" value="1" checked>
                            <label class="form-check-label small" for="permName">
                                Allow my name to appear on photo captions
                            </label>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 rounded-3 text-white shadow-sm">
                            <i class="fa-solid fa-check me-1"></i> Grant Consent & Show Photos
                        </button>
                    <?php endif; ?>
                </form>

                <hr class="my-4 text-muted">

                <div class="small text-muted">
                    <h6 class="fw-semibold text-dark"><i class="fa-solid fa-info-circle me-1 text-primary"></i> Need Help?</h6>
                    <p class="mb-2">If you wish to remove all your pictures permanently from the server, delete them individually using the trash icon on each photo.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
