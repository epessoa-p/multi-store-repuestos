USE prestamos_db;

START TRANSACTION;

-- =========================================================
-- CATEGORIAS DE CREDITO
-- =========================================================

CREATE TABLE IF NOT EXISTS credit_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    min_amount DECIMAL(15,2) NULL,
    max_amount DECIMAL(15,2) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY credit_categories_company_slug_unique (company_id, slug),
    KEY credit_categories_company_name_index (company_id, name),
    CONSTRAINT credit_categories_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- REGLAS DE CREDITO POR CATEGORIA
-- =========================================================

CREATE TABLE IF NOT EXISTS credit_category_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    credit_category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NULL,
    interest_rate DECIMAL(8,2) NOT NULL,
    interest_period ENUM('monthly','quarterly','semiannual','annual') NOT NULL DEFAULT 'monthly',
    term_months_limit INT UNSIGNED NOT NULL,
    min_amount DECIMAL(15,2) NULL,
    max_amount DECIMAL(15,2) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY credit_category_rules_category_index (credit_category_id),
    KEY credit_category_rules_company_active_index (company_id, active),
    CONSTRAINT credit_category_rules_category_id_foreign
        FOREIGN KEY (credit_category_id) REFERENCES credit_categories (id)
        ON DELETE CASCADE,
    CONSTRAINT credit_category_rules_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PRODUCTOS -> CATEGORIA DE CREDITO
-- =========================================================

ALTER TABLE products
    ADD COLUMN credit_category_id BIGINT UNSIGNED NULL AFTER company_id;

ALTER TABLE products
    ADD CONSTRAINT products_credit_category_id_foreign
    FOREIGN KEY (credit_category_id) REFERENCES credit_categories (id)
    ON DELETE SET NULL;

CREATE INDEX products_credit_category_id_index ON products (credit_category_id);

-- =========================================================
-- PRESTAMOS -> PRODUCTO / CATEGORIA / REGLA / PERIODICIDAD
-- =========================================================

ALTER TABLE loans
    ADD COLUMN product_id BIGINT UNSIGNED NULL AFTER loan_type_id,
    ADD COLUMN credit_category_id BIGINT UNSIGNED NULL AFTER product_id,
    ADD COLUMN credit_category_rule_id BIGINT UNSIGNED NULL AFTER credit_category_id,
    ADD COLUMN interest_period ENUM('monthly','quarterly','semiannual','annual') NOT NULL DEFAULT 'monthly' AFTER interest_rate;

ALTER TABLE loans
    ADD CONSTRAINT loans_product_id_foreign
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE SET NULL;

ALTER TABLE loans
    ADD CONSTRAINT loans_credit_category_id_foreign
    FOREIGN KEY (credit_category_id) REFERENCES credit_categories (id)
    ON DELETE SET NULL;

ALTER TABLE loans
    ADD CONSTRAINT loans_credit_category_rule_id_foreign
    FOREIGN KEY (credit_category_rule_id) REFERENCES credit_category_rules (id)
    ON DELETE SET NULL;

CREATE INDEX loans_product_id_index ON loans (product_id);
CREATE INDEX loans_credit_category_id_index ON loans (credit_category_id);
CREATE INDEX loans_credit_category_rule_id_index ON loans (credit_category_rule_id);

-- =========================================================
-- DATOS BASE DE EJEMPLO (EMPRESA DEMO id=1)
-- =========================================================

INSERT INTO credit_categories (company_id, name, slug, description, min_amount, max_amount, active, created_at, updated_at)
VALUES
(1, 'Vehículos', 'vehiculos', 'Créditos prendarios de vehículos con opciones flexibles', NULL, NULL, 1, NOW(), NOW()),
(1, 'Joyas', 'joyas', 'Créditos sobre joyería', NULL, NULL, 1, NOW(), NOW()),
(1, 'Artículos', 'articulos', 'Electrodomésticos y artículos varios', NULL, NULL, 1, NOW(), NOW()),
(1, 'Garrafas', 'garrafas', 'Crédito rápido para garrafas con rangos de monto', 25.00, 300.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
description = VALUES(description),
min_amount = VALUES(min_amount),
max_amount = VALUES(max_amount),
active = VALUES(active),
updated_at = VALUES(updated_at);

-- Reglas Vehículos (10%, 5%, 2% mensual, límite 3 meses)
INSERT INTO credit_category_rules (company_id, credit_category_id, name, interest_rate, interest_period, term_months_limit, min_amount, max_amount, active, created_at, updated_at)
SELECT 1, id, 'Vehículo plan 10%', 10.00, 'monthly', 3, NULL, NULL, 1, NOW(), NOW()
FROM credit_categories WHERE company_id = 1 AND slug = 'vehiculos';

INSERT INTO credit_category_rules (company_id, credit_category_id, name, interest_rate, interest_period, term_months_limit, min_amount, max_amount, active, created_at, updated_at)
SELECT 1, id, 'Vehículo plan 5%', 5.00, 'monthly', 3, NULL, NULL, 1, NOW(), NOW()
FROM credit_categories WHERE company_id = 1 AND slug = 'vehiculos';

INSERT INTO credit_category_rules (company_id, credit_category_id, name, interest_rate, interest_period, term_months_limit, min_amount, max_amount, active, created_at, updated_at)
SELECT 1, id, 'Vehículo plan 2%', 2.00, 'monthly', 3, NULL, NULL, 1, NOW(), NOW()
FROM credit_categories WHERE company_id = 1 AND slug = 'vehiculos';

-- Reglas Joyas
INSERT INTO credit_category_rules (company_id, credit_category_id, name, interest_rate, interest_period, term_months_limit, min_amount, max_amount, active, created_at, updated_at)
SELECT 1, id, 'Joyas 10% x 3 meses', 10.00, 'monthly', 3, NULL, NULL, 1, NOW(), NOW()
FROM credit_categories WHERE company_id = 1 AND slug = 'joyas';

-- Reglas Artículos
INSERT INTO credit_category_rules (company_id, credit_category_id, name, interest_rate, interest_period, term_months_limit, min_amount, max_amount, active, created_at, updated_at)
SELECT 1, id, 'Artículos 15% x 3 meses', 15.00, 'monthly', 3, NULL, NULL, 1, NOW(), NOW()
FROM credit_categories WHERE company_id = 1 AND slug = 'articulos';

-- Reglas Garrafas
INSERT INTO credit_category_rules (company_id, credit_category_id, name, interest_rate, interest_period, term_months_limit, min_amount, max_amount, active, created_at, updated_at)
SELECT 1, id, 'Garrafas 20% x 2 meses', 20.00, 'monthly', 2, 25.00, 300.00, 1, NOW(), NOW()
FROM credit_categories WHERE company_id = 1 AND slug = 'garrafas';

COMMIT;
