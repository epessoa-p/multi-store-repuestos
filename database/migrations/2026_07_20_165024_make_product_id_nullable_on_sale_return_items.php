<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Los ítems de "venta rápida" no tienen producto: product_id debe admitir NULL.
        // Se usa SQL directo para no tener que soltar/recrear la clave foránea.
        DB::statement('ALTER TABLE `sale_return_items` MODIFY `product_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `sale_return_items` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
    }
};
