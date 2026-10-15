<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

if (! function_exists('makeDashboardDdrCompany')) {
    function makeDashboardDdrCompany(string $name = 'Acme'): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
        ]);
    }
}

if (! function_exists('makeDashboardDdrAccount')) {
    function makeDashboardDdrAccount(?Company $company = null, array $overrides = []): StripeAccount
    {
        return StripeAccount::create(array_merge([
            'company_id' => $company?->id,
            'display_name' => 'Test account',
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_123',
            'status' => 'active',
        ], $overrides));
    }
}

test('dashboard lists visible accounts with ddr links', function () {
    $user = User::factory()->create();
    $account = makeDashboardDdrAccount(null, ['display_name' => 'Primary AUD']);
    $disabled = makeDashboardDdrAccount(null, ['display_name' => 'Old', 'status' => 'disabled']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Stripe Accounts', false);
    $response->assertSee('Primary AUD');
    $response->assertSee($account->ddrUrl(), false);
    $response->assertDontSee('Old');
});

test('dashboard scopes accounts to member companies', function () {
    $ownCompany = makeDashboardDdrCompany('Own Co');
    $otherCompany = makeDashboardDdrCompany('Other Co');
    $own = makeDashboardDdrAccount($ownCompany, ['display_name' => 'Own Account']);
    $other = makeDashboardDdrAccount($otherCompany, ['display_name' => 'Other Account']);

    $member = User::factory()->create();
    $member->companies()->attach($ownCompany->id);

    $response = $this->actingAs($member)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Own Account');
    $response->assertDontSee('Other Account');
});

test('dashboard offers whatsapp share with editable default text', function () {
    $user = User::factory()->create();
    makeDashboardDdrAccount(null, ['display_name' => 'Primary AUD']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('WhatsApp', false);
    $response->assertSee('whatsapp-share-modal', false);
    $response->assertSee('whatsapp-share-text', false);
    $response->assertSee('We urgently need to update your direct debit details', false);
    $response->assertSee('wa.me/?text=', false);
});

test('dashboard shows company logo for each account', function () {
    $user = User::factory()->create();
    $company = makeDashboardDdrCompany('Logo Co');
    $company->update(['logo' => 'acme-logo.png']);
    $account = makeDashboardDdrAccount($company, ['display_name' => 'Logo Account']);
    $noLogo = makeDashboardDdrAccount(null, ['display_name' => 'No Logo Account']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('images/acme-logo.png', false);
    $response->assertSee('alt="Logo Co logo"', false);
    $response->assertSee('No Logo Account');
});

test('dashboard shows clickable bulk direct debit stats and recent batches', function () {
    $user = User::factory()->create();

    $batch = StripeChargeBatch::create([
        'reference' => 'BATCH-DASH-1',
        'customer_count' => 3,
        'total_amount' => 30000,
        'currency' => 'aud',
        'status' => 'processing',
    ]);

    $customer = StripeCustomer::create([
        'stripe_customer_id' => 'cus_dash_1',
        'name' => 'Dash Customer',
        'stripe_data' => [],
    ]);

    $method = StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id,
        'stripe_payment_method_id' => 'pm_dash_1',
        'type' => 'au_becs_debit',
        'status' => 'active',
        'stripe_data' => [],
    ]);

    $itemBase = ['batch_id' => $batch->id, 'stripe_customer_id' => $customer->id, 'stripe_payment_method_id' => $method->id, 'currency' => 'aud'];

    StripeChargeBatchItem::create($itemBase + ['amount' => 10000, 'status' => 'succeeded']);
    StripeChargeBatchItem::create($itemBase + ['amount' => 10000, 'status' => 'processing']);
    StripeChargeBatchItem::create($itemBase + ['amount' => 10000, 'status' => 'failed']);

    $connection = XeroConnection::create([
        'user_id' => $user->id, 'access_token' => 'x', 'refresh_token' => 'y',
        'token_expires_at' => now()->addHour(),
    ]);
    $tenant = XeroTenant::create([
        'xero_connection_id' => $connection->id, 'tenant_id' => 't-dash', 'tenant_name' => 'T-Dash',
    ]);
    $dashClient = Client::create(['company_name' => 'Dash Client']);
    $invoice = XeroInvoice::create([
        'xero_tenant_id' => $tenant->id, 'xero_invoice_id' => (string) Str::uuid(),
        'type' => 'ACCREC', 'status' => 'AUTHORISED', 'client_id' => $dashClient->id,
    ]);
    DirectDebitPayment::create([
        'xero_invoice_id' => $invoice->id, 'xero_tenant_id' => $tenant->id,
        'xero_invoice_xero_id' => $invoice->xero_invoice_id, 'xero_invoice_number' => 'INV-DASH',
        'client_id' => $dashClient->id, 'amount' => 100, 'status' => 'settled', 'initiated_at' => now(),
    ]);
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Bulk Direct Debit', false);
    $response->assertSee('$100.00', false);
    $response->assertSee('BATCH-DASH-1', false);
    $response->assertSee('Recent bulk batches', false);
    $response->assertSee('Settled Direct Debits', false);
    $response->assertSee(route('admin.stripe.batches.index'), false);
    $response->assertSee(route('admin.stripe.batches.show', $batch), false);
    $response->assertSee(route('admin.stripe.bulk-charge'), false);
});
