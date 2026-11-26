<?php
/**
 * Admin Photo Moderation Grid
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

$filter = $_GET['filter'] ?? 'all';

$sql = "
    SELECT p.id, p.visitor_id, p.original_filename, p.stored_filename, p.storage_path, 
           p.mime_type, p.file_size, p.is_approved, p.uploaded_at,
           v.name AS visitor_name, v.email AS visitor_email
    FROM photos p
    LEFT JOIN visitors v ON p.visitor_id = v.id
";

if ($filter === 'approved') {
    $sql .= " WHERE p.is_approved = 1";
} elseif ($filter === 'hidden') {
    $sql .= " WHERE p.is_approved = 0";
}

$sql .= " ORDER BY p.uploaded_at DESC";

$stmt = $db->query($sql);
$photos = $stmt->fetchAll();

$pageTitle = 'Photo Moderation - Host Panel';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold font-outfit mb-0">Photo Moderation Gallery</h2>
            <p class="text-muted small mb-0">Review, approve, hide, or permanently remove attendee photos.</p>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-sm">
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/photos.php?filter=all">All Photos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/photos.php?filter=approved">Approved</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'hidden' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/photos.php?filter=hidden">Hidden</a>
        </li>
    </ul>

    <?php if (empty($photos)): ?>
        <div class="custom-card p-5 text-center text-muted">
            <i class="fa-solid fa-images fa-3x mb-3 opacity-50"></i>
            <h5 class="fw-semibold">No photos match the selected filter</h5>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($photos as $photo): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="custom-card h-100 overflow-hidden d-flex flex-column">
                        <div class="position-relative bg-dark" style="aspect-ratio: 4/3;">
                            <img src="<?= APP_URL . '/' . e($photo['storage_path']) ?>" 
                                 alt="<?= e($photo['original_filename']) ?>" 
                                 class="w-100 h-100 object-fit-cover">

                            <span class="position-absolute top-0 start-0 m-2 badge <?= $photo['is_approved'] ? 'bg-success' : 'bg-secondary' ?> shadow-sm">
                                <?= $photo['is_approved'] ? 'Approved' : 'Hidden' ?>
                            </span>
                        </div>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="fw-semibold text-dark text-truncate small" title="<?= e($photo['original_filename']) ?>">
                                <?= e($photo['original_filename']) ?>
                            </div>
                            <div class="text-muted small mt-1">
                                <i class="fa-solid fa-user me-1"></i> <?= e($photo['visitor_name'] ?? 'Unknown') ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center text-muted small mt-1">
                                <span><?= formatBytes((int)$photo['file_size']) ?></span>
                                <span><?= date('M d, H:i', strtotime($photo['uploaded_at'])) ?></span>
                            </div>

                            <div class="mt-3 pt-2 border-top d-flex gap-2 justify-content-between">
                                <!-- Toggle Approval -->
                                <form action="<?= APP_URL ?>/admin/actions/toggle-approval.php" method="POST" class="flex-grow-1">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                    <input type="hidden" name="return_url" value="<?= APP_URL ?>/admin/photos.php?filter=<?= e($filter) ?>">
                                    <button type="submit" class="btn btn-sm w-100 <?= $photo['is_approved'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                        <i class="fa-solid <?= $photo['is_approved'] ? 'fa-eye-slash' : 'fa-check' ?> me-1"></i>
                                        <?= $photo['is_approved'] ? 'Hide' : 'Approve' ?>
                                    </button>
                                </form>

                                <!-- Delete Photo -->
                                <form action="<?= APP_URL ?>/admin/actions/delete-photo.php" method="POST" 
                                      onsubmit="return confirm('Permanently delete this photo? This cannot be undone.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                    <input type="hidden" name="return_url" value="<?= APP_URL ?>/admin/photos.php?filter=<?= e($filter) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
