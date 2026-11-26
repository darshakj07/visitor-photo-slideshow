-- VISITOR PHOTO SLIDESHOW
-- Database Schema for MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS `visitor_slideshow_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `visitor_slideshow_db`;

-- 1. Table: visitors
CREATE TABLE IF NOT EXISTS `visitors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `access_pin_hash` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: photos
CREATE TABLE IF NOT EXISTS `photos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` INT UNSIGNED NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(100) NOT NULL UNIQUE,
    `storage_path` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(50) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_photos_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE,
    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_is_approved` (`is_approved`),
    INDEX `idx_uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: consent_records
CREATE TABLE IF NOT EXISTS `consent_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` INT UNSIGNED NOT NULL,
    `consent_type` VARCHAR(50) NOT NULL DEFAULT 'public_slideshow',
    `consent_status` ENUM('granted', 'withdrawn') NOT NULL DEFAULT 'granted',
    `display_name_permitted` TINYINT(1) NOT NULL DEFAULT 1,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `granted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `withdrawn_at` DATETIME NULL,
    CONSTRAINT `fk_consent_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE,
    INDEX `idx_consent_visitor` (`visitor_id`),
    INDEX `idx_consent_status` (`consent_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: admins
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: slideshow_settings
CREATE TABLE IF NOT EXISTS `slideshow_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
