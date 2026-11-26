<?php
/**
 * Public Slideshow Display Screen
 * TV & Projector Optimized Fullscreen Presentation
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Slideshow | <?= e(APP_NAME) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="bg-black text-white m-0 p-0 overflow-hidden">

<div id="slideshowViewport" class="slideshow-viewport">
    <!-- Top Animated Progress Bar -->
    <div id="slideshowProgressBar" class="slideshow-progress-bar"></div>

    <!-- Blurred Ambient Ambient Background -->
    <div id="slideBackdrop" class="slide-backdrop"></div>

    <!-- Foreground Display Image -->
    <div class="slide-foreground">
        <img id="slideImage" src="" alt="Slideshow Image" class="slide-image">
    </div>

    <!-- Empty State Container -->
    <div id="slideshowEmptyState" class="position-absolute top-50 start-50 translate-middle text-center p-4 d-none" style="z-index: 5;">
        <div class="p-5 rounded-4 bg-dark bg-opacity-75 border border-secondary shadow-lg">
            <i class="fa-solid fa-photo-film fa-4x text-primary mb-3"></i>
            <h2 class="fw-bold font-outfit text-white mb-2">Welcome to <?= e(APP_NAME) ?></h2>
            <p class="text-secondary mb-4">No approved photos are currently in rotation. Be the first to share your moments!</p>
            <a href="<?= APP_URL ?>/visitor/register.php" class="btn btn-primary rounded-pill px-4 py-2">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Photos
            </a>
        </div>
    </div>

    <!-- Floating Interactive HUD Overlay -->
    <div class="slideshow-hud">
        <!-- Left: Branding & Visitor Caption Badge -->
        <div class="d-flex align-items-center gap-3">
            <a href="<?= APP_URL ?>/index.php" class="text-white text-decoration-none fw-bold font-outfit small opacity-75 hover-opacity-100" title="Back to Home">
                <i class="fa-solid fa-chevron-left me-1"></i> <?= e(APP_NAME) ?>
            </a>
            <div id="hudVisitorBadge" class="hud-visitor-badge small fw-semibold text-white d-none">
                <i class="fa-solid fa-camera"></i> Photo by ...
            </div>
        </div>

        <!-- Center / Right: Playback Controls & Counter -->
        <div class="d-flex align-items-center gap-3">
            <span id="hudSlideCounter" class="badge bg-dark bg-opacity-50 text-white border border-secondary px-3 py-2 small fw-normal font-monospace">
                0 / 0
            </span>

            <div class="hud-controls d-flex align-items-center gap-2">
                <button type="button" id="hudPrev" class="btn" title="Previous Photo (Left Arrow)">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" id="hudPlayPause" class="btn" title="Pause / Play (Space)">
                    <i class="fa-solid fa-pause"></i>
                </button>
                <button type="button" id="hudNext" class="btn" title="Next Photo (Right Arrow)">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button type="button" id="hudFullscreen" class="btn" title="Toggle Fullscreen (F)">
                    <i class="fa-solid fa-expand"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Slideshow Engine Script -->
<script src="<?= APP_URL ?>/assets/js/slideshow.js"></script>
</body>
</html>
