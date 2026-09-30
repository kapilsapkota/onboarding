<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatch;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Services\StripeAccountResolver;
use Database\Seeders\StripeAccountSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

function makeStripeCompany(string $name = 'Acme'): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(5),
    ]);
}

function makeStripeAccount(?Company $company = null, array $overrides = []): StripeAccount
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

test('new columns default to null so legacy rows keep working', function () {
    $client = Client::create(['company_name' => 'Legacy Co', 'billing_email' => 'a@b.com']);

    expect($client->company_id)->toBeNull()
        ->and($client->company)->toBeNull();
});

test('secrets are encrypted at rest and hidden from serialization', function () {
    $account = makeStripeAccount();

    $raw = DB::table('stripe_accounts')->where('id', $account->id)->first();

    expect($raw->secret_key)->not->toBe('sk_test_123')
        ->and($account->secret_key)->toBe('sk_test_123')
        ->and($account->webhook_secret)->toBe('whsec_123')
        ->and($account->toArray())->not->toHaveKeys(['secret_key', 'webhook_secret']);
});

test('public_id is auto-generated and unique per account', function () {
    $a = makeStripeAccount();
    $b = makeStripeAccount();

    expect($a->public_id)->not->toBeEmpty()
        ->and($b->public_id)->not->toBeEmpty()
        ->and($a->public_id)->not->toBe($b->public_id);
});

test('setAsDefault keeps exactly one default per company', function () {
    $company = makeStripeCompany();
    $first = makeStripeAccount($company, ['is_default' => true]);
    $second = makeStripeAccount($company);

    $second->setAsDefault();

    expect($first->refresh()->is_default)->toBeFalse()
        ->and($second->refresh()->is_default)->toBeTrue();
});

test('resolver routes by business context and falls back to legacy', function () {
    config(['services.stripe.secret' => 'sk_legacy', 'services.stripe.key' => 'pk_legacy']);
    $resolver = new StripeAccountResolver;

    // No accounts at all: everything falls back to global config.
    expect($resolver->forCompany(999))->toBeNull()
        ->and($resolver->legacyAccount())->toBeNull()
        ->and($resolver->clientFor(null))->toBeInstanceOf(StripeClient::class)
        ->and($resolver->publishableKeyFor(null))->toBe('pk_legacy');

    $company = makeStripeCompany();
    $default = makeStripeAccount($company, ['is_default' => true]);
    $other = makeStripeAccount($company);

    expect($resolver->forCompany($company->id)->id)->toBe($default->id)
        ->and($resolver->forAccount($other->id)->id)->toBe($other->id)
        ->and($resolver->forPublicId($default->public_id)->id)->toBe($default->id)
        ->and($resolver->publishableKeyFor($default))->toBe('pk_test_123');

    // Unknown internal id throws instead of silently using another account.
    expect(fn () => $resolver->forAccount(999999))->toThrow(ModelNotFoundException::class);

    // Disabled accounts never resolve via public links.
    $default->update(['status' => 'disabled']);
    expect(fn () => $resolver->forPublicId($default->public_id))->toThrow(ModelNotFoundException::class);
});

test('resolver finds account from our own customer mirror, never from request data', function () {
    $company = makeStripeCompany();
    $account = makeStripeAccount($company);
    StripeCustomer::create(['stripe_customer_id' => 'cus_123', 'stripe_data' => [], 'stripe_account_id' => $account->id, 'email' => 'a@b.com']);

    $resolver = new StripeAccountResolver;

    expect($resolver->forStripeCustomer('cus_123')?->id)->toBe($account->id)
        ->and($resolver->forStripeCustomer('cus_unknown'))->toBeNull();
});

test('relations link company, client, payments and stripe records', function () {
    $company = makeStripeCompany();
    $account = makeStripeAccount($company);
    $client = Client::create(['company_name' => 'Acme Client', 'company_id' => $company->id]);
    $customer = StripeCustomer::create(['stripe_customer_id' => 'cus_1', 'stripe_data' => [], 'stripe_account_id' => $account->id]);
    $method = StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id, 'stripe_data' => [], 'stripe_account_id' => $account->id,
        'stripe_payment_method_id' => 'pm_1', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);
    $batch = StripeChargeBatch::create(['reference' => 'B1', 'stripe_account_id' => $account->id, 'status' => 'pending']);

    expect($company->stripeAccounts->pluck('id')->all())->toBe([$account->id])
        ->and($company->clients->pluck('id')->all())->toBe([$client->id])
        ->and($client->company->id)->toBe($company->id)
        ->and($customer->stripeAccount->id)->toBe($account->id)
        ->and($method->stripeAccount->id)->toBe($account->id)
        ->and($batch->stripeAccount->id)->toBe($account->id)
        ->and($account->stripeCustomers->pluck('id')->all())->toBe([$customer->id]);
});

test('legacy seeder is idempotent and backfills only unlinked rows', function () {
    config(['services.stripe.secret' => 'sk_legacy', 'services.stripe.key' => 'pk_legacy', 'services.stripe.webhook_secret' => 'whsec_legacy']);
    $company = makeStripeCompany();

    $preCustomer = StripeCustomer::create(['stripe_customer_id' => 'cus_pre', 'stripe_data' => [], 'email' => 'pre@b.com']);
    $preClient = Client::create(['company_name' => 'Pre', 'company_id' => $company->id]);

    $this->seed(StripeAccountSeeder::class);
    $this->seed(StripeAccountSeeder::class);

    expect(StripeAccount::where('is_legacy', true)->count())->toBe(1);

    $legacy = StripeAccount::where('is_legacy', true)->firstOrFail();
    expect($legacy->company_id)->toBe($company->id)
        ->and($legacy->is_default)->toBeTrue()
        ->and($preCustomer->refresh()->stripe_account_id)->toBe($legacy->id);

    // Rows already on a real account are never touched.
    $real = makeStripeAccount($company);
    $kept = StripeCustomer::create(['stripe_customer_id' => 'cus_kept', 'stripe_data' => [], 'stripe_account_id' => $real->id]);
    $this->seed(StripeAccountSeeder::class);
    expect($kept->refresh()->stripe_account_id)->toBe($real->id);

    // DD payments inherit company from their client.
    $owner = User::factory()->create();
    $connection = XeroConnection::create([
        'user_id' => $owner->id, 'access_token' => 'x', 'refresh_token' => 'y',
        'token_expires_at' => now()->addHour(),
    ]);
    $tenant = XeroTenant::create([
        'xero_connection_id' => $connection->id, 'tenant_id' => 't1', 'tenant_name' => 'T1',
    ]);
    $invoice = XeroInvoice::create([
        'xero_tenant_id' => $tenant->id, 'xero_invoice_id' => (string) Str::uuid(),
        'type' => 'ACCREC', 'status' => 'AUTHORISED',
    ]);
    $fresh = DirectDebitPayment::create([
        'xero_invoice_id' => $invoice->id,
        'xero_tenant_id' => $tenant->id,
        'xero_invoice_xero_id' => $invoice->xero_invoice_id,
        'client_id' => $preClient->id, 'amount' => 20, 'status' => 'pending', 'initiated_at' => now(),
    ]);
    $this->seed(StripeAccountSeeder::class);
    expect($fresh->refresh()->stripe_account_id)->toBe($legacy->id)
        ->and($fresh->refresh()->company_id)->toBe($company->id);
});

test('legacy seeder skips cleanly without stripe credentials', function () {
    config(['services.stripe.secret' => null]);

    $this->seed(StripeAccountSeeder::class);

    expect(StripeAccount::count())->toBe(0);
});
