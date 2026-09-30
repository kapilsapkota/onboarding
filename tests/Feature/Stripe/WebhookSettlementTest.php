<?php

use App\Http\Controllers\StripeWebhookController;
use App\Jobs\HandleFailedDirectDebitPayment;
use App\Jobs\WriteXeroPayment;
use App\Models\Client;
use App\Models\Company;
use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeBalanceTransaction;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Services\StripeAccountResolver;
use App\Services\StripeBecsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

afterEach(function () {
    StubWebhookController::$stub = null;
});

/**
 * Proves webhooks actually settle: signed event in, correct local state out.
 * Stripe reads are stubbed (no network); signature verification is real.
 */
class StubBecsService extends StripeBecsService
{
    public function __construct(?StripeAccount $account = null) {}

    public function getBalanceTransaction(string $paymentIntentId): array
    {
        return [
            'gross' => 100.0, 'fee' => 1.75, 'net' => 98.25,
            'currency' => 'AUD', 'stripe_bt_id' => 'txn_test123',
        ];
    }
}

function bindFakeBecs(): StubBecsService
{
    $fake = new StubBecsService;
    app()->instance(StripeBecsService::class, $fake);

    return $fake;
}

/**
 * Test-only controller that serves the stub through the protected seam,
 * including the per-account path (which production builds with `new`).
 */
class StubWebhookController extends StripeWebhookController
{
    public static ?StripeBecsService $stub = null;

    protected function becs(): StripeBecsService
    {
        return static::$stub ?? parent::becs();
    }
}

function makeSettlementChain(?Company $company = null): array
{
    $owner = User::factory()->create();
    $connection = XeroConnection::create([
        'user_id' => $owner->id, 'access_token' => 'x', 'refresh_token' => 'y',
        'token_expires_at' => now()->addHour(),
    ]);
    $tenant = XeroTenant::create([
        'xero_connection_id' => $connection->id, 'tenant_id' => 't1', 'tenant_name' => 'T1',
    ]);
    $client = Client::create(['company_name' => 'Settle Co', 'company_id' => $company?->id]);
    $invoice = XeroInvoice::create([
        'xero_tenant_id' => $tenant->id, 'xero_invoice_id' => (string) Illuminate\Support\Str::uuid(),
        'type' => 'ACCREC', 'status' => 'AUTHORISED', 'client_id' => $client->id,
    ]);

    return compact('owner', 'connection', 'tenant', 'client', 'invoice');
}

function makeSettlementDd(array $chain, array $overrides = []): DirectDebitPayment
{
    return DirectDebitPayment::create(array_merge([
        'xero_invoice_id' => $chain['invoice']->id,
        'xero_tenant_id' => $chain['tenant']->id,
        'xero_invoice_xero_id' => $chain['invoice']->xero_invoice_id,
        'xero_invoice_number' => 'INV-1',
        'client_id' => $chain['client']->id,
        'amount' => 100, 'status' => 'processing', 'initiated_at' => now(),
    ], $overrides));
}

function signedSettlementPost(string $uri, array $event, string $secret)
{
    $payload = json_encode($event);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return test()->call('POST', $uri, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
    ], $payload);
}

function intentEvent(string $id, string $status, array $extra = []): array
{
    return [
        'id' => 'evt_test', 'object' => 'event', 'type' => 'payment_intent.succeeded',
        'data' => ['object' => array_merge([
            'id' => $id, 'object' => 'payment_intent', 'status' => $status,
        ], $extra)],
    ];
}

test('succeeded intent settles dd payment, invoice and queues xero writeback once', function () {
    bindFakeBecs();
    Queue::fake();
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $chain = makeSettlementChain();
    $dd = makeSettlementDd($chain, ['gateway_payment_id' => 'pi_settle1']);

    $uri = '/webhooks/stripe';
    signedSettlementPost($uri, intentEvent('pi_settle1', 'succeeded', [
        'metadata' => ['dd_payment_id' => (string) $dd->id],
    ]), 'whsec_legacy')->assertOk();

    expect($dd->refresh()->status)->toBe('settled')
        ->and($dd->refresh()->stripe_fee)->toBe('1.75')
        ->and($dd->refresh()->stripe_balance_transaction_id)->toBe('txn_test123')
        ->and($chain['invoice']->refresh()->payment_status)->toBe('settled');

    Queue::assertPushed(WriteXeroPayment::class, 1);

    // Duplicate delivery is idempotent: still settled, still one job.
    signedSettlementPost($uri, intentEvent('pi_settle1', 'succeeded', [
        'metadata' => ['dd_payment_id' => (string) $dd->id],
    ]), 'whsec_legacy')->assertOk();

    Queue::assertPushed(WriteXeroPayment::class, 1);
});

test('succeeded intent resolves dd by gateway id fallback', function () {
    bindFakeBecs();
    Queue::fake();
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $chain = makeSettlementChain();
    $dd = makeSettlementDd($chain, ['gateway_payment_id' => 'pi_gateway1']);

    signedSettlementPost('/webhooks/stripe', intentEvent('pi_gateway1', 'succeeded'), 'whsec_legacy')->assertOk();

    expect($dd->refresh()->status)->toBe('settled');
    Queue::assertPushed(WriteXeroPayment::class, 1);
});

