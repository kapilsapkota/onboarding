<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeBalanceTransaction;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\StripePayout;
use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Notifications\DirectDebitFailedAdminNotification;
use App\Notifications\StripePaymentSuccessNotification;
use App\Notifications\StripePayoutSuccessNotification;
use App\Services\StripePayoutSyncService;
use Database\Seeders\StripeAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makePayoutCompany(string $name = 'Payout Co'): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(5),
    ]);
}

function makePayoutAccount(?Company $company = null, array $overrides = []): StripeAccount
{
    return StripeAccount::create(array_merge([
        'company_id' => $company?->id,
        'display_name' => 'Payout Account',
        'publishable_key' => 'pk_test_123',
        'secret_key' => 'sk_test_123',
        'webhook_secret' => 'whsec_test123',
        'status' => 'active',
    ], $overrides));
}

function payoutArray(string $id = 'po_test1'): array
{
    return [
        'id' => $id, 'object' => 'payout', 'status' => 'paid', 'type' => 'bank_account',
        'method' => 'standard', 'currency' => 'aud', 'amount' => 5000,
        'arrival_date' => time(), 'created' => time(), 'automatic' => true,
    ];
}

function btArray(string $id = 'txn_test1'): array
{
    return [
        'id' => $id, 'object' => 'balance_transaction', 'type' => 'charge',
        'status' => 'available', 'currency' => 'aud', 'amount' => 10000,
        'fee' => 175, 'net' => 9825, 'created' => time(), 'available_on' => time(),
        'source' => [
            'object' => 'charge', 'id' => 'ch_test1', 'payment_intent' => 'pi_test1',
            'customer' => 'cus_test1', 'description' => 'Test charge',
        ],
    ];
}

test('sync service stamps payouts and balance transactions with the account', function () {
    $account = makePayoutAccount(makePayoutCompany());

    $service = new StripePayoutSyncService(null, $account);
    $payout = $service->upsertPayout(payoutArray());
    $bt = $service->upsertBalanceTransaction(btArray());

    expect($payout->stripe_account_id)->toBe($account->id)
        ->and($bt->stripe_account_id)->toBe($account->id)
        ->and($account->refresh()->stripePayouts->pluck('id')->all())->toBe([$payout->id]);
});

test('legacy sync never wipes an existing account stamp', function () {
    $account = makePayoutAccount(makePayoutCompany());
    $payout = (new StripePayoutSyncService(null, $account))->upsertPayout(payoutArray('po_keep'));

    (new StripePayoutSyncService)->upsertPayout(payoutArray('po_keep'));

    expect($payout->refresh()->stripe_account_id)->toBe($account->id);
});

test('per-account payout webhook stamps the payout and notifies with context', function () {
    Notification::fake();

    $company = makePayoutCompany('Webhook Payout Co');
    $account = makePayoutAccount($company, ['webhook_secret' => 'whsec_pay']);

    $payload = json_encode([
        'id' => 'evt_po', 'object' => 'event', 'type' => 'payout.paid',
        'data' => ['object' => payoutArray('po_hook1')],
    ]);
    $timestamp = time();
    $sig = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_pay');

    $response = test()->call('POST', '/webhooks/stripe/'.$account->id, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$sig}",
    ], $payload);

    $response->assertOk();

    $row = StripePayout::where('stripe_payout_id', 'po_hook1')->firstOrFail();
    expect($row->stripe_account_id)->toBe($account->id);

    Notification::assertSentTo(
        new AnonymousNotifiable,
        StripePayoutSuccessNotification::class,
        fn ($n) => $n->stripeAccountId === $account->id
    );
});

