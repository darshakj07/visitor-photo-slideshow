<?php
/**
 * Modern Landing Page
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home - Live Event Photo Wall';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
                    <i class="fa-solid fa-sparkles me-1"></i> Interactive Event Photo Wall
                </span>
                <h1 class="display-4 fw-extrabold font-outfit mb-3">
                    Celebrate Moments Together in Real-Time
                </h1>
                <p class="lead text-light opacity-75 mb-4 px-md-5">
                    Welcome to <strong><?= e(APP_NAME) ?></strong>. Snap or select your favorite event photos, upload them seamlessly, and watch them appear on the venue's live public slideshow screen!
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= APP_URL ?>/visitor/register.php" class="btn btn-primary btn-lg px-4 py-3 rounded-pill shadow">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Share Your Photos
                    </a>
                    <a href="<?= APP_URL ?>/slideshow/index.php" target="_blank" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">
                        <i class="fa-solid fa-display me-2 text-warning"></i> View Live Slideshow
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase tracking-wider">Simple & Seamless</h6>
            <h2 class="fw-bold font-outfit">How It Works</h2>
            <p class="text-muted">Three effortless steps to showcase your event highlights.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="custom-card p-4 h-100 text-center">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; font-size: 1.5rem;">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h4 class="fw-bold font-outfit mb-2">1. Register & Select</h4>
                    <p class="text-muted mb-0">Enter your name and email. Pick up to 10 photos from your mobile device or computer with instant previews.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="custom-card p-4 h-100 text-center">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; font-size: 1.5rem;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h4 class="fw-bold font-outfit mb-2">2. Explicit Consent</h4>
                    <p class="text-muted mb-0">Confirm your consent for public screen display. Your privacy remains under your control with complete withdrawal rights.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="custom-card p-4 h-100 text-center">
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; font-size: 1.5rem;">
                        <i class="fa-solid fa-tv"></i>
                    </div>
                    <h4 class="fw-bold font-outfit mb-2">3. Watch on Screen</h4>
                    <p class="text-muted mb-0">Your photos are verified and instantly projected onto the ambient venue slideshow for all attendees to enjoy!</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h6 class="text-primary fw-bold text-uppercase tracking-wider">Built for Live Demonstrations</h6>
                <h2 class="fw-bold font-outfit mb-3">Engineered with Precision & Security</h2>
                <p class="text-secondary mb-4">
                    Designed specifically for MCA Assignment 1 using pure Core PHP 8.2, MySQL PDO prepared statements, and modern responsive design without heavy frameworks.
                </p>
                <div class="vstack gap-3">
                    <div class="d-flex gap-3">
                        <div class="text-primary fs-4"><i class="fa-solid fa-arrows-rotate"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Returning Visitor Memory</h5>
                            <p class="text-muted small mb-0">Recognizes returning visitors via high-entropy cryptographic keys and optional PINs, aggregating all your photos under your personal gallery.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="text-success fs-4"><i class="fa-solid fa-bolt"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Concurrent Upload Engine</h5>
                            <p class="text-muted small mb-0">Atomically manages simultaneous multi-visitor uploads with random filename generation and zero-disk collision.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="text-warning fs-4"><i class="fa-solid fa-tv"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Smart TV & Projector Fullscreen</h5>
                            <p class="text-muted small mb-0">Smooth 8-second crossfades, ambient blurred backdrop, and keyboard navigation (Space, Arrows, Fullscreen F).</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="custom-card p-4 shadow-lg border-0 bg-dark text-white text-center">
                    <div class="p-4 border border-secondary border-dashed rounded-3">
                        <i class="fa-solid fa-images fa-4x text-primary mb-3"></i>
                        <h4 class="font-outfit fw-bold">Live Public Stream</h4>
                        <p class="text-secondary small mb-4">Photos from all consented attendees cycle automatically every 8 seconds.</p>
                        <a href="<?= APP_URL ?>/slideshow/index.php" target="_blank" class="btn btn-warning fw-semibold px-4 py-2 rounded-pill">
                            <i class="fa-solid fa-play me-1"></i> Launch Slideshow Screen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Privacy & Consent Explanation Section -->
<section id="privacy" class="py-5 bg-white border-top">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-4">
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold mb-2">
                    <i class="fa-solid fa-shield-halved me-1"></i> Privacy First Policy
                </span>
                <h2 class="fw-bold font-outfit">Your Photos, Your Control</h2>
                <p class="text-muted">
                    We believe in complete transparency and consent sovereignty. Here is our solemn commitment to you:
                </p>
            </div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-check-double text-success me-2"></i>Explicit, Opt-In Consent</h6>
                    <p class="text-secondary small mb-0">The consent checkbox is never pre-selected. You choose whether your photos appear publicly on venue screens.</p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-eye-slash text-danger me-2"></i>No Public Email Exposure</h6>
                    <p class="text-secondary small mb-0">Your email address is strictly used for identification and is never visible on the slideshow or any public API.</p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-ban text-warning me-2"></i>Instant Consent Withdrawal</h6>
                    <p class="text-secondary small mb-0">Changed your mind? Click "Withdraw Consent" in your Visitor Dashboard to instantly remove all your photos from the slideshow.</p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-trash-can text-danger me-2"></i>Permanent Deletion Rights</h6>
                    <p class="text-secondary small mb-0">You can delete any of your photos at any time. When deleted, the image file is unlinked immediately from server storage.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
