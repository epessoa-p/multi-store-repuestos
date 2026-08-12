<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('catalog_token', 40)->nullable()->unique()->after('color');
            $table->boolean('catalog_enabled')->default(true)->after('catalog_token');
        });

        // Backfill: token único para las sucursales existentes.
        DB::table('branches')->select('id')->whereNull('catalog_token')->orderBy('id')
            ->each(function ($b) {
                DB::table('branches')->where('id', $b->id)->update([
                    'catalog_token' => Str::random(40),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['catalog_token', 'catalog_enabled']);
        });
    }
};
