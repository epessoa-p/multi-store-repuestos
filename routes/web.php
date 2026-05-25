<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CreditCategoryController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CargoController;
use App\Http\Controllers\Admin\PersonalController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Loans\LoanActionController;
use App\Http\Controllers\Loans\LoanController;
use App\Http\Controllers\Loans\LoanContractController;
use App\Http\Controllers\Loans\LoanPaymentController;
use App\Http\Controllers\DocumentTemplates\DocumentTemplateController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Company selection (multi-empresa)
    Route::get('/select-company', [LoginController::class, 'selectCompany'])->name('select-company');
    Route::post('/set-company/{companyId}', [LoginController::class, 'setCompany'])->name('set-company');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Super Admin - Company Management
    Route::middleware('check-role:super_admin')->prefix('admin/companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('/{company}', [CompanyController::class, 'show'])->name('show');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
    });

    // User Management
    Route::prefix('admin/users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/assign-role/{company}/{role}', [UserController::class, 'assignRole'])->name('assign-role');
    });

    // Role Management (Super Admin only)
    Route::prefix('admin/roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/cargos')->name('cargos.')->group(function () {
        Route::get('/', [CargoController::class, 'index'])->name('index');
        Route::get('/create', [CargoController::class, 'create'])->name('create');
        Route::post('/', [CargoController::class, 'store'])->name('store');
        Route::get('/role-permissions/{role}', [CargoController::class, 'rolePermissions'])->name('role-permissions');
        Route::get('/{cargo}/edit', [CargoController::class, 'edit'])->name('edit');
        Route::put('/{cargo}', [CargoController::class, 'update'])->name('update');
        Route::delete('/{cargo}', [CargoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/personal')->name('personal.')->group(function () {
        Route::get('/', [PersonalController::class, 'index'])->name('index');
        Route::get('/create', [PersonalController::class, 'create'])->name('create');
        Route::post('/', [PersonalController::class, 'store'])->name('store');
        Route::get('/{personal}/edit', [PersonalController::class, 'edit'])->name('edit');
        Route::put('/{personal}', [PersonalController::class, 'update'])->name('update');
        Route::delete('/{personal}', [PersonalController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::get('/create', [BranchController::class, 'create'])->name('create');
        Route::post('/', [BranchController::class, 'store'])->name('store');
        Route::get('/{branch}', [BranchController::class, 'show'])->name('show');
        Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
        Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
        Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/credit-categories')->name('credit-categories.')->group(function () {
        Route::get('/', [CreditCategoryController::class, 'index'])->name('index');
        Route::get('/create', [CreditCategoryController::class, 'create'])->name('create');
        Route::post('/', [CreditCategoryController::class, 'store'])->name('store');
        Route::get('/{creditCategory}', [CreditCategoryController::class, 'show'])->name('show');
        Route::get('/{creditCategory}/edit', [CreditCategoryController::class, 'edit'])->name('edit');
        Route::put('/{creditCategory}', [CreditCategoryController::class, 'update'])->name('update');
        Route::delete('/{creditCategory}', [CreditCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/warehouses')->name('warehouses.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
        Route::post('/{warehouse}/movements', [WarehouseController::class, 'storeMovement'])->name('movements.store');
    });

    // Loans Management
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/create', [LoanController::class, 'create'])->name('create');
        Route::post('/', [LoanController::class, 'store'])->name('store');
        Route::get('/payments', [LoanPaymentController::class, 'index'])->name('payments.index');
        Route::get('/{loan}/payments/create', [LoanPaymentController::class, 'create'])->name('payments.create');
        Route::post('/{loan}/payments', [LoanPaymentController::class, 'store'])->name('payments.store');
        Route::get('/{loan}/amortize', [LoanActionController::class, 'amortize'])->name('amortize');
        Route::post('/{loan}/amortize', [LoanActionController::class, 'storeAmortization'])->name('amortize.store');
        Route::get('/{loan}/liquidate', [LoanActionController::class, 'liquidate'])->name('liquidate');
        Route::post('/{loan}/liquidate', [LoanActionController::class, 'storeLiquidation'])->name('liquidate.store');
        Route::post('/{loan}/interest-payment', [LoanActionController::class, 'storeInterestPayment'])->name('interest.store');
        Route::get('/{loan}/contract', [LoanContractController::class, 'edit'])->name('contract.edit');
        Route::post('/{loan}/contract', [LoanContractController::class, 'update'])->name('contract.update');
        Route::match(['get', 'post'], '/{loan}/contract/download/pdf', [LoanContractController::class, 'downloadPdf'])->name('contract.download.pdf');
        Route::match(['get', 'post'], '/{loan}/contract/download/word', [LoanContractController::class, 'downloadWord'])->name('contract.download.word');
        Route::get('/{loan}/contract/download/excel', [LoanContractController::class, 'downloadExcel'])->name('contract.download.excel');
        Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
        Route::post('/{loan}/approve', [LoanController::class, 'approve'])->name('approve');
        Route::post('/{loan}/activate', [LoanController::class, 'activate'])->name('activate');
        Route::post('/{loan}/payment', [LoanController::class, 'recordPayment'])->name('payment');
        Route::delete('/{loan}', [LoanController::class, 'destroy'])->name('destroy');
    });

    // Document Templates
    Route::prefix('document-templates')->name('document-templates.')->group(function () {
        Route::get('/', [DocumentTemplateController::class, 'index'])->name('index');
        Route::get('/create', [DocumentTemplateController::class, 'create'])->name('create');
        Route::post('/', [DocumentTemplateController::class, 'store'])->name('store');
        Route::get('/{documentTemplate}/download/word', [DocumentTemplateController::class, 'downloadWord'])->name('download.word');
        Route::get('/{documentTemplate}/export/pdf', [DocumentTemplateController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{documentTemplate}', [DocumentTemplateController::class, 'show'])->name('show');
        Route::get('/{documentTemplate}/edit', [DocumentTemplateController::class, 'edit'])->name('edit');
        Route::put('/{documentTemplate}', [DocumentTemplateController::class, 'update'])->name('update');
        Route::delete('/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{documentTemplate}/apply-to-loan', [DocumentTemplateController::class, 'applyToLoan'])->name('apply-to-loan');
    });

    // Clients Module
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/create', [ClientController::class, 'create'])->name('create');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::get('/{client}', [ClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update');
    });
});

// Fallback
Route::redirect('/', '/dashboard');

