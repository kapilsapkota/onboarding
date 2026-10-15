<?php

use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

if (! function_exists('bulkSearchCompany')) {
    function bulkSearchCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
        ]);
    }
}

if (! function_exists('bulkSearchAccount')) {
    function bulkSearchAccount(?Company $company = null, array $overrides = []): StripeAccount
    {
        return StripeAccount::create(array_merge([
            'company_id' => $company?->id,
            'display_name' => 'Search account',
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_test123',
            'status' => 'active',
        ], $overrides));
    }
}

if (! function_exists('bulkSearchAdmin')) {
    function bulkSearchAdmin(): User
    {
        foreach (['view-stripe-account'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['view-stripe-account'])->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

if (! function_exists('bulkSearchBecsCustomer')) {
    function bulkSearchBecsCustomer(StripeAccount $account, string $cus, string $pm, string $name, string $email): StripeCustomer
    {
        $customer = StripeCustomer::create([
            'stripe_customer_id' => $cus,
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'name' => $name,
            'email' => $email,
        ]);
        StripePaymentMethod::create([
            'stripe_customer_id' => $customer->id,
            'stripe_data' => [],
            'stripe_account_id' => $account->id,
            'stripe_payment_method_id' => $pm,
            'type' => 'au_becs_debit',
            'status' => 'active',
        ]);

        return $customer;
    }
}

test('bulk charge search finds customers by name', function () {
    $account = bulkSearchAccount(bulkSearchCompany('Search Co'));
    bulkSearchBecsCustomer($account, 'cus_alice', 'pm_alice', 'Alice Wonderland', 'alice@example.com');
    bulkSearchBecsCustomer($account, 'cus_bob', 'pm_bob', 'Bob Builder', 'bob@example.com');

    $admin = bulkSearchAdmin();

    $this->actingAs($admin)->get('/admin/stripe/bulk-charge?search=Alice')
        ->assertOk()
        ->assertSee('Alice Wonderland', false)
        ->assertDontSee('Bob Builder', false);
});

test('bulk charge search finds customers by email', function () {
    $account = bulkSearchAccount(bulkSearchCompany('Email Co'));
    bulkSearchBecsCustomer($account, 'cus_carol', 'pm_carol', 'Carol Singer', 'carol.singer@example.com');
    bulkSearchBecsCustomer($account, 'cus_dave', 'pm_dave', 'Dave Jones', 'dave@example.com');

    $admin = bulkSearchAdmin();

    $this->actingAs($admin)->get('/admin/stripe/bulk-charge?search=carol.singer%40example.com')
        ->assertOk()
        ->assertSee('Carol Singer', false)
        ->assertDontSee('Dave Jones', false);
});

test('bulk charge search excludes customers without an active becs method', function () {
    $account = bulkSearchAccount(bulkSearchCompany('Eligible Co'));

    bulkSearchBecsCustomer($account, 'cus_elig', 'pm_elig', 'Eligible Person', 'shared-term@example.com');

    // Ineligible: same searchable term but no active BECS method.
    StripeCustomer::create([
        'stripe_customer_id' => 'cus_inelig',
        'stripe_data' => [],
        'stripe_account_id' => $account->id,
        'name' => 'Ineligible Person',
        'email' => 'shared-term-other@example.com',
    ]);

    $admin = bulkSearchAdmin();

    $this->actingAs($admin)->get('/admin/stripe/bulk-charge?search=shared-term')
        ->assertOk()
        ->assertSee('Eligible Person', false)
        ->assertDontSee('Ineligible Person', false);
});

test('bulk charge search respects the account filter', function () {
    $accountA = bulkSearchAccount(bulkSearchCompany('Filter A'));
    $accountB = bulkSearchAccount(bulkSearchCompany('Filter B'));
    bulkSearchBecsCustomer($accountA, 'cus_fa', 'pm_fa', 'Filter Person A', 'filter.person@example.com');
    bulkSearchBecsCustomer($accountB, 'cus_fb', 'pm_fb', 'Filter Person B', 'filter.person@example.com');

    $admin = bulkSearchAdmin();

    $this->actingAs($admin)->get('/admin/stripe/bulk-charge?stripe_account='.$accountA->id.'&search=filter.person')
        ->assertOk()
        ->assertSee('Filter Person A', false)
        ->assertDontSee('Filter Person B', false);
});

test('bulk charge ajax search returns rows json', function () {
    $account = bulkSearchAccount(bulkSearchCompany('Ajax Co'));
    bulkSearchBecsCustomer($account, 'cus_ajax', 'pm_ajax', 'Ajax Person', 'ajax.person@example.com');

    $admin = bulkSearchAdmin();

    $response = $this->actingAs($admin)->get('/admin/stripe/bulk-charge?search=ajax.person', [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json',
    ]);

    $response->assertOk()->assertJsonStructure(['rows', 'pagination']);
    expect($response->json('rows'))->toContain('Ajax Person');
});
