USE prestamos_db;

START TRANSACTION;

-- 1) Agregar columna branch_id al registro de creditos (loans)
ALTER TABLE loans
    ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER company_id;

-- 2) Backfill: asignar la primera sucursal activa por empresa a los creditos existentes
UPDATE loans l
JOIN (
    SELECT b.company_id, MIN(b.id) AS first_branch_id
    FROM branches b
    WHERE b.active = 1
      AND b.deleted_at IS NULL
    GROUP BY b.company_id
) x ON x.company_id = l.company_id
SET l.branch_id = x.first_branch_id
WHERE l.branch_id IS NULL;

-- 3) Volver obligatorio branch_id para que el filtro por sucursal sea consistente
ALTER TABLE loans
    MODIFY COLUMN branch_id BIGINT UNSIGNED NOT NULL;

-- 4) Índice y llave foránea
CREATE INDEX loans_branch_id_index ON loans (branch_id);

ALTER TABLE loans
    ADD CONSTRAINT loans_branch_id_foreign
    FOREIGN KEY (branch_id) REFERENCES branches (id)
    ON DELETE RESTRICT;

COMMIT;
