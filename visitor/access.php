<?php
/**
 * Returning Visitor Access & Identification
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutVisitor();
    setFlashMessage('info', 'You have exited your visitor session.');
    redirect(APP_URL . '/index.php');
}

// Handle Direct Magic Access Link via GET parameter (?key=...)
$magicKey = trim($_GET['key'] ?? '');
if ($magicKey !== '') {
    $visitor = authenticateVisitorByToken($magicKey);
    if ($visitor) {
        setFlashMessage('success', "Welcome back, {$visitor['name']}! Your gallery has been loaded.");
        redirect(APP_URL . '/visitor/dashboard.php');
    } else {
        setFlashMessage('danger', 'Invalid or expired access key link.');
    }
}

// If already logged in, redirect to dashboard
if (isVisitorLoggedIn()) {
    redirect(APP_URL . '/visitor/dashboard.php');
}

$errors = [];

// Handle POST Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $loginType = $_POST['login_type'] ?? 'pin';

    if ($loginType === 'token') {
        $accessKey = trim($_POST['access_key'] ?? '');
        if ($accessKey === '') {
            $errors[] = 'Please enter your Access Key.';
        } else {
            $visitor = authenticateVisitorByToken($accessKey);
            if ($visitor) {
                setFlashMessage('success', "Welcome back, {$visitor['name']}!");
                redirect(APP_URL . '/visitor/dashboard.php');
            } else {
                $errors[] = 'Invalid Access Key. Please check the key and try again.';
            }
        }
    } else {
        // Email + PIN login
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pin = trim($_POST['pin'] ?? '');

        if ($email === '') {
            $errors[] = 'Email address is required.';
        }
        if ($pin === '') {
            $errors[] = 'Access PIN is required.';
        }

        if (empty($errors)) {
            $visitor = authenticateVisitorByPin($email, $pin);
            if ($visitor) {
                setFlashMessage('success', "Welcome back, {$visitor['name']}!");
                redirect(APP_URL . '/visitor/dashboard.php');
            } else {
                $errors[] = 'Invalid email or PIN. If you did not create a PIN during upload, please use your Access Key.';
            }
        }
    }
}

$pageTitle = 'Returning Visitor Access';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="text-center mb-4">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
                    <i class="fa-solid fa-key me-1"></i> Secure Identification
                </span>
                <h2 class="fw-bold font-outfit">Access Your Photos</h2>
                <p class="text-muted">Retrieve your uploaded photos, upload more, or manage display permissions.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <?= implode('<br>', array_map('e', $errors)) ?>
                </div>
            <?php endif; ?>

            <div class="custom-card p-4 p-md-5">
                <!-- Nav Tabs for Login Methods -->
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-pill border" id="accessTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-semibold" id="pin-tab" data-bs-toggle="pill" data-bs-target="#pinTabContent" type="button" role="tab">
                            <i class="fa-solid fa-lock me-1"></i> Email + PIN
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold" id="token-tab" data-bs-toggle="pill" data-bs-target="#tokenTabContent" type="button" role="tab">
                            <i class="fa-solid fa-link me-1"></i> Access Key
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="accessTabsContent">
                    <!-- Tab 1: Email + PIN Form -->
                    <div class="tab-pane fade show active" id="pinTabContent" role="tabpanel">
                        <form action="<?= APP_URL ?>/visitor/access.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="login_type" value="pin">

                            <div class="mb-3">
                                <label for="loginEmail" class="form-label fw-semibold">Your Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="loginEmail" name="email" required placeholder="name@example.com">
                            </div>

                            <div class="mb-4">
                                <label for="loginPin" class="form-label fw-semibold">Your 4-8 Digit Access PIN</label>
                                <input type="password" class="form-control form-control-lg" id="loginPin" name="pin" required placeholder="e.g. 123456" maxlength="8">
                                <div class="form-text">The PIN you entered when first submitting photos.</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-3 rounded-3 shadow">
                                    <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Access My Gallery
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Access Key Form -->
                    <div class="tab-pane fade" id="tokenTabContent" role="tabpanel">
                        <form action="<?= APP_URL ?>/visitor/access.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="login_type" value="token">

                            <div class="mb-4">
                                <label for="accessKey" class="form-label fw-semibold">Private Access Key</label>
                                <input type="text" class="form-control form-control-lg font-monospace" id="accessKey" name="access_key" required placeholder="64-character hexadecimal key">
                                <div class="form-text">Paste the private access key provided during your upload.</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-3 rounded-3 shadow">
                                    <i class="fa-solid fa-key me-2"></i> Verify Key & Open Gallery
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- New Visitor Link -->
            <div class="text-center mt-4">
                <span class="text-muted">New visitor?</span>
                <a href="<?= APP_URL ?>/visitor/register.php" class="fw-semibold ms-1">
                    <i class="fa-solid fa-camera me-1"></i> Register & upload your first photos
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
