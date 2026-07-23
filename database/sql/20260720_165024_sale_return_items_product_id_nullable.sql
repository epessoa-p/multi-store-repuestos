-- ============================================================
--  VR Motors — Permitir devolver ítems de "venta rápida"
--  Los ítems de venta rápida no tienen producto (product_id NULL).
--  La columna sale_return_items.product_id pasa a admitir NULL.
--  La clave foránea sigue vigente (una FK admite valores NULL).
-- ============================================================

ALTER TABLE `sale_return_items` MODIFY `product_id` BIGINT UNSIGNED NULL;
