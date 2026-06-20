<?php

use App\Http\Controllers\Loyalty\LoyaltyDashboardController;
use App\Http\Controllers\Loyalty\LoyaltyMovementController;
use App\Http\Controllers\Loyalty\LoyaltyRedemptionController;
use App\Http\Controllers\Loyalty\LoyaltyRewardController;
use App\Http\Controllers\Loyalty\LoyaltySettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('loyalty')->name('loyalty.')->group(function () {

    // ── Dashboard (incluye ranking) ───────────────────────────
    Route::get('/', [LoyaltyDashboardController::class, 'index'])->name('dashboard')
        ->middleware('check-permission:loyalty-dashboard.view');

    // ── Configuración + reglas de acumulación ─────────────────
    Route::get('/settings',  [LoyaltySettingController::class, 'edit'])->name('settings.edit')
        ->middleware('check-permission:loyalty-settings.view');
    Route::put('/settings',  [LoyaltySettingController::class, 'update'])->name('settings.update')
        ->middleware('check-permission:loyalty-settings.edit');

    // ── Recompensas (catálogo) ────────────────────────────────
    Route::get('/rewards',                [LoyaltyRewardController::class, 'index'])->name('rewards.index')
        ->middleware('check-permission:loyalty-rewards.view');
    Route::get('/rewards/create',         [LoyaltyRewardController::class, 'create'])->name('rewards.create')
        ->middleware('check-permission:loyalty-rewards.create');
    Route::post('/rewards',               [LoyaltyRewardController::class, 'store'])->name('rewards.store')
        ->middleware('check-permission:loyalty-rewards.create');
    Route::get('/rewards/{reward}/edit',  [LoyaltyRewardController::class, 'edit'])->name('rewards.edit')
        ->middleware('check-permission:loyalty-rewards.edit');
    Route::put('/rewards/{reward}',       [LoyaltyRewardController::class, 'update'])->name('rewards.update')
        ->middleware('check-permission:loyalty-rewards.edit');
    Route::delete('/rewards/{reward}',    [LoyaltyRewardController::class, 'destroy'])->name('rewards.destroy')
        ->middleware('check-permission:loyalty-rewards.delete');

    // ── Canjes ────────────────────────────────────────────────
    Route::get('/redemptions',          [LoyaltyRedemptionController::class, 'index'])->name('redemptions.index')
        ->middleware('check-permission:loyalty-redemptions.view');
    Route::get('/redemptions/create',   [LoyaltyRedemptionController::class, 'create'])->name('redemptions.create')
        ->middleware('check-permission:loyalty.redeem');
    Route::post('/redemptions',         [LoyaltyRedemptionController::class, 'store'])->name('redemptions.store')
        ->middleware('check-permission:loyalty.redeem');
    Route::get('/clients/{client}/data', [LoyaltyRedemptionController::class, 'clientData'])->name('clients.data')
        ->middleware('check-permission:loyalty.redeem');

    // ── Movimientos de puntos ─────────────────────────────────
    Route::get('/movements', [LoyaltyMovementController::class, 'index'])->name('movements.index')
        ->middleware('check-permission:loyalty-movements.view');
});
