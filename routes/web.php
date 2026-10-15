<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DirectDebitPaymentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StripeAccountController;
use App\Http\Controllers\Admin\StripeBulkChargeController;
use App\Http\Controllers\Admin\StripeCustomerController;
use App\Http\Controllers\Admin\StripePayoutController;
use App\Http\Controllers\Admin\StripeTransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\XeroConnectionController;
use App\Http\Controllers\Admin\XeroContactController;
use App\Http\Controllers\Admin\XeroCustomerController;
use App\Http\Controllers\Admin\XeroInvoiceController;
use App\Http\Controllers\Admin\XeroInvoiceManagerController;
use App\Http\Controllers\Admin\XeroReconcileController;
use App\Http\Controllers\Admin\XeroRepeatingInvoiceController;
use App\Http\Controllers\Admin\XeroSyncController;
use App\Http\Controllers\Admin\XeroTenantController;
use App\Http\Controllers\Admin\XeroTenantSettingsController;
use App\Http\Controllers\Admin\XeroWebhookController;
use App\Http\Controllers\AdminChargeController;
use App\Http\Controllers\Api\ClientMigrationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DdrController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteDeliveryController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ddr', function () {
    return view('ddr');
});

// Per-company DDR forms, resolved server-side by the account's public uuid.
Route::get('/ddr/{publicId}', [DdrController::class, 'show'])->name('ddr.account');
Route::post('/ddr/{publicId}/setup-intent', [DdrController::class, 'setupIntent'])->name('ddr.account.setup-intent');
Route::post('/ddr/{publicId}', [DdrController::class, 'store'])->name('ddr.account.store');

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
Route::post('/webhooks/stripe/{stripeAccount}', [StripeWebhookController::class, 'account'])->name('webhooks.stripe.account');
Route::post('/webhooks/xero', XeroWebhookController::class)->name('webhooks.xero');

Route::post('/onboarding/create-customer', [OnboardingController::class, 'createCustomer'])
    ->name('onboarding.create-customer');

Route::post('/onboarding/direct-debit', [OnboardingController::class, 'directDebitStore'])
    ->name('onboarding.direct-debit');

Route::post('/onboarding/setup-intent', [OnboardingController::class, 'createSetupIntent'])
    ->name('onboarding.setup-intent');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Route::redirect('/onboarding', '/');
Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
Route::get('/onboarding/thanks', [OnboardingController::class, 'thanks'])->name('onboarding.thanks');

