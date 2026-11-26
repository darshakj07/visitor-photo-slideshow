<?php
/**
 * Navigation Bar Component
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

$visitor = getCurrentVisitor();
$admin = getCurrentAdmin();
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <!-- Brand Logo & Name -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold font-outfit" href="<?= APP_URL ?>/index.php">
            <span class="brand-icon d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-2 shadow-sm">
                <i class="fa-solid fa-photo-film"></i>
            </span>
            <span class="tracking-tight text-white"><?= e(APP_NAME) ?></span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1 gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'index.php' ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/index.php">
                        <i class="fa-solid fa-house me-1 text-secondary"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'register.php' ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/visitor/register.php">
                        <i class="fa-solid fa-cloud-arrow-up me-1 text-secondary"></i> Share Photos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-semibold <?= $currentScript === 'slideshow' ? 'active' : '' ?>" href="<?= APP_URL ?>/slideshow/index.php" target="_blank" title="Open Slideshow in new tab">
                        <i class="fa-solid fa-display me-1"></i> Live Slideshow <i class="fa-solid fa-arrow-up-right-from-square ms-1 small"></i>
                    </a>
                </li>

                <!-- Visitor Account Status -->
                <?php if ($visitor): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-sm btn-outline-light text-start text-lg-center px-3 mt-2 mt-lg-0" href="#" id="visitorDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-circle me-1 text-info"></i> <?= e($visitor['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="visitorDropdown">
                            <li><h6 class="dropdown-header">Visitor Menu</h6></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/visitor/dashboard.php"><i class="fa-solid fa-images me-2 text-primary"></i> My Photos</a></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/visitor/register.php"><i class="fa-solid fa-plus-circle me-2 text-success"></i> Upload More</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/visitor/access.php?action=logout"><i class="fa-solid fa-right-from-bracket me-2"></i> Exit Session</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'access.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/visitor/access.php">
                            <i class="fa-solid fa-key me-1 text-secondary"></i> My Photos
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Admin Status -->
                <?php if ($admin): ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" href="<?= APP_URL ?>/admin/dashboard.php">
                            <i class="fa-solid fa-shield-halved"></i> Admin Panel
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link text-muted small" href="<?= APP_URL ?>/admin/login.php" title="Host / Admin Portal">
                            <i class="fa-solid fa-lock me-1"></i> Host
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
