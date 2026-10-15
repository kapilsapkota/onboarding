<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\StripeAccount;
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

if (! function_exists('stripeCustomersAdmin')) {
    function stripeCustomersAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'view-direct-debit', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'stripe-customers-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['view-direct-debit'])->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

if (! function_exists('stripeCustomersFixtures')) {
    /**
     * @return array{account: StripeAccount, customer: StripeCustomer}
     */
    function stripeCustomersFixtures(): array
    {
        $company = Company::create([
            'name' => 'Customers Co',
            'slug' => 'customers-co-'.Str::random(5),
        ]);

        $account = StripeAccount::create([
            'company_id' => $company->id,
            'display_name' => 'Customers Main',
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_test123',
            'status' => 'active',
        ]);

        $customer = StripeCustomer::create([
            'stripe_customer_id' => 'cus_mgmt_'.Str::random(6),
            'stripe_account_id' => $account->id,
            'name' => 'Managed Person',
            'email' => 'managed.person@example.com',
            'stripe_data' => [],
            'last_synced_at' => now(),
        ]);

        StripePaymentMethod::create([
            'stripe_customer_id' => $customer->id,
            'stripe_account_id' => $account->id,
            'stripe_payment_method_id' => 'pm_mgmt_'.Str::random(6),
            'type' => 'au_becs_debit',
            'last4' => '1234',
            'status' => 'active',
            'is_default' => true,
            'stripe_data' => [],
        ]);

        Client::create([
            'company_name' => 'Managed Co',
            'billing_email' => 'managed.person@example.com',
            'company_id' => $company->id,
            'stripe_customer_id' => $customer->stripe_customer_id,
        ]);

        return compact('account', 'customer');
    }
}

test('guests cannot view stripe customers', function () {
    $this->get(route('admin.stripe.customers.index'))->assertRedirect('/login');
});

test('users without permission cannot view stripe customers', function () {
    ['customer' => $customer] = stripeCustomersFixtures();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.stripe.customers.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.stripe.customers.show', $customer))
        ->assertForbidden();
});

test('stripe customers index lists customers with account and link context', function () {
    ['customer' => $customer] = stripeCustomersFixtures();
    $admin = stripeCustomersAdmin();

    $this->actingAs($admin)
        ->get(route('admin.stripe.customers.index'))
        ->assertOk()
        ->assertSee('Managed Person', false)
        ->assertSee($customer->stripe_customer_id, false)
        ->assertSee('Customers Main', false)
        ->assertSee('Customers Co', false)
        ->assertSee('Managed Co', false)
        ->assertSee(route('admin.stripe.customers.show', $customer), false);
});

test('stripe customers index searches by name email and stripe id', function () {
    stripeCustomersFixtures();
    $admin = stripeCustomersAdmin();

    $this->actingAs($admin)
        ->get(route('admin.stripe.customers.index', ['search' => 'managed.person']))
        ->assertOk()
        ->assertSee('Managed Person', false);

    $this->actingAs($admin)
        ->get(route('admin.stripe.customers.index', ['search' => 'nobody-matches-this']))
        ->assertOk()
        ->assertDontSee('Managed Person', false);
});

test('stripe customers index filters by account', function () {
    ['account' => $account] = stripeCustomersFixtures();
    $admin = stripeCustomersAdmin();

    $this->actingAs($admin)
        ->get(route('admin.stripe.customers.index', ['stripe_account' => $account->id]))
        ->assertOk()
        ->assertSee('Managed Person', false);

    $this->actingAs($admin)
        ->get(route('admin.stripe.customers.index', ['stripe_account' => 'unassigned']))
        ->assertOk()
        ->assertDontSee('Managed Person', false);
});

test('stripe customer show page displays methods and charges context', function () {
    ['customer' => $customer] = stripeCustomersFixtures();
    $admin = stripeCustomersAdmin();

    $this->actingAs($admin)
        ->get(route('admin.stripe.customers.show', $customer))
        ->assertOk()
        ->assertSee('Managed Person', false)
        ->assertSee('BECS ••••1234', false)
        ->assertSee('Managed Co', false);
});

test('stripe customers can be synced from stripe', function () {
    $service = Mockery::mock();
    $service->shouldReceive('all')->andReturn((object) ['data' => [], 'has_more' => false]);

    $client = Mockery::mock(StripeClient::class);
    $client->shouldReceive('getService')->with('customers')->andReturn($service);

    $resolver = Mockery::mock(StripeAccountResolver::class)->makePartial();
    $resolver->shouldReceive('clientFor')->andReturn($client);
    app()->instance(StripeAccountResolver::class, $resolver);

    ['account' => $account] = stripeCustomersFixtures();
    $admin = stripeCustomersAdmin();

    $this->actingAs($admin)
        ->post(route('admin.stripe.customers.sync'), ['stripe_account_id' => $account->id])
        ->assertRedirect()
        ->assertSessionHas('success');
});