test('payment notifications carry account and company in subject and body', function () {
    $company = makePayoutCompany('Notify Co');
    $account = makePayoutAccount($company, ['display_name' => 'Notify Account']);

    $batch = StripeChargeBatch::create([
        'reference' => 'B-NOT', 'stripe_account_id' => $account->id, 'status' => 'processing',
    ]);
    $customer = StripeCustomer::create([
        'stripe_customer_id' => 'cus_not', 'stripe_data' => [],
        'stripe_account_id' => $account->id, 'name' => 'Notify Customer',
    ]);
    $method = StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id, 'stripe_data' => [],
        'stripe_account_id' => $account->id, 'stripe_payment_method_id' => 'pm_not_1',
        'type' => 'au_becs_debit', 'status' => 'active',
    ]);
    $item = StripeChargeBatchItem::create([
        'batch_id' => $batch->id, 'stripe_account_id' => $account->id,
        'stripe_customer_id' => $customer->id, 'stripe_payment_method_id' => $method->id,
        'amount' => 2500, 'status' => 'succeeded',
    ]);

    $user = User::factory()->create(['name' => 'Staff']);
    $mail = (new StripePaymentSuccessNotification($item->id))->toMail($user);

    expect($mail->subject)->toContain('Notify Account')
        ->and($mail->subject)->toContain('Notify Co');

    $html = $mail->render();
    expect($html)->toContain('Notify Account')
        ->and($html)->toContain('Notify Co');
});

test('payout notification resolves account from the local payout row', function () {
    $company = makePayoutCompany('Fallback Co');
    $account = makePayoutAccount($company, ['display_name' => 'Fallback Account']);
    StripePayout::create(['stripe_payout_id' => 'po_fb', 'stripe_account_id' => $account->id, 'amount' => 1000, 'currency' => 'aud']);

    $user = User::factory()->create();
    $mail = (new StripePayoutSuccessNotification(
        payoutId: 'po_fb', amount: 1000, currency: 'aud',
    ))->toMail($user);

    expect($mail->subject)->toContain('Fallback Account')
        ->and($mail->subject)->toContain('Fallback Co');

    $legacyMail = (new StripePayoutSuccessNotification(
        payoutId: 'po_missing', amount: 1000, currency: 'aud',
    ))->toMail($user);

    expect($legacyMail->subject)->toContain('Legacy pool');
});

test('failed dd admin notification names account and company', function () {
    $company = makePayoutCompany('DD Co');
    $account = makePayoutAccount($company, ['display_name' => 'DD Account']);

    $owner = User::factory()->create();
    $connection = XeroConnection::create([
        'user_id' => $owner->id, 'access_token' => 'x', 'refresh_token' => 'y',
        'token_expires_at' => now()->addHour(),
    ]);
    $tenant = XeroTenant::create([
        'xero_connection_id' => $connection->id, 'tenant_id' => 't1', 'tenant_name' => 'T1',
    ]);
    $client = Client::create(['company_name' => 'DD Client', 'company_id' => $company->id]);
    $invoice = XeroInvoice::create([
        'xero_tenant_id' => $tenant->id, 'xero_invoice_id' => (string) Str::uuid(),
        'type' => 'ACCREC', 'status' => 'AUTHORISED', 'client_id' => $client->id,
    ]);
    $dd = DirectDebitPayment::create([
        'xero_invoice_id' => $invoice->id, 'xero_tenant_id' => $tenant->id,
        'xero_invoice_xero_id' => $invoice->xero_invoice_id, 'client_id' => $client->id,
        'company_id' => $company->id, 'stripe_account_id' => $account->id,
        'amount' => 100, 'status' => 'failed', 'failure_reason' => 'No account',
        'initiated_at' => now(),
    ]);

    $admin = User::factory()->create();
    $mail = (new DirectDebitFailedAdminNotification($dd))->toMail($admin);

    expect($mail->subject)->toContain('DD Account')
        ->and($mail->subject)->toContain('DD Client');
});

test('seeder backfills payouts and balance transactions onto the legacy account', function () {
    config(['services.stripe.secret' => 'sk_legacy', 'services.stripe.key' => 'pk_legacy', 'services.stripe.webhook_secret' => 'whsec_legacy']);
    makePayoutCompany();

    StripePayout::create(['stripe_payout_id' => 'po_pre', 'amount' => 100, 'currency' => 'aud']);
    StripeBalanceTransaction::create(['stripe_balance_transaction_id' => 'txn_pre', 'amount' => 100, 'fee' => 0, 'net' => 100]);

    $this->seed(StripeAccountSeeder::class);

    $legacy = StripeAccount::where('is_legacy', true)->firstOrFail();

    expect(StripePayout::where('stripe_payout_id', 'po_pre')->firstOrFail()->stripe_account_id)->toBe($legacy->id)
        ->and(StripeBalanceTransaction::where('stripe_balance_transaction_id', 'txn_pre')->firstOrFail()->stripe_account_id)->toBe($legacy->id);
});
