<?php
/**
 * Host / Admin Main Dashboard
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

$admin = requireAdminAuth();
$db = getDB();

// Handle Slideshow Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    validateCsrfOrDie();

    $interval = max(3, min(60, (int) ($_POST['slideshow_interval'] ?? 8)));
    $showName = !empty($_POST['show_visitor_name']) ? '1' : '0';

    try {
        $stmt = $db->prepare("REPLACE INTO slideshow_settings (setting_key, setting_value, updated_at) VALUES ('slideshow_interval', ?, NOW()), ('show_visitor_name', ?, NOW())");
        $stmt->execute([(string)$interval, $showName]);
        setFlashMessage('success', 'Slideshow settings updated successfully.');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to update settings: ' . $e->getMessage());
    }
    redirect(APP_URL . '/admin/dashboard.php');
}

// 1. Compute Dashboard Metrics
$totalVisitors = (int) $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
$totalPhotos = (int) $db->query("SELECT COUNT(*) FROM photos")->fetchColumn();

// Active consents (latest consent status is 'granted')
$activeConsents = (int) $db->query("
    SELECT COUNT(*) FROM (
        SELECT cr1.visitor_id, cr1.consent_status
        FROM consent_records cr1
        INNER JOIN (
            SELECT visitor_id, MAX(id) AS max_id
            FROM consent_records
            GROUP BY visitor_id
        ) cr2 ON cr1.id = cr2.max_id
        WHERE cr1.consent_status = 'granted'
    ) active_c
")->fetchColumn();

// Total photos actively eligible for public slideshow
$activeSlideshowPhotos = (int) $db->query("
    SELECT COUNT(*) 
    FROM photos p
    INNER JOIN (
        SELECT cr1.visitor_id, cr1.consent_status
        FROM consent_records cr1
        INNER JOIN (
            SELECT visitor_id, MAX(id) AS max_id
            FROM consent_records
            GROUP BY visitor_id
        ) cr2 ON cr1.id = cr2.max_id
        WHERE cr1.consent_status = 'granted'
    ) c ON p.visitor_id = c.visitor_id
    WHERE p.is_approved = 1
")->fetchColumn();

// Current Settings
$currentInterval = (int) getSlideshowSetting('slideshow_interval', '8');
$currentShowName = (bool) getSlideshowSetting('show_visitor_name', '1');

// 2. Fetch Recent Photos
$recentPhotosStmt = $db->query("
    SELECT p.id, p.visitor_id, p.original_filename, p.storage_path, p.file_size, p.is_approved, p.uploaded_at,
           v.name AS visitor_name, v.email AS visitor_email
    FROM photos p
    LEFT JOIN visitors v ON p.visitor_id = v.id
    ORDER BY p.uploaded_at DESC
    LIMIT 10
");
$recentPhotos = $recentPhotosStmt->fetchAll();

$pageTitle = 'Host Administration Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold mb-1">
                <i class="fa-solid fa-shield-halved me-1"></i> Event Host Administration
            </span>
            <h2 class="fw-bold font-outfit mb-0">Event Overview & Moderation</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= APP_URL ?>/slideshow/index.php" target="_blank" class="btn btn-warning rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-display me-1"></i> Live Slideshow
            </a>
            <a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-outline-danger rounded-pill px-3">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Navigation Pills -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-sm">
        <li class="nav-item">
            <a class="nav-link active" href="<?= APP_URL ?>/admin/dashboard.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= APP_URL ?>/admin/photos.php"><i class="fa-solid fa-images me-1"></i> Photo Moderation</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= APP_URL ?>/admin/visitors.php"><i class="fa-solid fa-users me-1"></i> Visitors List</a>
        </li>
    </ul>

    <!-- 4 Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="custom-card p-4 border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Visitors</div>
                        <h2 class="fw-bold font-outfit mb-0 mt-1"><?= $totalVisitors ?></h2>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3">
                        <i class="fa-solid fa-users fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="custom-card p-4 border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Uploads</div>
                        <h2 class="fw-bold font-outfit mb-0 mt-1"><?= $totalPhotos ?></h2>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info p-3">
                        <i class="fa-solid fa-photo-film fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="custom-card p-4 border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Active Consents</div>
                        <h2 class="fw-bold font-outfit mb-0 mt-1"><?= $activeConsents ?></h2>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success p-3">
                        <i class="fa-solid fa-shield-check fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="custom-card p-4 border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Live on Screen</div>
                        <h2 class="fw-bold font-outfit mb-0 mt-1 text-warning"><?= $activeSlideshowPhotos ?></h2>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning p-3">
                        <i class="fa-solid fa-tv fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main: Recent Submissions Table -->
        <div class="col-lg-8">
            <div class="custom-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-outfit mb-0">Recent Uploads</h5>
                    <a href="<?= APP_URL ?>/admin/photos.php" class="small fw-semibold">View All Photos &rarr;</a>
                </div>

                <?php if (empty($recentPhotos)): ?>
                    <p class="text-muted text-center py-4 mb-0">No photos have been uploaded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Photo</th>
                                    <th>Visitor</th>
                                    <th>Status</th>
                                    <th>Uploaded</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPhotos as $rp): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= APP_URL . '/' . e($rp['storage_path']) ?>" 
                                                 alt="Thumb" class="rounded object-fit-cover" 
                                                 style="width: 48px; height: 48px;">
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($rp['visitor_name'] ?? 'Unknown') ?></div>
                                            <div class="text-muted small"><?= e($rp['visitor_email'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <?php if ($rp['is_approved']): ?>
                                                <span class="badge bg-success-subtle text-success">Approved</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Hidden</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('M d, H:i', strtotime($rp['uploaded_at'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <!-- Toggle Approval Form -->
                                                <form action="<?= APP_URL ?>/admin/actions/toggle-approval.php" method="POST">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="photo_id" value="<?= (int) $rp['id'] ?>">
                                                    <input type="hidden" name="return_url" value="<?= APP_URL ?>/admin/dashboard.php">
                                                    <button type="submit" class="btn btn-sm <?= $rp['is_approved'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" 
                                                            title="<?= $rp['is_approved'] ? 'Hide from slideshow' : 'Approve for slideshow' ?>">
                                                        <i class="fa-solid <?= $rp['is_approved'] ? 'fa-eye-slash' : 'fa-check' ?>"></i>
                                                    </button>
                                                </form>

                                                <!-- Delete Photo Form -->
                                                <form action="<?= APP_URL ?>/admin/actions/delete-photo.php" method="POST" 
                                                      onsubmit="return confirm('Permanently delete this photo?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="photo_id" value="<?= (int) $rp['id'] ?>">
                                                    <input type="hidden" name="return_url" value="<?= APP_URL ?>/admin/dashboard.php">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently delete">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: Slideshow Live Settings Controls -->
        <div class="col-lg-4">
            <div class="custom-card p-4">
                <h5 class="fw-bold font-outfit mb-3 text-dark">
                    <i class="fa-solid fa-sliders text-primary me-2"></i> Slideshow Settings
                </h5>

                <form action="<?= APP_URL ?>/admin/dashboard.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="update_settings" value="1">

                    <div class="mb-3">
                        <label for="slideInterval" class="form-label fw-semibold">Slide Duration (Seconds)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="slideInterval" name="slideshow_interval" 
                                   min="3" max="60" value="<?= $currentInterval ?>" required>
                            <span class="input-group-text">seconds</span>
                        </div>
                        <div class="form-text">Time each photo remains on the screen (default: 8s).</div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="showVisitorName" 
                               name="show_visitor_name" value="1" <?= $currentShowName ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="showVisitorName">
                            Display Visitor Attribution
                        </label>
                        <div class="form-text">Show "Photo by [Name]" caption when visitor permits it.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-3">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