Route::middleware(['auth'])->prefix('admin')->name('clients.')->group(function () {
    Route::get('/clients-export', [ClientController::class, 'export'])
        ->name('export');
    Route::get('/clients', [ClientController::class, 'index'])->name('index');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('destroy');
    Route::patch('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('status');
    Route::post('/clients/{client}/assign-xero-contact',
        [ClientController::class, 'assignXeroContact']
    )->name('assign-xero-contact');
    Route::post('/admin/clients/{client}/charge', [AdminChargeController::class, 'charge'])->name('charge');
    Route::post('/clients/{client}/charge-invoice', [AdminChargeController::class, 'chargeInvoice'])
        ->name('charge-invoice');
    Route::post('/clients/{client}/invoices/{invoice}/charge', [AdminChargeController::class, 'chargeInvoice'])
        ->name('invoices.charge')
        ->middleware('auth');
    Route::post('clients/{client}/invoices/{invoice}/sync-xero', [ClientController::class, 'syncXero'])
        ->name('invoices.syncXero');
    Route::post(
        '/clients/{client}/payment-methods/setup',
        [ClientController::class, 'createPaymentMethodSetup']
    )->name('payment-methods.setup');

    Route::post(
        '/clients/{client}/payment-methods',
        [ClientController::class, 'storePaymentMethod']
    )->name('payment-methods.store');
    Route::patch(
        '/clients/{client}/payment-methods/{paymentMethod}/default',
        [ClientController::class, 'makeDefaultPaymentMethod']
    )->name('payment-methods.default');

});

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    // ── Xero ─────────────────────────────────────────────────────────────────
    Route::prefix('xero')->name('xero.')->group(function () {
        Route::get('/', [XeroConnectionController::class, 'index'])->name('index');
        Route::get('/connect', [XeroConnectionController::class, 'connect'])->name('connect');
        Route::get('/callback', [XeroConnectionController::class, 'callback'])->name('callback');
        Route::post('/{connection}/refresh', [XeroConnectionController::class, 'refresh'])->name('refresh');
        Route::delete('/{connection}/disconnect', [XeroConnectionController::class, 'disconnect'])->name('disconnect');
        Route::get('/{xeroConnection}/tenants/{tenant}/contacts', [XeroContactController::class, 'contacts'])->name('contacts');
        Route::post('/tenants/{tenant}/contacts', [XeroContactController::class, 'syncTenant'])->name('contacts.sync');
        Route::post('/assign-contact', [XeroContactController::class, 'assign'])->name('contacts.assign');
        Route::post('bulk-assign', [XeroContactController::class, 'bulkAssign'])->name('contacts.bulk-assign');
        Route::delete('contacts/{xeroContact}/match', [XeroContactController::class, 'clearMatch'])->name('contacts.clear-match');
        Route::post('/contacts/auto-match', [XeroContactController::class, 'autoMatch'])->name('contacts.auto-match');
        Route::get('/contacts/search', [XeroContactController::class, 'search'])->name('contacts.search');
        Route::post('/sync-invoices', [XeroInvoiceController::class, 'sync'])->name('sync-invoices');
        Route::post('/tenant/{tenant}/sync', [XeroSyncController::class, 'sync'])->name('tenants.sync');
        Route::get('tenants/{tenant}/bank-settings', [XeroTenantSettingsController::class, 'edit'])->name('tenants.bank-settings');
        Route::put('tenants/{tenant}/bank-settings', [XeroTenantSettingsController::class, 'update'])->name('tenants.bank-settings-update');

        // ── Organisations (source-of-truth hub: tenant ↔ company ↔ Stripe) ─
        Route::get('organisations', [XeroTenantController::class, 'index'])->name('organisations.index');
        Route::get('organisations/{organisation}', [XeroTenantController::class, 'show'])->name('organisations.show');
        Route::get('organisations/{organisation}/edit', [XeroTenantController::class, 'edit'])->name('organisations.edit');
        Route::put('organisations/{organisation}', [XeroTenantController::class, 'update'])->name('organisations.update');

        // ── Reconcile all customers (every organisation) ───────────────
        Route::get('reconcile', [XeroReconcileController::class, 'index'])->name('reconcile.index');
        Route::post('reconcile/auto-match', [XeroReconcileController::class, 'autoMatch'])->name('reconcile.auto-match');

        // ── Invoices (all tenants, Xero write-through CRUD) ──────────────
        Route::get('invoices', [XeroInvoiceManagerController::class, 'index'])->name('invoices.index');
        Route::post('invoices/sync-all', [XeroInvoiceManagerController::class, 'syncAll'])->name('invoices.sync-all');
        Route::get('invoices/create', [XeroInvoiceManagerController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [XeroInvoiceManagerController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [XeroInvoiceManagerController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/edit', [XeroInvoiceManagerController::class, 'edit'])->name('invoices.edit');
        Route::put('invoices/{invoice}', [XeroInvoiceManagerController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [XeroInvoiceManagerController::class, 'destroy'])->name('invoices.destroy');
        Route::post('invoices/{invoice}/authorise', [XeroInvoiceManagerController::class, 'authorise'])->name('invoices.authorise');
        Route::post('invoices/tenant/{tenant}/sync', [XeroInvoiceManagerController::class, 'sync'])->name('invoices.sync');

        // ── Customers (Xero contacts, all tenants) ────────────────────────
        Route::get('customers', [XeroCustomerController::class, 'index'])->name('customers.index');
        Route::post('customers/sync-all', [XeroCustomerController::class, 'syncAll'])->name('customers.sync-all');
        Route::get('customers/create', [XeroCustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [XeroCustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [XeroCustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [XeroCustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [XeroCustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [XeroCustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('customers/{customer}/restore', [XeroCustomerController::class, 'restore'])->name('customers.restore');
        Route::post('customers/tenant/{tenant}/sync', [XeroCustomerController::class, 'sync'])->name('customers.sync');

        // ── Repeating invoices (all tenants, Xero write-through CRUD) ─────
        Route::get('repeating-invoices', [XeroRepeatingInvoiceController::class, 'index'])->name('repeating-invoices.index');
        Route::post('repeating-invoices/sync-all', [XeroRepeatingInvoiceController::class, 'syncAll'])->name('repeating-invoices.sync-all');
        Route::get('repeating-invoices/create', [XeroRepeatingInvoiceController::class, 'create'])->name('repeating-invoices.create');
        Route::post('repeating-invoices', [XeroRepeatingInvoiceController::class, 'store'])->name('repeating-invoices.store');
        Route::get('repeating-invoices/{repeatingInvoice}', [XeroRepeatingInvoiceController::class, 'show'])->name('repeating-invoices.show');
        Route::get('repeating-invoices/{repeatingInvoice}/edit', [XeroRepeatingInvoiceController::class, 'edit'])->name('repeating-invoices.edit');
        Route::put('repeating-invoices/{repeatingInvoice}', [XeroRepeatingInvoiceController::class, 'update'])->name('repeating-invoices.update');
        Route::delete('repeating-invoices/{repeatingInvoice}', [XeroRepeatingInvoiceController::class, 'destroy'])->name('repeating-invoices.destroy');
        Route::post('repeating-invoices/{repeatingInvoice}/authorise', [XeroRepeatingInvoiceController::class, 'authorise'])->name('repeating-invoices.authorise');
        Route::post('repeating-invoices/tenant/{tenant}/sync', [XeroRepeatingInvoiceController::class, 'sync'])->name('repeating-invoices.sync');
    });

    // ── Payments ──────────────────────────────────────────────────────────────
    Route::resource('directDebitPayment', DirectDebitPaymentController::class)->only(['index', 'show']);
    Route::post('directDebitPayment/{directDebitPayment}/cancel', [DirectDebitPaymentController::class, 'cancel'])->name('directDebitPayment.cancel');
    Route::post('directDebitPayment/{directDebitPayment}/retry', [DirectDebitPaymentController::class, 'retry'])->name('directDebitPayment.retry');
    Route::post('directDebitPayment/{directDebitPayment}/post-to-xero', [DirectDebitPaymentController::class, 'postToXero'])->name('directDebitPayment.post-to-xero');

    // ── Payouts ───────────────────────────────────────────────────────────────
    Route::prefix('payouts')->name('payouts.')->group(function () {
        Route::get('/', [StripePayoutController::class, 'index'])->name('index');
        Route::get('{payout}', [StripePayoutController::class, 'show'])->name('show');
    });

    Route::prefix('stripe/transactions')
        ->name('stripe.transactions.')
        ->middleware(['auth'])
        ->group(function () {

            Route::get('/', [
                StripeTransactionController::class,
                'index',
            ])->name('index');

            Route::get('/{transactionId}', [
                StripeTransactionController::class,
                'show',
            ])->name('show');
        });

    Route::prefix('stripe')->name('stripe.')->group(function () {
        Route::get('bulk-charge', [StripeBulkChargeController::class, 'index'])->name('bulk-charge');
        Route::post('bulk-charge/review', [StripeBulkChargeController::class, 'review'])->name('bulk-charge.review');
        Route::post('bulk-charge/confirm', [StripeBulkChargeController::class, 'confirm'])->name('bulk-charge.confirm');
        Route::get('batches/{batch}', [StripeBulkChargeController::class, 'showBatch'])->name('batches.show');
        Route::get('batches', [StripeBulkChargeController::class, 'batches'])->name('batches.index');
        Route::post('items/{item}/retry', [StripeBulkChargeController::class, 'retry'])->name('items.retry');
        Route::post('items/{item}/cancel', [StripeBulkChargeController::class, 'cancel'])->name('items.cancel');
        Route::get('customers', [StripeCustomerController::class, 'index'])->name('customers.index');
        Route::post('customers/sync', [StripeCustomerController::class, 'sync'])->name('customers.sync');
        Route::get('customers/{customer}', [StripeCustomerController::class, 'show'])->name('customers.show');
    });

    // ── Stripe Accounts (multi-tenant credentials, DDR links) ───────────────
    Route::resource('stripe-accounts', StripeAccountController::class)->except(['show', 'create', 'edit']);

    // ── Quotes ────────────────────────────────────────────────────────────────
    Route::resource('quotes', QuoteController::class);
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::post('quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
    Route::patch('quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.status');
    Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');

    Route::prefix('quotes/{quote}/deliveries')->name('quotes.deliveries.')->group(function () {
        Route::get('{delivery}/status', [QuoteDeliveryController::class, 'status'])
            ->name('status');

        Route::post('{delivery}/attempts/{attempt}/retry', [QuoteDeliveryController::class, 'retryAttempt'])
            ->name('attempts.retry');
    });

    Route::resource('categories', CategoryController::class);
    Route::post('/categories/{category}/duplicate', [CategoryController::class, 'duplicate'])->name('categories.duplicate');
    Route::resource('products', ProductController::class);
    Route::resource('companies', CompanyController::class);
});

// ── Xero settings ─────────────────────────────────────────────────────────────
Route::prefix('settings/xero/tenants/{tenant}')
    ->name('xero.tenants.')
    ->group(function () {
        Route::get('/', [XeroTenantSettingsController::class, 'edit'])->name('edit');
        Route::put('/', [XeroTenantSettingsController::class, 'update'])->name('update');
    });

Route::get('/xero/auth/callback', [XeroConnectionController::class, 'callback'])
    ->name('xero.auth.callback');

Route::get('quotes/{quote}/sign', [PublicQuoteController::class, 'showSignForm'])->name('quotes.sign');
Route::post('quotes/{quote}/save-signature', [PublicQuoteController::class, 'saveSignature'])->name('quotes.save-signature');

Route::get('/q/{token}', [PublicQuoteController::class, 'show'])
    ->name('quotes.public.view')
    ->where('token', '[A-Za-z0-9]{12}');
// ── Public customer quote view (no auth, opaque token) ────────────────────────
Route::get('/q/{token}', [PublicQuoteController::class, 'show'])
    ->name('quotes.public.view')
    ->where('token', '[A-Za-z0-9]{12}');

Route::get('/q/{token}/pdf', [PublicQuoteController::class, 'pdf'])
    ->name('quotes.public.pdf')
    ->where('token', '[A-Za-z0-9]{12}');

Route::get('/quotes/{quote}/signatures/{signature}', [
    PublicQuoteController::class,
    'signature',
])->name('quotes.signature');

Route::post('api/internal/migration/clients', [ClientMigrationController::class, 'store']
)->middleware('migration.token');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
    Route::post('permissions/bulk-group', [PermissionController::class, 'bulkUpdateGroup'])->name('permissions.bulk-group');
    Route::delete('permissions/bulk', [PermissionController::class, 'bulkDestroy'])->name('permissions.bulk-destroy');
    Route::resource('permissions', PermissionController::class)->except(['show', 'create', 'edit']);
    Route::get('activity-logs/{activityLog}/body', [ActivityLogController::class, 'body'])->name('activity-logs.body');
    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show', 'destroy']);
});
