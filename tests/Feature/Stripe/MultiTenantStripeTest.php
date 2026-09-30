<?php

use App\Http\Controllers\DdrController;
use App\Models\Client;
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

uses(RefreshDatabase::class);

function makeTenantCompany(string $name = 'Tenant Co'): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(5),
    ]);
}

function makeTenantAccount(?Company $company = null, array $overrides = []): StripeAccount
{
    return StripeAccount::create(array_merge([
        'company_id' => $company?->id,
        'display_name' => 'Tenant account',
        'publishable_key' => 'pk_test_123',
        'secret_key' => 'sk_test_123',
        'webhook_secret' => 'whsec_test123',
        'status' => 'active',
    ], $overrides));
}

function makeTenantSuperAdmin(array $extra = []): User
{
    $names = array_unique(array_merge([
        'view-stripe-account', 'create-stripe-account', 'edit-stripe-account', 'delete-stripe-account',
        'view-user',
    ], $extra));

    foreach ($names as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $names)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeBecsCustomer(StripeAccount $account, string $cus = 'cus_t1', string $pm = 'pm_t1'): StripeCustomer
{
    $customer = StripeCustomer::create([
        'stripe_customer_id' => $cus, 'stripe_data' => [], 'stripe_account_id' => $account->id,
        'name' => 'Tenant Customer', 'email' => 'tenant@example.com',
    ]);
    StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id, 'stripe_data' => [],
        'stripe_account_id' => $account->id, 'stripe_payment_method_id' => $pm,
        'type' => 'au_becs_debit', 'status' => 'active',
    ]);

    return $customer;
}

