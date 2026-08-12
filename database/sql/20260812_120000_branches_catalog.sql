-- =====================================================================
-- Catálogo público por sucursal: token de acceso + habilitado
-- Fecha: 2026-08-12
-- =====================================================================

ALTER TABLE `branches`
    ADD COLUMN `catalog_token` VARCHAR(40) NULL AFTER `color`,
    ADD COLUMN `catalog_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `catalog_token`,
    ADD UNIQUE KEY `branches_catalog_token_unique` (`catalog_token`);

-- Backfill: asignar un token aleatorio (40 chars) a cada sucursal existente.
-- SHA2(...,160) genera 40 caracteres hex; combinamos id + UUID + microtiempo
-- para garantizar unicidad.
UPDATE `branches`
SET `catalog_token` = SHA2(CONCAT(`id`, '-', UUID(), '-', RAND()), 160)
WHERE `catalog_token` IS NULL;
