-- ============================================================
-- Remove loan_type_id and purpose columns from loans table
-- Date: 2026-03-23
-- ============================================================

-- Remove foreign key if exists
ALTER TABLE `loans` DROP FOREIGN KEY IF EXISTS `loans_loan_type_id_foreign`;

-- Remove loan_type_id column
ALTER TABLE `loans` DROP COLUMN IF EXISTS `loan_type_id`;

-- Remove purpose column
ALTER TABLE `loans` DROP COLUMN IF EXISTS `purpose`;

-- Update any existing pending/approved loans to active
UPDATE `loans` SET `status` = 'active',
    `approved_by` = `created_by`,
    `approved_at` = `created_at`,
    `start_date` = CURDATE(),
    `end_date` = DATE_ADD(CURDATE(), INTERVAL `term_months` MONTH)
WHERE `status` IN ('pending', 'approved');
