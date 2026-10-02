<?php

use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use App\Services\StripePayoutSyncService;

test('reconcile by payment intent degrades gracefully for unknown ids', function () {
    $result = app(StripePayoutSyncService::class)->reconcilePaymentIntent('pi_bogus_does_not_exist');

    expect($result)->toBeNull();
});

test('reconcile-items command succeeds when nothing is pending', function () {
    $this->artisan('stripe:reconcile-items --days=7')
        ->assertSuccessful();
});

test('sync-payouts command rejects unknown account', function () {
    $this->artisan('stripe:sync-payouts --account=999999')
        ->assertFailed();
});

test('sync-becs-customers command rejects unknown account', function () {
    $this->artisan('stripe:sync-becs-customers --account=999999')
        ->assertFailed();
});

test('stripe commands expose account option', function () {
    $this->artisan('stripe:sync-payouts --help')->assertSuccessful();
    $this->artisan('stripe:sync-becs-customers --help')->assertSuccessful();
    $this->artisan('stripe:reconcile-items --help')->assertSuccessful();
});

test('payment intent webhook still settles when bt persist fails', function () {
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $creator = User::factory()->create();
    $batch = StripeChargeBatch::create(['reference' => 'B-RECON', 'status' => 'processing']);
    $batch->update(['created_by' => $creator->id]);
    $customer = StripeCustomer::create(['stripe_customer_id' => 'cus_recon', 'stripe_data' => []]);
    $method = StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id, 'stripe_data' => [],
        'stripe_payment_method_id' => 'pm_recon_1', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);
    $item = StripeChargeBatchItem::create([
        'batch_id' => $batch->id, 'stripe_customer_id' => $customer->id,
        'stripe_payment_method_id' => $method->id, 'amount' => 1000,
        'status' => 'processing', 'stripe_payment_intent_id' => 'pi_recon_bogus',
    ]);

    $event = [
        'id' => 'evt_test', 'object' => 'event', 'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_recon_bogus', 'object' => 'payment_intent', 'status' => 'succeeded']],
    ];
    $payload = json_encode($event);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_legacy');

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
    ], $payload)->assertOk();

    // Status still flips even though the BT has no Stripe record (reconcile returns null).
    expect($item->refresh()->status)->toBe('succeeded');
});
