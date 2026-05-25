-- ============================================================
-- loan_images table — store photos/images attached to loans
-- Run after existing migrations
-- ============================================================

CREATE TABLE IF NOT EXISTS `loan_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `loan_id` BIGINT UNSIGNED NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `size_bytes` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_loan_images_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
