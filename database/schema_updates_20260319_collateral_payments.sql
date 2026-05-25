USE prestamos_db;

START TRANSACTION;

-- =========================================================
-- 1) SUCURSAL DEBE TENER ALMACEN PRINCIPAL
-- =========================================================

ALTER TABLE branches
    ADD COLUMN warehouse_id BIGINT UNSIGNED NULL AFTER company_id;

-- Crear almacenes para sucursales que no tengan ninguno
INSERT INTO warehouses (company_id, branch_id, name, code, location, description, active, created_at, updated_at)
SELECT
    b.company_id,
    b.id,
    CONCAT('Almacén ', b.name),
    CONCAT('WH-', b.id, '-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
    b.address,
    'Almacén principal autogenerado desde script de sucursales',
    1,
    NOW(),
    NOW()
FROM branches b
LEFT JOIN warehouses w ON w.branch_id = b.id AND w.deleted_at IS NULL
WHERE b.deleted_at IS NULL
  AND w.id IS NULL;

-- Asignar warehouse_id a cada sucursal (primer almacén activo)
UPDATE branches b
JOIN (
    SELECT branch_id, MIN(id) AS first_warehouse_id
    FROM warehouses
    WHERE deleted_at IS NULL
    GROUP BY branch_id
) x ON x.branch_id = b.id
SET b.warehouse_id = x.first_warehouse_id
WHERE b.warehouse_id IS NULL;

ALTER TABLE branches
    MODIFY COLUMN warehouse_id BIGINT UNSIGNED NOT NULL;

CREATE INDEX branches_warehouse_id_index ON branches (warehouse_id);

ALTER TABLE branches
    ADD CONSTRAINT branches_warehouse_id_foreign
    FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
    ON DELETE RESTRICT;

-- =========================================================
-- 2) PRENDA DE PRESTAMO (RETENCION / VENDIBLE / LIBERADA)
-- =========================================================

CREATE TABLE IF NOT EXISTS loan_collaterals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    loan_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    inventory_movement_id BIGINT UNSIGNED NULL,
    status ENUM('retained','sellable','released','sold') NOT NULL DEFAULT 'retained',
    retained_at TIMESTAMP NULL DEFAULT NULL,
    expected_release_date DATE NULL,
    sellable_at TIMESTAMP NULL DEFAULT NULL,
    released_at TIMESTAMP NULL DEFAULT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY loan_collaterals_loan_id_unique (loan_id),
    KEY loan_collaterals_company_status_idx (company_id, status),
    KEY loan_collaterals_product_idx (product_id),
    CONSTRAINT loan_collaterals_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE,
    CONSTRAINT loan_collaterals_loan_id_foreign
        FOREIGN KEY (loan_id) REFERENCES loans (id)
        ON DELETE CASCADE,
    CONSTRAINT loan_collaterals_branch_id_foreign
        FOREIGN KEY (branch_id) REFERENCES branches (id)
        ON DELETE RESTRICT,
    CONSTRAINT loan_collaterals_warehouse_id_foreign
        FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
        ON DELETE RESTRICT,
    CONSTRAINT loan_collaterals_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE RESTRICT,
    CONSTRAINT loan_collaterals_inventory_movement_id_foreign
        FOREIGN KEY (inventory_movement_id) REFERENCES inventory_movements (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3) PAGOS DE PRESTAMO (interes/capital/mixto)
-- =========================================================

ALTER TABLE loan_payments
    ADD COLUMN payment_type ENUM('interest','capital','mixed') NOT NULL DEFAULT 'mixed' AFTER amount;

CREATE INDEX loan_payments_loan_payment_date_idx ON loan_payments (loan_id, payment_date);

COMMIT;
