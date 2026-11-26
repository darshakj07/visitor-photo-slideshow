<?php
/**
 * Global Footer Component
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);
?>
<footer class="footer mt-auto py-4 bg-white border-top">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-center text-md-start">
                <span class="fw-bold font-outfit text-dark me-2">
                    <i class="fa-solid fa-photo-film text-primary me-1"></i> <?= e(APP_NAME) ?>
                </span>
                <span class="text-muted small">| MCA Assignment 1 &copy; <?= date('Y') ?></span>
                <div class="text-muted small mt-1">
                    Powered by Core PHP 8.2 &bull; MySQL &bull; Vanilla JS &bull; Bootstrap 5
                </div>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-inline-flex gap-3 small">
                    <a href="<?= APP_URL ?>/index.php#privacy" class="text-secondary text-decoration-none hover-primary">
                        <i class="fa-solid fa-shield-cat me-1"></i> Privacy & Consent
                    </a>
                    <a href="<?= APP_URL ?>/slideshow/index.php" target="_blank" class="text-secondary text-decoration-none hover-primary">
                        <i class="fa-solid fa-tv me-1"></i> Slideshow
                    </a>
                    <a href="<?= APP_URL ?>/visitor/access.php" class="text-secondary text-decoration-none hover-primary">
                        <i class="fa-solid fa-user-lock me-1"></i> Returning Visitor
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Global JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<!-- Page Specific Scripts Injection -->
<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
