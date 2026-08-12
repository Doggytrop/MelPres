<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerDocumentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AdvisorController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\RestructuringController;
use App\Http\Controllers\CollectorController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdmin\CompanyController as SuperAdminCompanyController;
use App\Http\Controllers\SuperAdmin\SaasActivityLogController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('dashboard');
        Route::get('activity-logs', [SaasActivityLogController::class, 'index'])
            ->name('activity-logs.index');
        Route::resource('companies', SuperAdminCompanyController::class)
            ->only(['index', 'create', 'store', 'show']);
        Route::post('companies/{company}/renew', [SuperAdminCompanyController::class, 'renew'])
            ->name('companies.renew');
        Route::post('companies/{company}/grace', [SuperAdminCompanyController::class, 'updateGrace'])
            ->name('companies.grace.update');
        Route::delete('companies/{company}/grace', [SuperAdminCompanyController::class, 'removeGrace'])
            ->name('companies.grace.remove');
        Route::post('companies/{company}/suspend', [SuperAdminCompanyController::class, 'suspend'])
            ->name('companies.suspend');
        Route::post('companies/{company}/reactivate', [SuperAdminCompanyController::class, 'reactivate'])
            ->name('companies.reactivate');
        Route::post('companies/{company}/cancel', [SuperAdminCompanyController::class, 'cancel'])
            ->name('companies.cancel');
    });

Route::middleware('auth', 'redirect.customer')->group(function () {

    // — Profile —
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['company.required', 'subscription.access'])->group(function () {
    // — Dashboard —
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // — Customers —
    Route::resource('customers', CustomerController::class)->except(['destroy']);
    Route::post('customers/{customer}/documents', [CustomerDocumentController::class, 'store'])
        ->name('customers.documents.store');
    Route::get('customer-documents/{customerDocument}/view', [CustomerDocumentController::class, 'view'])
        ->name('customer-documents.view');
    Route::get('customer-documents/{customerDocument}/download', [CustomerDocumentController::class, 'download'])
        ->name('customer-documents.download');
    Route::delete('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy'])
        ->name('customers.documents.destroy');
    // — Loans —
    Route::get('loans/search-customer', [LoanController::class, 'searchCustomer'])
        ->name('loans.search-customer');
    Route::resource('loans', LoanController::class)->except(['destroy']);
    Route::post('loans/{loan}/payments', [PaymentController::class, 'store'])
        ->name('loans.payments.store');
    Route::get('loans/{loan}/contract', [LoanController::class, 'contract'])->name('loans.contract');
    Route::get('loans/{loan}/promissory-note', [LoanController::class, 'promissoryNote'])->name('loans.promissory-note');

    // — History —
    Route::get('history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('history/{loan}', [HistoryController::class, 'show'])->name('history.show');
    Route::get('history/{loan}/pdf', [HistoryController::class, 'pdf'])->name('history.pdf');

    // — Cash Register —
    Route::get('cash-register', [CashRegisterController::class, 'index'])->name('cash-register.index');
    Route::get('cash-register/pdf', [CashRegisterController::class, 'pdf'])->name('cash-register.pdf');

    // — Simulator —
    Route::get('simulator', [SimulatorController::class, 'index'])->name('simulator.index');
    Route::post('simulator/calculate', [SimulatorController::class, 'calculate'])->name('simulator.calculate');

    // — Restructuring —
    Route::get('restructuring/overdue', [RestructuringController::class, 'overdue'])
        ->name('restructuring.overdue');
    Route::get('restructuring/active', [RestructuringController::class, 'active'])
        ->name('restructuring.active');
    Route::get('restructuring/history', [RestructuringController::class, 'history'])
        ->name('restructuring.history');
    Route::get('restructuring/{loan}/create', [RestructuringController::class, 'create'])
        ->name('restructuring.create');
    Route::post('restructuring/{loan}/create', [RestructuringController::class, 'store'])
        ->name('restructuring.store');
    Route::get('restructuring/pdf/{restructuring}', [RestructuringController::class, 'pdf'])
        ->name('restructuring.pdf');

    // — Admin only —
    Route::middleware(['solo.admin'])->group(function () {
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
            ->name('customers.destroy');
        Route::post('customers/{customer}/reset-password', [CustomerController::class, 'resetPassword'])
            ->name('customers.reset-password');
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])
            ->name('loans.destroy');
        Route::get('settings', [SettingController::class, 'index'])
            ->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])
            ->name('settings.update');
        Route::post('settings/collectors/{user}', [CollectorController::class, 'adminUpdateConfig'])
            ->name('settings.collectors.update');
        Route::resource('advisors', AdvisorController::class)->except(['show']);
        Route::get('activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])
            ->name('activity-logs.index');
    });
    });
});

// — Gestión de usuarios —
Route::middleware(['auth', 'company.required', 'subscription.access', 'solo.admin'])->group(function () {
    Route::resource('users', App\Http\Controllers\UserController::class)->except(['show']);
});

// — Portal del cliente —
Route::middleware(['auth', 'company.required', 'subscription.access'])->prefix('portal')->group(function () {
    Route::get('/', [App\Http\Controllers\PortalController::class, 'index'])->name('portal.index');
    Route::get('/loan/{loan}', [App\Http\Controllers\PortalController::class, 'show'])->name('portal.show');
});

// — Panel del cobrador —
Route::middleware(['auth', 'company.required', 'subscription.access', 'solo.collector'])->prefix('collector')->group(function () {
    Route::get('/', [App\Http\Controllers\CollectorController::class, 'index'])->name('collector.index');
    Route::post('/collect/{loan}', [App\Http\Controllers\CollectorController::class, 'collect'])->name('collector.collect');
});


require __DIR__.'/auth.php';
