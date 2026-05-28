<?php

use App\Http\Controllers\Admin\CashRegisterController;
use App\Http\Controllers\CashRegister\CashSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // Admin: gestión de cajas
    Route::prefix('admin/cash-registers')->name('cash-registers.')->group(function () {
        Route::get('/',                    [CashRegisterController::class, 'index'])->name('index');
        Route::get('/create',              [CashRegisterController::class, 'create'])->name('create');
        Route::post('/',                   [CashRegisterController::class, 'store'])->name('store');
        Route::get('/{cashRegister}',      [CashRegisterController::class, 'show'])->name('show');
        Route::get('/{cashRegister}/edit', [CashRegisterController::class, 'edit'])->name('edit');
        Route::put('/{cashRegister}',      [CashRegisterController::class, 'update'])->name('update');
        Route::delete('/{cashRegister}',   [CashRegisterController::class, 'destroy'])->name('destroy');
    });

    // Operaciones de caja (cajero)
    Route::prefix('cash')->name('cash.')->group(function () {
        Route::post('/open',                       [CashSessionController::class, 'open'])->name('open');
        Route::post('/session/{session}/close',    [CashSessionController::class, 'close'])->name('session.close');
        Route::get('/session/{session}',           [CashSessionController::class, 'show'])->name('session.show');
        Route::post('/session/{session}/movement', [CashSessionController::class, 'addMovement'])->name('movement.store');
    });
});
