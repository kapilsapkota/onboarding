<?php

use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use App\Services\StripeAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

if (! function_exists('bulkCancelCompany')) {
    function bulkCancelCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
        ]);
    }
}

if (! function_exists('bulkCancelAccount')) {
    function bulkCancelAccount(?Company $company = null): StripeAccount
    {
        return StripeAccount::create([
            'company_id' => $company?->id,
            'display_name' => 'Cancel account',
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_test123',
            'status' => 'active',
        ]);
    }
}

if (! function_exists('bulkCancelAdmin')) {
    function bulkCancelAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'view-stripe-account', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['view-stripe-account'])->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

if (! function_exists('bulkCancelSetup')) {
    /**
     * @return array{account: StripeAccount, batch: StripeChargeBatch, item: StripeChargeBatchItem}
     */
    function bulkCancelSetup(string $status = 'pending', ?string $intentId = null): array
    {
        $account = bulkCancelAccount(bulkCancelCompany('Cancel Co '.Str::random(4)));

        $customer = StripeCustomer::create([
            'stripe_customer_id' => 'cus_cancel_'.Str::random(6),
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'name' => 'Cancel Person',
            'email' => 'cancel.person@example.com',
        ]);
        $pm = StripePaymentMethod::create([
            'stripe_customer_id' => $customer->id,
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'stripe_payment_method_id' => 'pm_cancel_'.Str::random(6),
            'type' => 'au_becs_debit',
            'status' => 'active',
        ]);

        $batch = StripeChargeBatch::create([
            'reference' => 'BATCH-'.strtoupper(Str::random(8)),
            'stripe_account_id' => $account->id,
            'customer_count' => 1,
            'total_amount' => 5000,
            'currency' => 'aud',
            'status' => 'processing',
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
        ]);

        return compact('account', 'batch', 'item');
    }
}

if (! function_exists('bulkCancelFakeStripe')) {
    /**
     * Binds a fake StripeAccountResolver whose client returns the given
     * PaymentIntent payloads for retrieve/cancel.
     */
    function bulkCancelFakeStripe(string $retrieveStatus = 'requires_payment_method', string $cancelStatus = 'canceled'): void
    {
        $retrieveIntent = new class($retrieveStatus)
        {
            public function __construct(public string $status) {}

            public function toArray(): array
            {
                return ['id' => 'pi_test123', 'status' => $this->status, 'object' => 'payment_intent'];
            }
        };

        $cancelIntent = new class($cancelStatus)
        {
            public function __construct(public string $status) {}

            public function toArray(): array
            {
                return ['id' => 'pi_test123', 'status' => $this->status, 'object' => 'payment_intent'];
            }
        };

        $paymentIntents = Mockery::mock();
        $paymentIntents->shouldReceive('retrieve')->andReturn($retrieveIntent);
        $paymentIntents->shouldReceive('cancel')->andReturn($cancelIntent);

        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($paymentIntents);

        $resolver = Mockery::mock(StripeAccountResolver::class);
        $resolver->shouldReceive('clientFor')->andReturn($client);
        app()->instance(StripeAccountResolver::class, $resolver);
    }
}

test('pending bulk charge items without a payment intent can be cancelled locally', function () {
    ['item' => $item] = bulkCancelSetup('pending');
    $admin = bulkCancelAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.cancel', $item))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($item->refresh()->status)->toBe('cancelled');
});

test('processing bulk charge items cancel the stripe payment intent', function () {
    bulkCancelFakeStripe('requires_payment_method', 'canceled');

    ['item' => $item] = bulkCancelSetup('processing', 'pi_test123');
    $admin = bulkCancelAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.cancel', $item))
        ->assertRedirect()
        ->assertSessionHas('success');

    $item->refresh();
    expect($item->status)->toBe('cancelled')
        ->and($item->stripe_data['status'] ?? null)->toBe('canceled');
});

test('succeeded bulk charge items cannot be cancelled', function () {
    ['item' => $item] = bulkCancelSetup('succeeded', 'pi_test456');
    $admin = bulkCancelAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.cancel', $item))
        ->assertRedirect()
        ->assertSessionHasErrors('cancel');

    expect($item->refresh()->status)->toBe('succeeded');
});

test('failed bulk charge items cannot be cancelled', function () {
    ['item' => $item] = bulkCancelSetup('failed');
    $admin = bulkCancelAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.cancel', $item))
        ->assertRedirect()
        ->assertSessionHasErrors('cancel');

    expect($item->refresh()->status)->toBe('failed');
});

test('already cancelled items cannot be cancelled again', function () {
    ['item' => $item] = bulkCancelSetup('cancelled');
    $admin = bulkCancelAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.cancel', $item))
        ->assertRedirect()
        ->assertSessionHasErrors('cancel');
});

test('stripe-succeeded intents sync locally instead of cancelling', function () {
    bulkCancelFakeStripe('succeeded', 'succeeded');

    ['item' => $item] = bulkCancelSetup('processing', 'pi_test789');
    $admin = bulkCancelAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.items.cancel', $item))
        ->assertRedirect()
        ->assertSessionHasErrors('cancel');

    expect($item->refresh()->status)->toBe('succeeded');
});

test('batch page offers cancel only for cancellable items', function () {
    $account = bulkCancelAccount(bulkCancelCompany('Cancel UI Co'));

    $makeItem = function (string $status, ?string $intentId) use ($account) {
        $customer = StripeCustomer::create([
            'stripe_customer_id' => 'cus_cui_'.Str::random(6),
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'name' => 'Cancel UI Person',
            'email' => 'cancel.ui@example.com',
        ]);
        $pm = StripePaymentMethod::create([
            'stripe_customer_id' => $customer->id,
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'stripe_payment_method_id' => 'pm_cui_'.Str::random(6),
            'type' => 'au_becs_debit',
            'status' => 'active',
        ]);
        $batch = StripeChargeBatch::create([
            'reference' => 'BATCH-'.strtoupper(Str::random(8)),
            'stripe_account_id' => $account->id,
            'customer_count' => 1,
            'total_amount' => 1000,
            'currency' => 'aud',
            'status' => 'processing',
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
        ]);
    };

    $pending = $makeItem('pending', null);
    $processing = $makeItem('processing', 'pi_cui123');
    $succeeded = $makeItem('succeeded', 'pi_cui456');

    $admin = bulkCancelAdmin();

    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $pending->batch))
        ->assertOk()
        ->assertSee(route('admin.stripe.items.cancel', $pending), false);

    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $processing->batch))
        ->assertOk()
        ->assertSee(route('admin.stripe.items.cancel', $processing), false);

    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $succeeded->batch))
        ->assertOk()
        ->assertDontSee(route('admin.stripe.items.cancel', $succeeded), false);
});
