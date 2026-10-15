<?php

use App\Jobs\SyncStripeBecsCustomersJob;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\User;
use App\Services\StripeAccountResolver;
use App\Services\StripeBecsCustomerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

function becsQueueAdmin(): User
{
    Permission::firstOrCreate(['name' => 'view-direct-debit', 'guard_name' => 'web']);

    $role = Role::firstOrCreate(['name' => 'becs-queue-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', ['view-direct-debit'])->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function becsQueueAccount(string $name = 'Queue Main'): StripeAccount
{
    return StripeAccount::create([
        'display_name' => $name,
        'publishable_key' => 'pk_test_queue',
        'secret_key' => 'sk_test_queue',
        'webhook_secret' => 'whsec_queue',
        'status' => 'active',
    ]);
}

test('stripe customer sync endpoint queues one job for the selected account', function () {
    Queue::fake();

    $account = becsQueueAccount();
    $admin = becsQueueAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.customers.sync'), ['stripe_account_id' => $account->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(SyncStripeBecsCustomersJob::class, 1);
    Queue::assertPushed(SyncStripeBecsCustomersJob::class, function ($job) use ($account): bool {
        return $job->stripeAccountId === $account->id;
    });
});

test('stripe customer sync endpoint queues one job per account plus legacy', function () {
    Queue::fake();

    becsQueueAccount('Queue One');
    becsQueueAccount('Queue Two');
    $admin = becsQueueAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.customers.sync'))
        ->assertRedirect()
        ->assertSessionHas('success');

    // One job per account plus one for the legacy pool.
    Queue::assertPushed(SyncStripeBecsCustomersJob::class, 3);
});

test('sync command with queue flag dispatches one job for the account', function () {
    Queue::fake();

    $account = becsQueueAccount();

    $this->artisan('stripe:sync-becs-customers', ['--account' => $account->id, '--queue' => true])
        ->assertSuccessful();

    Queue::assertPushed(SyncStripeBecsCustomersJob::class, 1);
    Queue::assertPushed(SyncStripeBecsCustomersJob::class, function ($job) use ($account): bool {
        return $job->stripeAccountId === $account->id;
    });
    expect(StripeCustomer::count())->toBe(0);
});

test('sync command with queue flag fans out one job per account plus legacy', function () {
    Queue::fake();

    becsQueueAccount('Queue One');
    becsQueueAccount('Queue Two');

    $this->artisan('stripe:sync-becs-customers', ['--queue' => true])
        ->assertSuccessful();

    Queue::assertPushed(SyncStripeBecsCustomersJob::class, 3);
    expect(StripeCustomer::count())->toBe(0);
});

test('account job syncs that account inline through the service', function () {
    $account = becsQueueAccount();

    $client = Mockery::mock(StripeClient::class);

    $resolver = Mockery::mock(StripeAccountResolver::class)->makePartial();
    $resolver->shouldReceive('clientFor')->once()->with(Mockery::on(fn ($a): bool => $a?->id === $account->id))->andReturn($client);
    app()->instance(StripeAccountResolver::class, $resolver);

    $sync = Mockery::mock(StripeBecsCustomerSyncService::class);
    $sync->shouldReceive('syncThrough')->once()->with($client, Mockery::on(fn ($a): bool => $a->id === $account->id))->andReturn(7);
    app()->instance(StripeBecsCustomerSyncService::class, $sync);

    (new SyncStripeBecsCustomersJob($account->id))->handle(app(StripeAccountResolver::class), app(StripeBecsCustomerSyncService::class));
});

test('legacy job syncs the legacy pool with a null account', function () {
    $client = Mockery::mock(StripeClient::class);

    $resolver = Mockery::mock(StripeAccountResolver::class)->makePartial();
    $resolver->shouldReceive('clientFor')->once()->with(null)->andReturn($client);
    app()->instance(StripeAccountResolver::class, $resolver);

    $sync = Mockery::mock(StripeBecsCustomerSyncService::class);
    $sync->shouldReceive('syncThrough')->once()->with($client, null)->andReturn(3);
    app()->instance(StripeBecsCustomerSyncService::class, $sync);

    (new SyncStripeBecsCustomersJob(null))->handle(app(StripeAccountResolver::class), app(StripeBecsCustomerSyncService::class));
});
