<?php
/**
 * Admin Visitor Management
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

$sql = "
    SELECT 
        v.id, 
        v.name, 
        v.email, 
        v.created_at,
        COUNT(p.id) AS total_photos,
        SUM(CASE WHEN p.is_approved = 1 THEN 1 ELSE 0 END) AS approved_photos,
        c.consent_status,
        c.display_name_permitted
    FROM visitors v
    LEFT JOIN photos p ON v.id = p.visitor_id
    LEFT JOIN (
        SELECT cr1.visitor_id, cr1.consent_status, cr1.display_name_permitted
        FROM consent_records cr1
        INNER JOIN (
            SELECT visitor_id, MAX(id) AS max_id
            FROM consent_records
            GROUP BY visitor_id
        ) cr2 ON cr1.id = cr2.max_id
    ) c ON v.id = c.visitor_id
    GROUP BY v.id, v.name, v.email, v.created_at, c.consent_status, c.display_name_permitted
    ORDER BY v.created_at DESC
";

$stmt = $db->query($sql);
$visitors = $stmt->fetchAll();

$pageTitle = 'Visitor Management - Host Panel';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold font-outfit mb-0">Registered Event Visitors</h2>
            <p class="text-muted small mb-0">Attendee records, photo metrics, and consent state.</p>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="custom-card p-4">
        <?php if (empty($visitors)): ?>
            <p class="text-muted text-center py-4 mb-0">No visitors have registered yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Visitor</th>
                            <th>Email</th>
                            <th>Photos</th>
                            <th>Consent</th>
                            <th>Attribution</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitors as $v): ?>
                            <tr>
                                <td class="fw-semibold text-dark">
                                    <i class="fa-solid fa-user-circle text-primary me-1"></i> <?= e($v['name']) ?>
                                </td>
                                <td class="text-muted font-monospace small">
                                    <?= e($v['email']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        <?= (int)$v['total_photos'] ?> total (<?= (int)$v['approved_photos'] ?> approved)
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['consent_status'] === 'granted'): ?>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fa-solid fa-check me-1"></i> Granted
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="fa-solid fa-ban me-1"></i> Withdrawn
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !empty($v['display_name_permitted']) ? '<span class="text-success small"><i class="fa-solid fa-check"></i> Allowed</span>' : '<span class="text-muted small">Anonymous</span>' ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('M d, Y H:i', strtotime($v['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
