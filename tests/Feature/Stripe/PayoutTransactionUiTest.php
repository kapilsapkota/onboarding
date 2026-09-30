<?php

use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripePayout;
use App\Models\User;
use App\Services\StripeAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makePayoutUiCompany(string $name): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(5),
    ]);
}

function makePayoutUiAccount(?Company $company = null, array $overrides = []): StripeAccount
{
    return StripeAccount::create(array_merge([
        'company_id' => $company?->id,
        'display_name' => 'UI Account',
        'publishable_key' => 'pk_test_123',
        'secret_key' => 'sk_test_123',
        'webhook_secret' => 'whsec_test123',
        'status' => 'active',
    ], $overrides));
}

function makePayoutUiUser(?Company $company = null): User
{
    $user = User::factory()->create();

    if ($company) {
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $user->assignRole('manager');
        $user->companies()->attach($company->id);
    }

    return $user;
}

test('payouts index filters by account and shows badges', function () {
    $companyA = makePayoutUiCompany('UI Co A');
    $accountA = makePayoutUiAccount($companyA, ['display_name' => 'UI Account A']);
    $accountB = makePayoutUiAccount(makePayoutUiCompany('UI Co B'), ['display_name' => 'UI Account B']);

    StripePayout::create(['stripe_payout_id' => 'po_uia', 'stripe_account_id' => $accountA->id, 'amount' => 100, 'currency' => 'aud']);
    StripePayout::create(['stripe_payout_id' => 'po_uib', 'stripe_account_id' => $accountB->id, 'amount' => 200, 'currency' => 'aud']);
    StripePayout::create(['stripe_payout_id' => 'po_uinull', 'amount' => 300, 'currency' => 'aud']);

    $user = makePayoutUiUser();

    $this->actingAs($user)->get('/admin/payouts')
        ->assertOk()
        ->assertSee('po_uia', false)
        ->assertSee('po_uib', false)
        ->assertSee('UI Account A', false)
        ->assertSee('Legacy pool', false);

    $this->actingAs($user)->get('/admin/payouts?stripe_account='.$accountA->id)
        ->assertOk()
        ->assertSee('po_uia', false)
        ->assertDontSee('po_uib', false)
        ->assertDontSee('po_uinull', false);

    $this->actingAs($user)->get('/admin/payouts?stripe_account=unassigned')
        ->assertOk()
        ->assertSee('po_uinull', false)
        ->assertDontSee('po_uia', false);

    $this->actingAs($user)->get('/admin/payouts?stripe_account=999999')->assertNotFound();
});

test('payouts index blocks foreign accounts for company members', function () {
    $companyA = makePayoutUiCompany('Mine UI');
    $companyB = makePayoutUiCompany('Theirs UI');
    $accountB = makePayoutUiAccount($companyB);

    $user = makePayoutUiUser($companyA);

    // Own picker still lists everything visible (legacy + own + unscoped).
    $this->actingAs($user)->get('/admin/payouts')->assertOk();

    $this->actingAs($user)->get('/admin/payouts?stripe_account='.$accountB->id)->assertForbidden();
});

test('payout detail names its account', function () {
    $company = makePayoutUiCompany('Show UI Co');
    $account = makePayoutUiAccount($company, ['display_name' => 'Show UI Account']);
    StripePayout::create(['stripe_payout_id' => 'po_show', 'stripe_account_id' => $account->id, 'amount' => 100, 'currency' => 'aud']);
    StripePayout::create(['stripe_payout_id' => 'po_shownull', 'amount' => 100, 'currency' => 'aud']);

    $user = makePayoutUiUser();

    $this->actingAs($user)->get('/admin/payouts/po_show')
        ->assertOk()
        ->assertSee('Show UI Account', false)
        ->assertSee('Show UI Co', false);

    $this->actingAs($user)->get('/admin/payouts/po_shownull')
        ->assertOk()
        ->assertSee('Legacy pool', false);
});

test('transactions reject unknown, foreign and keyless accounts without crashing', function () {
    $user = makePayoutUiUser();

    $this->actingAs($user)->get('/admin/stripe/transactions?stripe_account_id=999999')->assertNotFound();

    $companyA = makePayoutUiCompany('Txn Mine');
    $accountB = makePayoutUiAccount(makePayoutUiCompany('Txn Theirs'));
    $member = makePayoutUiUser($companyA);

    $this->actingAs($member)->get('/admin/stripe/transactions?stripe_account_id='.$accountB->id)->assertForbidden();

    $keyless = makePayoutUiAccount(null, ['secret_key' => null]);
    $this->actingAs($user)->get('/admin/stripe/transactions?stripe_account_id='.$keyless->id)
        ->assertRedirect()
        ->assertSessionHasErrors('stripe_account_id');

    // Dummy secret can never succeed: friendly error banner, never a 500.
    $dummy = makePayoutUiAccount();
    $this->actingAs($user)->get('/admin/stripe/transactions?stripe_account_id='.$dummy->id)
        ->assertRedirect()
        ->assertSessionHasErrors('stripe');

    $this->actingAs($user)->get('/admin/stripe/transactions/txn_bogus?stripe_account_id='.$dummy->id)
        ->assertRedirect()
        ->assertSessionHasErrors('stripe');
});

test('resolver visibleAccounts scopes by membership', function () {
    $companyA = makePayoutUiCompany('Vis A');
    $companyB = makePayoutUiCompany('Vis B');
    $accountA = makePayoutUiAccount($companyA);
    $accountB = makePayoutUiAccount($companyB);
    $loose = makePayoutUiAccount(null);
    $disabled = makePayoutUiAccount($companyA, ['status' => 'disabled']);

    $resolver = new StripeAccountResolver;

    $all = $resolver->visibleAccounts(null)->pluck('id')->all();
    expect($all)->toContain($accountA->id, $accountB->id, $loose->id)
        ->and($all)->not->toContain($disabled->id);

    $member = makePayoutUiUser($companyA);
    $mine = $resolver->visibleAccounts($member)->pluck('id')->all();
    expect($mine)->toContain($accountA->id, $loose->id)
        ->and($mine)->not->toContain($accountB->id, $disabled->id);
});