function stripeWebhookHeaders(string $payload, string $secret): array
{
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return ['HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}"];
}

function stripeWebhookPost(string $uri, string $payload, string $secret)
{
    $server = stripeWebhookHeaders($payload, $secret);
    $server['CONTENT_TYPE'] = 'application/json';

    return test()->call('POST', $uri, [], [], [], $server, $payload);
}

// ── Phase 2: Stripe Account CRUD ──────────────────────────────────────────

test('stripe accounts index renders and crud works end to end', function () {
    $admin = makeTenantSuperAdmin();
    $company = makeTenantCompany();

    $this->actingAs($admin)->get('/admin/stripe-accounts')
        ->assertOk()
        ->assertSee('Stripe Accounts', false);

    $this->actingAs($admin)->post('/admin/stripe-accounts', [
        'company_id' => $company->id,
        'display_name' => 'Primary AUD',
        'publishable_key' => 'pk_live_abc',
        'secret_key' => 'sk_live_abc',
        'webhook_secret' => 'whsec_abc',
        'is_default' => '1',
        'status' => 'active',
    ])->assertRedirect();

    $account = StripeAccount::where('display_name', 'Primary AUD')->firstOrFail();
    expect($account->company_id)->toBe($company->id)
        ->and($account->is_default)->toBeTrue()
        ->and($account->secret_key)->toBe('sk_live_abc');

    // Blank secrets keep stored values; default can move.
    $second = makeTenantAccount($company);
    $this->actingAs($admin)->put("/admin/stripe-accounts/{$second->id}", [
        'company_id' => $company->id,
        'display_name' => 'Secondary',
        'publishable_key' => 'pk_live_xyz',
        'secret_key' => '',
        'webhook_secret' => '',
        'is_default' => '1',
        'status' => 'active',
    ])->assertRedirect();

    expect($second->refresh()->secret_key)->toBe('sk_test_123')
        ->and($second->refresh()->is_default)->toBeTrue()
        ->and($account->refresh()->is_default)->toBeFalse();

    // Delete blocked with customers; allowed when empty.
    $this->actingAs($admin)->delete("/admin/stripe-accounts/{$second->id}")->assertRedirect();
    expect(StripeAccount::where('id', $second->id)->exists())->toBeFalse();
});

test('stripe account validation rejects bad key formats', function () {
    $admin = makeTenantSuperAdmin();

    $this->actingAs($admin)->post('/admin/stripe-accounts', [
        'display_name' => 'Bad',
        'publishable_key' => 'not-a-key',
        'secret_key' => 'also-bad',
        'status' => 'active',
    ])->assertSessionHasErrors(['publishable_key', 'secret_key']);

    expect(StripeAccount::where('display_name', 'Bad')->exists())->toBeFalse();
});

test('legacy account cannot be deleted and unauthorized users are blocked', function () {
    $this->get('/admin/stripe-accounts')->assertRedirect('/login');

    $admin = makeTenantSuperAdmin();
    $legacy = makeTenantAccount(null, ['is_legacy' => true]);

    $this->actingAs($admin)->delete("/admin/stripe-accounts/{$legacy->id}")->assertSessionHasErrors('account');
    expect(StripeAccount::where('id', $legacy->id)->exists())->toBeTrue();

    $plain = User::factory()->create();
    $this->actingAs($plain)->get('/admin/stripe-accounts')->assertForbidden();
    $this->actingAs($plain)->post('/admin/stripe-accounts', ['display_name' => 'X', 'status' => 'active'])->assertForbidden();
    $this->actingAs($plain)->put("/admin/stripe-accounts/{$legacy->id}", ['display_name' => 'X', 'status' => 'active'])->assertForbidden();
    $this->actingAs($plain)->delete("/admin/stripe-accounts/{$legacy->id}")->assertForbidden();
});

// ── Phase 2: client-company linking ───────────────────────────────────────

test('clients can be linked to a company and filtered', function () {
    $company = makeTenantCompany('Link Co');
    $linked = Client::create(['company_name' => 'Linked', 'company_id' => $company->id]);
    $unlinked = Client::create(['company_name' => 'Free']);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/clients?company_id='.$company->id)
        ->assertOk()
        ->assertSee('Linked', false)
        ->assertDontSee('Free');

    $this->actingAs($user)->get('/admin/clients?company_id=unassigned')
        ->assertOk()
        ->assertSee('Free', false)
        ->assertDontSee('Linked');

    // Assign via update.
    $editor = makeTenantSuperAdmin(['edit-client']);
    $this->actingAs($editor)->put("/admin/clients/{$unlinked->id}", ['company_id' => $company->id])
        ->assertRedirect();
    expect($unlinked->refresh()->company_id)->toBe($company->id);

    // Unknown company rejected.
    $this->actingAs($editor)->put("/admin/clients/{$linked->id}", ['company_id' => 999999])
        ->assertSessionHasErrors('company_id');
});

// ── Phase 3: per-company DDR ──────────────────────────────────────────────

test('ddr link renders company branding and rejects bad links', function () {
    $company = makeTenantCompany('DDR Co');
    $account = makeTenantAccount($company, ['publishable_key' => 'pk_live_ddr']);

    $this->get('/ddr/'.$account->public_id)
        ->assertOk()
        ->assertSee('DDR Co', false)
        ->assertSee('pk_live_ddr', false);

    // Legacy global form untouched.
    $this->get('/ddr')->assertOk()->assertSee('All in IT Solutions', false);

    $this->get('/ddr/not-a-uuid')->assertNotFound();

    $account->update(['status' => 'disabled']);
    $this->get('/ddr/'.$account->public_id)->assertNotFound();
});

test('ddr store creates client linked to the account company', function () {
    $company = makeTenantCompany('Form Co');
    $account = makeTenantAccount($company);

    $response = $this->post('/ddr/'.$account->public_id, [
        'company_name' => 'Form Client',
        'account_name' => 'Form Client',
        'bsb' => '123456',
        'account_number' => '12345678',
        'contacts' => [[
            'full_name' => 'Form Person',
            'email' => 'form@example.com',
            'phone' => '0400000000',
        ]],
    ]);

    $response->assertRedirect(route('onboarding.thanks'));

    $client = Client::where('company_name', 'Form Client')->firstOrFail();
    expect($client->company_id)->toBe($company->id)
        ->and($client->mandate_status)->toBe('pending')
        ->and($client->contacts->first()?->email)->toBe('form@example.com');
});

// ── Phase 4: per-company bulk charging ────────────────────────────────────

test('bulk confirm stamps the account and charges through it', function () {
    $company = makeTenantCompany('Bulk Co');
    $account = makeTenantAccount($company);
    $customer = makeBecsCustomer($account);

    $admin = makeTenantSuperAdmin();

    // Filtered index shows only this account's customers.
    $other = makeTenantAccount(makeTenantCompany('Other Co'));
    makeBecsCustomer($other, 'cus_other', 'pm_other');

    $this->actingAs($admin)->get('/admin/stripe/bulk-charge?stripe_account='.$account->id)
        ->assertOk()
        ->assertSee('Tenant Customer', false)
        ->assertDontSee('cus_other');

    $pm = StripePaymentMethod::where('stripe_payment_method_id', 'pm_t1')->firstOrFail();

    $response = $this->actingAs($admin)->post('/admin/stripe/bulk-charge/confirm', [
        'stripe_account_id' => $account->id,
        'items' => [[
            'stripe_customer_id' => $customer->id,
            'stripe_payment_method_id' => $pm->id,
            'amount' => 5000,
            'description' => 'Test bulk',
        ]],
    ]);

    // Job runs sync with a dummy key and fails at Stripe, but the batch and
    // its account stamping must exist.
    $batch = StripeChargeBatch::latest('id')->firstOrFail();
    expect($batch->stripe_account_id)->toBe($account->id);

    $item = $batch->items()->firstOrFail();
    expect($item->stripe_account_id)->toBe($account->id);
});

test('bulk confirm rejects cross-account customers', function () {
    $accountA = makeTenantAccount(makeTenantCompany('Co A'));
    $accountB = makeTenantAccount(makeTenantCompany('Co B'));
    $customerB = makeBecsCustomer($accountB, 'cus_b', 'pm_b');
    $pmB = StripePaymentMethod::where('stripe_payment_method_id', 'pm_b')->firstOrFail();

    $admin = makeTenantSuperAdmin();

    $this->actingAs($admin)->post('/admin/stripe/bulk-charge/confirm', [
        'stripe_account_id' => $accountA->id,
        'items' => [[
            'stripe_customer_id' => $customerB->id,
            'stripe_payment_method_id' => $pmB->id,
            'amount' => 1000,
        ]],
    ])->assertSessionHasErrors('items');
});

test('bulk confirm stays backward compatible without an account id', function () {
    $customer = StripeCustomer::create([
        'stripe_customer_id' => 'cus_legacyx', 'stripe_data' => [],
        'name' => 'Legacy', 'email' => 'legacy@example.com',
    ]);
    $pm = StripePaymentMethod::create([
        'stripe_customer_id' => $customer->id, 'stripe_data' => [],
        'stripe_payment_method_id' => 'pm_legacyx', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);

    $admin = makeTenantSuperAdmin();

    $this->actingAs($admin)->post('/admin/stripe/bulk-charge/confirm', [
        'items' => [[
            'stripe_customer_id' => $customer->id,
            'stripe_payment_method_id' => $pm->id,
            'amount' => 2500,
        ]],
    ])->assertRedirect();

    expect(StripeChargeBatch::latest('id')->firstOrFail()->stripe_account_id)->toBeNull();
});

test('bulk all-mode splits selections into one batch per account', function () {
    $accountA = makeTenantAccount(makeTenantCompany('Split A'));
    $accountB = makeTenantAccount(makeTenantCompany('Split B'));
    $custA = makeBecsCustomer($accountA, 'cus_sa', 'pm_sa');
    $custB = makeBecsCustomer($accountB, 'cus_sb', 'pm_sb');
    $custNull = StripeCustomer::create([
        'stripe_customer_id' => 'cus_sn', 'stripe_data' => [], 'name' => 'Null', 'email' => 'n@x.com',
    ]);
    $pmNull = StripePaymentMethod::create([
        'stripe_customer_id' => $custNull->id, 'stripe_data' => [],
        'stripe_payment_method_id' => 'pm_sn', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);

    $admin = makeTenantSuperAdmin();

    // Index shows every pool with its account badge.
    $this->actingAs($admin)->get('/admin/stripe/bulk-charge')
        ->assertOk()
        ->assertSee($accountA->display_name, false)
        ->assertSee('Legacy pool', false);

    $item = fn ($customer, $pm, $amount) => [
        'stripe_customer_id' => $customer->id,
        'stripe_payment_method_id' => $pm->id,
        'amount' => $amount,
    ];

    $response = $this->actingAs($admin)->post('/admin/stripe/bulk-charge/confirm', [
        'items' => [
            $item($custA, StripePaymentMethod::where('stripe_payment_method_id', 'pm_sa')->firstOrFail(), 1000),
            $item($custB, StripePaymentMethod::where('stripe_payment_method_id', 'pm_sb')->firstOrFail(), 2000),
            $item($custNull, $pmNull, 3000),
        ],
    ]);

    $response->assertRedirect(route('admin.stripe.batches.index'));

    $accountIds = StripeChargeBatch::orderBy('id')->pluck('stripe_account_id')->all();
    expect($accountIds)->toBe([$accountA->id, $accountB->id, null]);

    // Single-pool selection still lands on the batch page directly.
    $solo = $this->actingAs($admin)->post('/admin/stripe/bulk-charge/confirm', [
        'stripe_account_id' => $accountA->id,
        'items' => [
            $item($custA, StripePaymentMethod::where('stripe_payment_method_id', 'pm_sa')->firstOrFail(), 1000),
        ],
    ]);
    $batch = StripeChargeBatch::latest('id')->firstOrFail();
    $solo->assertRedirect(route('admin.stripe.batches.show', $batch));

    // Batch page names the account it charged through.
    $this->actingAs($admin)->get(route('admin.stripe.batches.show', $batch))
        ->assertOk()
        ->assertSee($accountA->display_name, false);
});

test('batches list filters by account and shows badges', function () {
    $accountA = makeTenantAccount(makeTenantCompany('List A'), ['display_name' => 'List Account A']);
    $accountB = makeTenantAccount(makeTenantCompany('List B'), ['display_name' => 'List Account B']);

    StripeChargeBatch::create(['reference' => 'B-LA', 'stripe_account_id' => $accountA->id, 'status' => 'pending']);
    StripeChargeBatch::create(['reference' => 'B-LB', 'stripe_account_id' => $accountB->id, 'status' => 'pending']);
    StripeChargeBatch::create(['reference' => 'B-LN', 'status' => 'pending']);

    $admin = makeTenantSuperAdmin();

    $this->actingAs($admin)->get('/admin/stripe/batches')
        ->assertOk()
        ->assertSee('B-LA', false)
        ->assertSee('B-LB', false)
        ->assertSee('B-LN', false)
        ->assertSee('List Account A', false)
        ->assertSee('Legacy pool', false);

    $this->actingAs($admin)->get('/admin/stripe/batches?stripe_account='.$accountA->id)
        ->assertOk()
        ->assertSee('B-LA', false)
        ->assertDontSee('B-LB', false)
        ->assertDontSee('B-LN', false);

    $this->actingAs($admin)->get('/admin/stripe/batches?stripe_account=unassigned')
        ->assertOk()
        ->assertSee('B-LN', false)
        ->assertDontSee('B-LA', false);

    $this->actingAs($admin)->get('/admin/stripe/batches?stripe_account=999999')->assertNotFound();
});

test('batch items list filters by account and shows badges', function () {
    $accountA = makeTenantAccount(makeTenantCompany('Item A'), ['display_name' => 'Item Account A']);
    $accountB = makeTenantAccount(makeTenantCompany('Item B'));

    $batchA = StripeChargeBatch::create(['reference' => 'B-IA', 'stripe_account_id' => $accountA->id, 'status' => 'pending']);
    $batchB = StripeChargeBatch::create(['reference' => 'B-IB', 'stripe_account_id' => $accountB->id, 'status' => 'pending']);

    $custA = makeBecsCustomer($accountA, 'cus_ia', 'pm_ia');
    $custB = makeBecsCustomer($accountB, 'cus_ib', 'pm_ib');

    StripeChargeBatchItem::create([
        'batch_id' => $batchA->id, 'stripe_account_id' => $accountA->id,
        'stripe_customer_id' => $custA->id,
        'stripe_payment_method_id' => StripePaymentMethod::where('stripe_payment_method_id', 'pm_ia')->firstOrFail()->id,
        'amount' => 1000, 'status' => 'pending',
    ]);
    StripeChargeBatchItem::create([
        'batch_id' => $batchB->id, 'stripe_account_id' => $accountB->id,
        'stripe_customer_id' => $custB->id,
        'stripe_payment_method_id' => StripePaymentMethod::where('stripe_payment_method_id', 'pm_ib')->firstOrFail()->id,
        'amount' => 2000, 'status' => 'pending',
    ]);

    $admin = makeTenantSuperAdmin();

    $this->actingAs($admin)->get('/admin/stripe/batches?view=items')
        ->assertOk()
        ->assertSee('B-IA', false)
        ->assertSee('B-IB', false)
        ->assertSee('Item Account A', false);

    $this->actingAs($admin)->get('/admin/stripe/batches?view=items&stripe_account='.$accountA->id)
        ->assertOk()
        ->assertSee('B-IA', false)
        ->assertDontSee('B-IB', false);

    Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    $member = User::factory()->create();
    $member->companies()->attach(makeTenantCompany('Other List')->id);

    // Member of an unrelated company is blocked from account A's list.
    $this->actingAs($member)->get('/admin/stripe/batches?stripe_account='.$accountA->id)->assertForbidden();
    $this->actingAs($member)->get('/admin/stripe/batches?view=items&stripe_account='.$accountA->id)->assertForbidden();
});

test('users cannot charge through another companys account', function () {
    $companyA = makeTenantCompany('Mine');
    $companyB = makeTenantCompany('Theirs');
    $accountB = makeTenantAccount($companyB);

    Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->companies()->attach($companyA->id);

    $this->actingAs($user)->get('/admin/stripe/bulk-charge?stripe_account='.$accountB->id)->assertForbidden();

    $this->actingAs($user)->post('/admin/stripe/bulk-charge/confirm', [
        'stripe_account_id' => $accountB->id,
        'items' => [[
            'stripe_customer_id' => 1,
            'stripe_payment_method_id' => 1,
            'amount' => 1000,
        ]],
    ])->assertForbidden();
});

// ── Phase 5: per-account webhooks ─────────────────────────────────────────

function stripeTestEvent(string $type, array $object): array
{
    return [
        'id' => 'evt_test123',
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => $object],
    ];
}

test('per-account webhook verifies signature and scopes mandate updates', function () {
    $accountA = makeTenantAccount(makeTenantCompany('WA'), ['webhook_secret' => 'whsec_aaa']);
    $accountB = makeTenantAccount(makeTenantCompany('WB'), ['webhook_secret' => 'whsec_bbb']);

    // Same-shaped mirrors on both accounts (Stripe ids are globally unique).
    $cusA = StripeCustomer::create(['stripe_customer_id' => 'cus_wa', 'stripe_data' => [], 'stripe_account_id' => $accountA->id]);
    $cusB = StripeCustomer::create(['stripe_customer_id' => 'cus_wb', 'stripe_data' => [], 'stripe_account_id' => $accountB->id]);
    $pmA = StripePaymentMethod::create([
        'stripe_customer_id' => $cusA->id, 'stripe_data' => [], 'stripe_account_id' => $accountA->id,
        'stripe_payment_method_id' => 'pm_aaa', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);
    $pmB = StripePaymentMethod::create([
        'stripe_customer_id' => $cusB->id, 'stripe_data' => [], 'stripe_account_id' => $accountB->id,
        'stripe_payment_method_id' => 'pm_bbb', 'type' => 'au_becs_debit', 'status' => 'active',
    ]);

    $payloadA = json_encode(stripeTestEvent('mandate.updated', [
        'id' => 'mandate_1', 'object' => 'mandate', 'status' => 'inactive', 'payment_method' => 'pm_aaa',
    ]));
    $payloadB = json_encode(stripeTestEvent('mandate.updated', [
        'id' => 'mandate_2', 'object' => 'mandate', 'status' => 'inactive', 'payment_method' => 'pm_bbb',
    ]));

    // Wrong secret → 400.
    stripeWebhookPost('/webhooks/stripe/'.$accountA->id, $payloadA, 'whsec_wrong')
        ->assertStatus(400);

    // Account B's event posted to account A's endpoint must not touch B's row.
    stripeWebhookPost('/webhooks/stripe/'.$accountA->id, $payloadB, 'whsec_aaa')
        ->assertOk();
    expect($pmB->refresh()->status)->toBe('active');

    // Correct endpoint deactivates its own row only.
    stripeWebhookPost('/webhooks/stripe/'.$accountA->id, $payloadA, 'whsec_aaa')
        ->assertOk();

    expect($pmA->refresh()->status)->toBe('inactive')
        ->and($pmB->refresh()->status)->toBe('active');
});

test('per-account webhook requires configured secret and known account', function () {
    $account = makeTenantAccount(null, ['webhook_secret' => null]);

    $payload = json_encode(stripeTestEvent('mandate.updated', ['id' => 'm', 'status' => 'active']));

    stripeWebhookPost('/webhooks/stripe/'.$account->id, $payload, 'whsec_x')
        ->assertStatus(400);

    stripeWebhookPost('/webhooks/stripe/999999', $payload, 'whsec_x')
        ->assertNotFound();
});

test('ddr mirror stamps account without stripe_data errors', function () {
    $account = makeTenantAccount(makeTenantCompany('Mirror Co'));

    $controller = new DdrController(app(StripeAccountResolver::class));
    $mirror = new ReflectionMethod($controller, 'mirrorCustomer');
    $mirror->setAccessible(true);
    $mirror->invoke($controller, $account, 'cus_mirror', 'Mirror Name', 'mirror@example.com');

    $row = StripeCustomer::where('stripe_customer_id', 'cus_mirror')->firstOrFail();
    expect($row->stripe_account_id)->toBe($account->id)
        ->and($row->name)->toBe('Mirror Name');
});

test('legacy webhook still rejects bad signatures', function () {
    config(['services.stripe.webhook_secret' => 'whsec_legacy']);

    $payload = json_encode(stripeTestEvent('mandate.updated', ['id' => 'm', 'status' => 'active']));

    stripeWebhookPost('/webhooks/stripe', $payload, 'whsec_nope')
        ->assertStatus(400);
});
