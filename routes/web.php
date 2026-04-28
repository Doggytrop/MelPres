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

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware('auth')->group(function () {

    // — Dashboard —
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // — Profile —
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // — Customers —
    Route::resource('customers', CustomerController::class)->except(['destroy']);
    Route::post('customers/{customer}/documents', [CustomerDocumentController::class, 'store'])
        ->name('customers.documents.store');
    Route::delete('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy'])
        ->name('customers.documents.destroy');

    // — Loans —
    Route::get('loans/search-customer', [LoanController::class, 'searchCustomer'])
        ->name('loans.search-customer');
    Route::resource('loans', LoanController::class)->except(['destroy']);
    Route::post('loans/{loan}/payments', [PaymentController::class, 'store'])
        ->name('loans.payments.store');

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
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])
            ->name('loans.destroy');
        Route::get('settings', [SettingController::class, 'index'])
            ->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])
            ->name('settings.update');
        Route::resource('advisors', AdvisorController::class)->except(['show']);
    });

});

require __DIR__.'/auth.php';