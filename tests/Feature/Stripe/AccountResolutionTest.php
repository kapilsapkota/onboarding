<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Services\StripeAccountResolver;
use App\Services\StripeService;
use Illuminate\Support\Str;

function makeResCompany(string $name = 'ResCo'): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(5),
    ]);
}

function makeResAccount(?Company $company = null, array $overrides = []): StripeAccount
{
    return StripeAccount::create(array_merge([
        'company_id' => $company?->id,
        'display_name' => 'Res account',
        'publishable_key' => 'pk_test_res',
        'secret_key' => 'sk_test_res',
        'webhook_secret' => 'whsec_res',
        'status' => 'active',
    ], $overrides));
}

function makeResClient(array $overrides = []): Client
{
    return Client::create(array_merge([
        'company_name' => 'Res Client',
    ], $overrides));
}

test('forClient falls back through customer, company, then legacy', function () {
    $resolver = new StripeAccountResolver;

    expect($resolver->forClient(null))->toBeNull()
        ->and($resolver->forClient(makeResClient()))->toBeNull();

    $company = makeResCompany();
    $default = makeResAccount($company, ['is_default' => true]);

    $client = makeResClient(['company_id' => $company->id]);

    expect($resolver->forClient($client)->id)->toBe($default->id);

    // The mirrored customer's own account wins over the company default.
    $owned = makeResAccount();
    StripeCustomer::create([
        'stripe_customer_id' => 'cus_res_owned',
        'stripe_account_id' => $owned->id,
        'stripe_data' => [],
    ]);
    $client->update(['stripe_customer_id' => 'cus_res_owned']);

    expect($resolver->forClient($client->fresh())->id)->toBe($owned->id);
});

test('forClient skips inactive customer accounts', function () {
    $resolver = new StripeAccountResolver;

    $company = makeResCompany('ResInactiveCo');
    $default = makeResAccount($company, ['is_default' => true]);
    $inactive = makeResAccount(null, ['status' => 'inactive']);
    StripeCustomer::create([
        'stripe_customer_id' => 'cus_res_inactive',
        'stripe_account_id' => $inactive->id,
        'stripe_data' => [],
    ]);

    $client = makeResClient(['company_id' => $company->id, 'stripe_customer_id' => 'cus_res_inactive']);

    expect($resolver->forClient($client)->id)->toBe($default->id);
});

test('forDirectDebitPayment prefers stamped account then client chain', function () {
    $resolver = new StripeAccountResolver;

    expect($resolver->forDirectDebitPayment(null))->toBeNull();

    $company = makeResCompany('ResDdCo');
    $companyAccount = makeResAccount($company, ['is_default' => true]);
    $stamped = makeResAccount();

    $owner = User::factory()->create();
    $connection = XeroConnection::create([
        'user_id' => $owner->id, 'access_token' => 'x', 'refresh_token' => 'y',
        'token_expires_at' => now()->addHour(),
    ]);
    $tenant = XeroTenant::create([
        'xero_connection_id' => $connection->id, 'tenant_id' => 't-res', 'tenant_name' => 'T-Res',
    ]);
    $client = makeResClient(['company_id' => $company->id]);
    $invoice = XeroInvoice::create([
        'xero_tenant_id' => $tenant->id, 'xero_invoice_id' => (string) Str::uuid(),
        'type' => 'ACCREC', 'status' => 'AUTHORISED', 'client_id' => $client->id,
    ]);

    $payment = DirectDebitPayment::create([
        'xero_invoice_id' => $invoice->id,
        'xero_tenant_id' => $tenant->id,
        'xero_invoice_xero_id' => $invoice->xero_invoice_id,
        'xero_invoice_number' => 'INV-RES',
        'client_id' => $client->id,
        'company_id' => $company->id,
        'amount' => 10, 'status' => 'pending', 'initiated_at' => now(),
        'stripe_account_id' => $stamped->id,
    ]);

    // Stamped account wins over the company default.
    expect($resolver->forDirectDebitPayment($payment->fresh())->id)->toBe($stamped->id);

    // Inactive stamp falls through to the company default.
    $stamped->update(['status' => 'inactive']);

    expect($resolver->forDirectDebitPayment($payment->fresh())->id)->toBe($companyAccount->id);
});

test('stripe service binds to accounts and resolves clients', function () {
    $service = app(StripeService::class);

    $account = makeResAccount();

    expect($service->forAccount($account))->toBeInstanceOf(StripeService::class)
        ->and($service->forAccount(null))->toBeInstanceOf(StripeService::class);
});
