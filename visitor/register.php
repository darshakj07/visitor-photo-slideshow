<?php
/**
 * Visitor Registration & Photo Submission
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Share Your Photos - Visitor Registration';
$extraScripts = [APP_URL . '/assets/js/upload.js'];
$currentVisitor = getCurrentVisitor();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header Title -->
            <div class="text-center mb-4">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
                    <i class="fa-solid fa-camera-retro me-1"></i> Public Photo Wall
                </span>
                <h2 class="fw-bold font-outfit">Share Your Event Highlights</h2>
                <p class="text-muted">Upload your photos to be featured immediately on the venue's live public slideshow screen.</p>
            </div>

            <!-- Upload Alert Box for Validation -->
            <div id="uploadAlert" class="alert d-none" role="alert"></div>

            <div class="custom-card p-4 p-md-5 mb-4">
                <form id="uploadForm" action="<?= APP_URL ?>/api/upload.php" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>

                    <h5 class="fw-bold font-outfit mb-3 text-dark">
                        <i class="fa-solid fa-user text-primary me-2"></i> 1. Visitor Details
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="visitorName" class="form-label fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="visitorName" name="name" 
                                   placeholder="e.g. Alex Morgan" required 
                                   value="<?= e($currentVisitor['name'] ?? '') ?>">
                            <div class="form-text">This name may appear as a photo caption on the screen if you permit it.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="visitorEmail" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-lg" id="visitorEmail" name="email" 
                                   placeholder="name@example.com" required 
                                   value="<?= e($currentVisitor['email'] ?? '') ?>" 
                                   <?= $currentVisitor ? 'readonly' : '' ?>>
                            <div class="form-text">Never displayed publicly. Used to organize your personal photo gallery.</div>
                        </div>

                        <div class="col-md-12">
                            <label for="visitorPin" class="form-label fw-semibold">
                                Create an Access PIN <span class="text-muted fw-normal">(Optional, 4-8 digits)</span>
                            </label>
                            <input type="password" class="form-control" id="visitorPin" name="pin" 
                                   placeholder="e.g. 123456" maxlength="8" pattern="[0-9]{4,8}">
                            <div class="form-text">
                                <i class="fa-solid fa-lock me-1 text-secondary"></i>
                                If you want to view, add, or delete your photos later from your phone or another device, remember this PIN!
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">

                    <h5 class="fw-bold font-outfit mb-2 text-dark d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-images text-primary me-2"></i> 2. Select Photos</span>
                        <span id="fileCountBadge" class="badge bg-secondary-subtle text-secondary fw-normal">0 / 10 photos selected</span>
                    </h5>
                    <p class="text-muted small mb-3">Accepts JPEG, PNG, and WebP up to 10 MB each. You can select up to 10 photos in one submission.</p>

                    <!-- Dropzone -->
                    <div id="dropzone" class="dropzone-container mb-3">
                        <div class="dropzone-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <h6 class="fw-semibold mb-1">Click to browse or drag and drop your photos here</h6>
                        <span class="text-muted small">Max 10 MB per photo &bull; Up to 10 photos</span>
                        <input type="file" id="photoInput" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="d-none">
                    </div>

                    <!-- Dynamic Previews Container -->
                    <div id="previewGrid" class="preview-grid mb-4"></div>

                    <hr class="my-4 text-muted">

                    <!-- Consent and Privacy Section -->
                    <h5 class="fw-bold font-outfit mb-3 text-dark">
                        <i class="fa-solid fa-shield-halved text-success me-2"></i> 3. Privacy & Public Display Consent
                    </h5>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check">
                            <!-- Mandatory: Explicit consent checkbox NOT preselected -->
                            <input class="form-check-input" type="checkbox" id="consentCheckbox" name="consent" value="1" required>
                            <label class="form-check-label fw-semibold text-dark" for="consentCheckbox">
                                I explicitly consent to displaying my uploaded photos publicly on the event slideshow screen. <span class="text-danger">*</span>
                            </label>
                        </div>
                        <div class="form-check mt-2 ms-4">
                            <input class="form-check-input" type="checkbox" id="nameDisplayCheckbox" name="display_name_permitted" value="1" checked>
                            <label class="form-check-label text-muted small" for="nameDisplayCheckbox">
                                Display my name as a photo attribution caption alongside my pictures.
                            </label>
                        </div>
                        <div class="mt-2 ms-4 small text-muted">
                            <i class="fa-solid fa-circle-info me-1"></i> You retain full ownership and can withdraw consent or delete any of your photos at any time via your personal Visitor Dashboard.
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-4">
                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg py-3 rounded-3 shadow">
                            <i class="fa-solid fa-paper-plane me-2"></i> Upload & Join Public Slideshow
                        </button>
                    </div>
                </form>
            </div>

            <!-- Returning Visitor Notice -->
            <div class="text-center text-muted small">
                Already submitted photos? 
                <a href="<?= APP_URL ?>/visitor/access.php" class="fw-semibold text-primary">
                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Access your previous photos here
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