test('failed intent fails dd payment and invoice and dispatches handler', function () {
    Queue::fake();
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $chain = makeSettlementChain();
    $dd = makeSettlementDd($chain, ['gateway_payment_id' => 'pi_fail1']);

    $event = intentEvent('pi_fail1', 'failed', [
        'last_payment_error' => ['message' => 'No account', 'code' => 'payment_failed'],
    ]);
    $event['type'] = 'payment_intent.payment_failed';

    signedSettlementPost('/webhooks/stripe', $event, 'whsec_legacy')->assertOk();

    expect($dd->refresh()->status)->toBe('failed')
        ->and($dd->refresh()->failure_code)->toBe('payment_failed')
        ->and($chain['invoice']->refresh()->payment_status)->toBe('failed');

    Queue::assertPushed(HandleFailedDirectDebitPayment::class, 1);
});

test('batch item lifecycle settles and normalizes canceled spelling', function () {
    Queue::fake();
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $creator = User::factory()->create();
    $batch = StripeChargeBatch::create(['reference' => 'B-SET', 'status' => 'processing']);
    $batch->update(['created_by' => $creator->id]);
    $customer = StripeCustomer::create(['stripe_customer_id' => 'cus_set', 'stripe_data' => []]);
    $method = StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id, 'stripe_data' => [],
        'stripe_payment_method_id' => 'pm_set_1', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);
    $item = StripeChargeBatchItem::create([
        'batch_id' => $batch->id, 'stripe_customer_id' => $customer->id,
        'stripe_payment_method_id' => $method->id, 'amount' => 1000,
        'status' => 'processing', 'stripe_payment_intent_id' => 'pi_batch1',
    ]);

    $processing = intentEvent('pi_batch1', 'processing');
    $processing['type'] = 'payment_intent.processing';
    signedSettlementPost('/webhooks/stripe', $processing, 'whsec_legacy')->assertOk();
    expect($item->refresh()->status)->toBe('processing');

    signedSettlementPost('/webhooks/stripe', intentEvent('pi_batch1', 'succeeded'), 'whsec_legacy')->assertOk();
    expect($item->refresh()->status)->toBe('succeeded')
        ->and($batch->refresh()->status)->toBe('completed');

    $item2 = StripeChargeBatchItem::create([
        'batch_id' => $batch->id, 'stripe_customer_id' => $customer->id,
        'stripe_payment_method_id' => $method->id, 'amount' => 500,
        'status' => 'processing', 'stripe_payment_intent_id' => 'pi_batch2',
    ]);
    $canceled = intentEvent('pi_batch2', 'canceled');
    $canceled['type'] = 'payment_intent.canceled';
    signedSettlementPost('/webhooks/stripe', $canceled, 'whsec_legacy')->assertOk();
    expect($item2->refresh()->status)->toBe('cancelled');
});

test('per-account endpoint only settles its own records', function () {
    StubWebhookController::$stub = bindFakeBecs();
    app()->instance(
        StripeWebhookController::class,
        new StubWebhookController(app(StripeBecsService::class), app(StripeAccountResolver::class))
    );
    Queue::fake();

    $companyA = Company::create(['name' => 'WA', 'slug' => 'wa-'.Str::random(4)]);
    $companyB = Company::create(['name' => 'WB', 'slug' => 'wb-'.Str::random(4)]);
    $accountA = StripeAccount::create([
        'company_id' => $companyA->id, 'display_name' => 'A', 'status' => 'active', 'webhook_secret' => 'whsec_aaa',
    ]);
    $accountB = StripeAccount::create([
        'company_id' => $companyB->id, 'display_name' => 'B', 'status' => 'active', 'webhook_secret' => 'whsec_bbb',
    ]);

    $chain = makeSettlementChain($companyB);
    $dd = makeSettlementDd($chain, ['gateway_payment_id' => 'pi_acct', 'stripe_account_id' => $accountB->id]);

    // Event for B's payment on A's endpoint: untouched.
    signedSettlementPost('/webhooks/stripe/'.$accountA->id, intentEvent('pi_acct', 'succeeded'), 'whsec_aaa')->assertOk();
    expect($dd->refresh()->status)->toBe('processing');

    // Same event on B's endpoint: settled.
    signedSettlementPost('/webhooks/stripe/'.$accountB->id, intentEvent('pi_acct', 'succeeded'), 'whsec_bbb')->assertOk();
    expect($dd->refresh()->status)->toBe('settled');
    Queue::assertPushed(WriteXeroPayment::class, 1);
});

test('unresolvable balance transaction degrades gracefully', function () {
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $event = [
        'id' => 'evt_test', 'object' => 'event', 'type' => 'charge.succeeded',
        'data' => ['object' => ['id' => 'ch_bogus', 'balance_transaction' => 'txn_bogus']],
    ];

    // Real sync service, bogus ids: must still answer 200, never 500.
    signedSettlementPost('/webhooks/stripe', $event, 'whsec_legacy')->assertOk();

    expect(StripeBalanceTransaction::where('stripe_balance_transaction_id', 'txn_bogus')->exists())->toBeFalse();
});
