-- =====================================================================
-- Origen (procedencia) de productos — script de producción (todo en uno)
--   1) Tabla product_origins
--   2) Columna products.origin_id (FK)
--   3) Permisos del módulo (para asignarlos desde Administración ▸ Cargos)
--   4) Orígenes comunes por empresa (BRASIL, CHINA, JAPON)
-- NO asigna permisos a roles: eso se hace desde la vista de Cargos.
-- Ejecutar UNA sola vez. Fecha: 2026-08-13
-- =====================================================================

-- 1) Tabla de orígenes (mismo esquema que product_brands)
CREATE TABLE IF NOT EXISTS `product_origins` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `description` VARCHAR(500) NULL,
    `active`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `product_origins_company_id_index` (`company_id`),
    CONSTRAINT `product_origins_company_id_fk`
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Columna origin_id en products (FK -> product_origins, SET NULL al borrar)
ALTER TABLE `products`
    ADD COLUMN `origin_id` BIGINT UNSIGNED NULL AFTER `brand_id`,
    ADD KEY `products_origin_id_index` (`origin_id`),
    ADD CONSTRAINT `products_origin_id_fk`
        FOREIGN KEY (`origin_id`) REFERENCES `product_origins`(`id`) ON DELETE SET NULL;

-- 3) Permisos (idempotente: permissions.slug es UNIQUE).
--    La asignación a cada cargo se hace luego desde Administración ▸ Cargos.
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Orígenes',      'product-origins.view',   'product_origins', NULL, NOW(), NOW()),
('Crear Orígenes',    'product-origins.create', 'product_origins', NULL, NOW(), NOW()),
('Editar Orígenes',   'product-origins.edit',   'product_origins', NULL, NOW(), NOW()),
('Eliminar Orígenes', 'product-origins.delete', 'product_origins', NULL, NOW(), NOW());

-- 4) Orígenes comunes para CADA empresa existente (idempotente).
INSERT INTO `product_origins` (`company_id`, `name`, `active`, `created_at`, `updated_at`)
SELECT c.`id`, o.`name`, 1, NOW(), NOW()
FROM `companies` c
CROSS JOIN (
    SELECT 'BRASIL' AS `name`
    UNION ALL SELECT 'CHINA'
    UNION ALL SELECT 'JAPON'
) o
WHERE NOT EXISTS (
    SELECT 1 FROM `product_origins` po
    WHERE po.`company_id` = c.`id` AND po.`name` = o.`name`
);
