<?php
/**
 * Admin Logout Handler
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logoutAdmin();
setFlashMessage('info', 'You have been signed out of the Host / Admin panel.');
redirect(APP_URL . '/admin/login.php');
