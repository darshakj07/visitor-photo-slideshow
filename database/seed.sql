-- VISITOR PHOTO SLIDESHOW
-- Initial Seed Data

USE `visitor_slideshow_db`;

-- Seed default Administrator: admin / Admin@2026!
INSERT INTO `admins` (`username`, `password_hash`, `name`, `created_at`)
VALUES (
    'admin',
    '$2y$10$MpYXfcmAmZpyYzAn4753C.uH/aFEVTbQipnrItaN0Pf0yFFwgPfZa',
    'Event Coordinator',
    NOW()
)
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Seed default Slideshow Settings
INSERT INTO `slideshow_settings` (`setting_key`, `setting_value`, `updated_at`)
VALUES 
    ('slideshow_interval', '8', NOW()),
    ('show_visitor_name', '1', NOW()),
    ('auto_play', '1', NOW()),
    ('transition_speed', '800', NOW())
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
