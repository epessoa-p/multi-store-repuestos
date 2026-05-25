USE prestamos_db;

START TRANSACTION;

-- =========================================================
-- 1) Contrato de prestamo (texto + firma)
-- =========================================================
CREATE TABLE IF NOT EXISTS loan_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    content LONGTEXT NULL,
    signature_path VARCHAR(255) NULL,
    signed_at TIMESTAMP NULL DEFAULT NULL,
    signed_by BIGINT UNSIGNED NULL,
    status ENUM('draft','signed','archived') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY loan_contracts_loan_id_unique (loan_id),
    KEY loan_contracts_company_id_idx (company_id),
    KEY loan_contracts_signed_by_idx (signed_by),
    CONSTRAINT loan_contracts_loan_id_foreign
        FOREIGN KEY (loan_id) REFERENCES loans (id)
        ON DELETE CASCADE,
    CONSTRAINT loan_contracts_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE,
    CONSTRAINT loan_contracts_signed_by_foreign
        FOREIGN KEY (signed_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 2) Adjuntos del contrato
-- =========================================================
CREATE TABLE IF NOT EXISTS loan_contract_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_contract_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    size_bytes BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY loan_contract_attachments_contract_idx (loan_contract_id),
    CONSTRAINT loan_contract_attachments_contract_foreign
        FOREIGN KEY (loan_contract_id) REFERENCES loan_contracts (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3) payment_type en loan_payments (por si no existe)
-- =========================================================
SET @payment_type_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'loan_payments'
      AND COLUMN_NAME = 'payment_type'
);

SET @sql_payment_type := IF(@payment_type_exists = 0,
    "ALTER TABLE loan_payments ADD COLUMN payment_type ENUM('interest','capital','mixed') NOT NULL DEFAULT 'mixed' AFTER amount",
    'SELECT 1');
PREPARE stmt_payment_type FROM @sql_payment_type;
EXECUTE stmt_payment_type;
DEALLOCATE PREPARE stmt_payment_type;

COMMIT;
