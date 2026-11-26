<?php
/**
 * Host / Admin Login
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        if (attemptAdminLogin($username, $password)) {
            setFlashMessage('success', 'Logged in successfully as Administrator.');
            redirect(APP_URL . '/admin/dashboard.php');
        } else {
            $error = 'Invalid administrative credentials.';
        }
    }
}

$pageTitle = 'Host / Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="text-center mb-4">
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold mb-2">
                    <i class="fa-solid fa-shield-halved me-1"></i> Host Area
                </span>
                <h3 class="fw-bold font-outfit">Host / Admin Authentication</h3>
                <p class="text-muted small">Sign in to manage visitors, moderate photos, and control the slideshow.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger shadow-sm">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="custom-card p-4 p-md-5">
                <form action="<?= APP_URL ?>/admin/login.php" method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="adminUsername" class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control form-control-lg" id="adminUsername" name="username" required placeholder="admin">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="adminPassword" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control form-control-lg" id="adminPassword" name="password" required placeholder="••••••••">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg py-3 rounded-3 shadow">
                            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Sign In to Host Panel
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3 pt-3 border-top">
                    <span class="text-muted small">Default Credentials: <code>admin</code> / <code>Admin@2026!</code></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
