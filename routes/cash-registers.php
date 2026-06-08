<?php

use App\Http\Controllers\Admin\CashRegisterController;
use App\Http\Controllers\CashRegister\CashSessionController;
use App\Http\Controllers\CashRegister\MovementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ── Movimientos (tablero financiero por sucursal) ─────────────
    Route::get('cash/movimientos', [MovementController::class, 'index'])
        ->name('cash.movements')->middleware('check-permission:cash-registers.view');

    // ── Admin: gestión de cajas ───────────────────────────────────
    // IMPORTANTE: /create debe ir ANTES de /{cashRegister} (wildcard)
    Route::prefix('admin/cash-registers')->name('cash-registers.')->group(function () {
        Route::get('/',          [CashRegisterController::class, 'index'])->name('index')->middleware('check-permission:cash-registers.view');
        Route::get('/create',    [CashRegisterController::class, 'create'])->name('create')->middleware('check-permission:cash-registers.create');
        Route::post('/',         [CashRegisterController::class, 'store'])->name('store')->middleware('check-permission:cash-registers.create');
        Route::get('/{cashRegister}',      [CashRegisterController::class, 'show'])->name('show')->middleware('check-permission:cash-registers.view');
        Route::get('/{cashRegister}/edit', [CashRegisterController::class, 'edit'])->name('edit')->middleware('check-permission:cash-registers.edit');
        Route::put('/{cashRegister}',      [CashRegisterController::class, 'update'])->name('update')->middleware('check-permission:cash-registers.edit');
        Route::delete('/{cashRegister}',   [CashRegisterController::class, 'destroy'])->name('destroy')->middleware('check-permission:cash-registers.delete');
    });

    // ── Operaciones de caja (cajero) ──────────────────────────────
    Route::middleware('check-permission:cash.operate')->prefix('cash')->name('cash.')->group(function () {
        Route::post('/open',                       [CashSessionController::class, 'open'])->name('open');
        Route::post('/session/{session}/close',    [CashSessionController::class, 'close'])->name('session.close');
        Route::get('/session/{session}',           [CashSessionController::class, 'show'])->name('session.show');
        Route::post('/session/{session}/movement', [CashSessionController::class, 'addMovement'])->name('movement.store');
    });
});
