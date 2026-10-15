<?php

use App\Jobs\ProcessStripeCharge;
use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

if (! function_exists('bulkRetryCompany')) {
    function bulkRetryCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
        ]);
    }
}

if (! function_exists('bulkRetryAccount')) {
    function bulkRetryAccount(?Company $company = null): StripeAccount
    {
        return StripeAccount::create([
            'company_id' => $company?->id,
            'display_name' => 'Retry account',
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_test123',
            'status' => 'active',
        ]);
    }
}

if (! function_exists('bulkRetryAdmin')) {
    function bulkRetryAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'view-stripe-account', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['view-stripe-account'])->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

if (! function_exists('bulkRetrySetup')) {
    /**
     * @return array{account: StripeAccount, batch: StripeChargeBatch, item: StripeChargeBatchItem}
     */
    function bulkRetrySetup(string $status = 'failed', ?string $intentId = null): array
    {
        $account = bulkRetryAccount(bulkRetryCompany('Retry Co '.Str::random(4)));

        $customer = StripeCustomer::create([
            'stripe_customer_id' => 'cus_retry_'.Str::random(6),
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'name' => 'Retry Person',
            'email' => 'retry.person@example.com',
        ]);
        $pm = StripePaymentMethod::create([
            'stripe_customer_id' => $customer->id,
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'stripe_payment_method_id' => 'pm_retry_'.Str::random(6),
            'type' => 'au_becs_debit',
            'status' => 'active',
        ]);

        $batch = StripeChargeBatch::create([
            'reference' => 'BATCH-'.strtoupper(Str::random(8)),
            'stripe_account_id' => $account->id,
            'customer_count' => 1,
            'total_amount' => 5000,
            'currency' => 'aud',
            'status' => $status === 'failed' ? 'failed' : 'processing',
        ]);

        $item = StripeChargeBatchItem::create([
            'batch_id' => $batch->id,
            'stripe_account_id' => $account->id,
            'stripe_customer_id' => $customer->id,
            'stripe_payment_method_id' => $pm->id,
            'amount' => 5000,
            'currency' => 'aud',
            'status' => $status,
            'stripe_payment_intent_id' => $intentId,
            'error_message' => $status === 'failed' ? 'Could not connect to Stripe (https://api.stripe.com).' : null,
        ]);

        return compact('account', 'batch', 'item');
    }
}

if (! function_exists('bulkRetryJobItemId')) {
    function bulkRetryJobItemId(ProcessStripeCharge $job): ?int
    {
        $property = new ReflectionProperty(ProcessStripeCharge::class, 'item');
        $property->setAccessible(true);

        return $property->getValue($job)?->id;
    }
}

test('failed bulk charge items can be retried', function () {
    Queue::fake();

    ['batch' => $batch, 'item' => $item] = bulkRetrySetup('failed');
    $admin = bulkRetryAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.retry', $item))
        ->assertRedirect()
        ->assertSessionHas('success');

    $item->refresh();
    expect($item->status)->toBe('pending')
        ->and($item->error_message)->toBeNull()
        ->and($item->processed_at)->toBeNull();

    // Batch flips back to processing while the retried item is pending.
    expect($batch->refresh()->status)->toBe('processing');

    Queue::assertPushed(ProcessStripeCharge::class, fn ($job) => bulkRetryJobItemId($job) === $item->id);
});

test('non-failed bulk charge items cannot be retried', function () {
    Queue::fake();

    ['item' => $item] = bulkRetrySetup('succeeded');
    $admin = bulkRetryAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.retry', $item))
        ->assertRedirect()
        ->assertSessionHasErrors('retry');

    expect($item->refresh()->status)->toBe('succeeded');

    Queue::assertNotPushed(ProcessStripeCharge::class);
});

test('failed items with a payment intent are not retried automatically', function () {
    Queue::fake();

    ['item' => $item] = bulkRetrySetup('failed', 'pi_existing123');
    $admin = bulkRetryAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.retry', $item))
        ->assertRedirect()
        ->assertSessionHasErrors('retry');

    expect($item->refresh()->status)->toBe('failed');

    Queue::assertNotPushed(ProcessStripeCharge::class);
});

test('batch page offers retry only for retryable failed items', function () {
    $account = bulkRetryAccount(bulkRetryCompany('Retry UI Co'));

    $makeItem = function (string $status, ?string $intentId) use ($account) {
        $customer = StripeCustomer::create([
            'stripe_customer_id' => 'cus_ui_'.Str::random(6),
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'name' => 'UI Person',
            'email' => 'ui.person@example.com',
        ]);
        $pm = StripePaymentMethod::create([
            'stripe_customer_id' => $customer->id,
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'stripe_payment_method_id' => 'pm_ui_'.Str::random(6),
            'type' => 'au_becs_debit',
            'status' => 'active',
        ]);
        $batch = StripeChargeBatch::create([
            'reference' => 'BATCH-'.strtoupper(Str::random(8)),
            'stripe_account_id' => $account->id,
            'customer_count' => 1,
            'total_amount' => 1000,
            'currency' => 'aud',
            'status' => 'failed',
        ]);

        return StripeChargeBatchItem::create([
            'batch_id' => $batch->id,
            'stripe_account_id' => $account->id,
            'stripe_customer_id' => $customer->id,
            'stripe_payment_method_id' => $pm->id,
            'amount' => 1000,
            'currency' => 'aud',
            'status' => $status,
            'stripe_payment_intent_id' => $intentId,
            'error_message' => 'Could not connect to Stripe.',
        ]);
    };

    $retryable = $makeItem('failed', null);
    $withIntent = $makeItem('failed', 'pi_ui123');
    $succeeded = $makeItem('succeeded', 'pi_ui456');

    $admin = bulkRetryAdmin();

    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $retryable->batch))
        ->assertOk()
        ->assertSee(route('admin.stripe.items.retry', $retryable), false);

    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $withIntent->batch))
        ->assertOk()
        ->assertDontSee(route('admin.stripe.items.retry', $withIntent), false);

    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $succeeded->batch))
        ->assertOk()
        ->assertDontSee(route('admin.stripe.items.retry', $succeeded), false);
});
