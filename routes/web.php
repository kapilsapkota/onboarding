<?php

use App\Http\Controllers\Admin\DirectDebitPaymentController;
use App\Http\Controllers\Admin\XeroConnectionController;
use App\Http\Controllers\Admin\XeroContactController;
use App\Http\Controllers\Admin\XeroInvoiceController;
use App\Http\Controllers\Admin\XeroSyncController;
use App\Http\Controllers\Admin\XeroTenantSettingsController;
use App\Http\Controllers\AdminChargeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/ddr', function () {
    return view('ddr');
});
Route::post('/onboarding/create-customer', [OnboardingController::class, 'createCustomer'])->name('onboarding.create-customer');
Route::post('/onboarding/direct-debit', [OnboardingController::class, 'directDebitStore'])->name('onboarding.direct-debit');

Route::post('/onboarding/setup-intent', [OnboardingController::class, 'createSetupIntent'])
    ->name('onboarding.setup-intent');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::redirect('/onboarding', '/');
Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
Route::get('/onboarding/thanks', [OnboardingController::class, 'thanks'])->name('onboarding.thanks');

Route::middleware(['auth'])->prefix('admin')->name('clients.')->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->name('index');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('destroy');
    Route::patch('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('status');

    Route::post('/admin/clients/{client}/charge', [AdminChargeController::class, 'charge'])->name('charge');
    Route::post('/clients/{client}/charge-invoice', [AdminChargeController::class, 'chargeInvoice'])
        ->name('charge-invoice');
    Route::post('/clients/{client}/invoices/{invoice}/charge', [AdminChargeController::class, 'chargeInvoice'])
        ->name('invoices.charge')
        ->middleware('auth');
    Route::post('clients/{client}/invoices/{invoice}/sync-xero', [ClientController::class, 'syncXero'])
        ->name('invoices.syncXero');
});

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Xero OAuth
    Route::prefix('xero')->name('xero.')->group(function () {
        Route::get('/', [XeroConnectionController::class, 'index'])->name('index');
        Route::get('/connect', [XeroConnectionController::class, 'connect'])->name('connect');
        Route::get('/callback', [XeroConnectionController::class, 'callback'])->name('callback');
        Route::post('/{connection}/refresh', [XeroConnectionController::class, 'refresh'])->name('refresh');
        Route::delete('/{connection}/disconnect', [XeroConnectionController::class, 'disconnect'])->name('disconnect');

        Route::get('/{xeroConnection}/tenants/{tenant}/contacts', [XeroContactController::class, 'contacts'])->name('contacts');
        Route::post('/tenants/{tenant}/contacts', [XeroContactController::class, 'syncTenant'])->name('contacts.sync');
        Route::post('/assign-contact', [XeroContactController::class, 'assign'])->name('contacts.assign');
        Route::post('bulk-assign', [XeroContactController::class, 'bulkAssign'])
            ->name('contacts.bulk-assign');
        Route::delete('contacts/{xeroContact}/match', [XeroContactController::class, 'clearMatch'])
            ->name('contacts.clear-match');
        Route::post('/contacts/auto-match', [XeroContactController::class, 'autoMatch'])
            ->name('contacts.auto-match');

        Route::post('/sync-invoices', [XeroInvoiceController::class, 'sync'])->name('sync-invoices');
        Route::post('/tenant/{tenant}/sync', [XeroSyncController::class, 'sync'])->name('tenants.sync');

        Route::get('tenants/{tenant}/bank-settings', [XeroTenantSettingsController::class, 'edit'])
            ->name('tenants.bank-settings');
        Route::put('tenants/{tenant}/bank-settings', [XeroTenantSettingsController::class, 'update'])
            ->name('tenants.bank-settings-update');
    });
    Route::resource('directDebitPayment', DirectDebitPaymentController::class)->only(['index', 'show']);
    Route::post('directDebitPayment/{directDebitPayment}/cancel',       [DirectDebitPaymentController::class, 'cancel'])->name('directDebitPayment.cancel');
    Route::post('directDebitPayment/{directDebitPayment}/retry',        [DirectDebitPaymentController::class, 'retry'])->name('directDebitPayment.retry');
    Route::post('directDebitPayment/{directDebitPayment}/post-to-xero', [DirectDebitPaymentController::class, 'postToXero'])->name('directDebitPayment.post-to-xero');
});

Route::prefix('settings/xero/tenants/{tenant}')
    ->name('xero.tenants.')
    ->group(function () {
        Route::get('/',        [XeroTenantSettingsController::class, 'edit'])   ->name('edit');
        Route::put('/',        [XeroTenantSettingsController::class, 'update']) ->name('update');
    });
Route::get('/xero/auth/callback',   [XeroConnectionController::class, 'callback'])->name('xero.auth.callback');
Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->name('webhooks.stripe');
Route::post('/webhooks/xero', \App\Http\Controllers\Admin\XeroWebhookController::class)
    ->name('webhooks.xero');
