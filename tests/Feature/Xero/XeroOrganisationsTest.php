<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeXeroOrgAdmin(array $permissions): User
{
    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'xero-org-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeXeroOrgFixtures(): array
{
    $owner = User::factory()->create();

    $connection = XeroConnection::create([
        'user_id' => $owner->id,
        'access_token' => 'fake-access',
        'refresh_token' => 'fake-refresh',
        'token_expires_at' => now()->addHour(),
        'is_active' => true,
        'needs_reauth' => false,
    ]);

    $company = Company::create(['name' => 'Acme Group', 'slug' => 'acme-group', 'email' => 'acme-group@example.com']);
    $otherCompany = Company::create(['name' => 'Other Co', 'slug' => 'other-co', 'email' => 'other@example.com']);

    $account = StripeAccount::create([
        'company_id' => $company->id,
        'display_name' => 'Acme Main',
        'status' => 'active',
    ]);
    $otherAccount = StripeAccount::create([
        'company_id' => $otherCompany->id,
        'display_name' => 'Other Main',
        'status' => 'active',
    ]);

    $tenant = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-org-aaa',
        'tenant_name' => 'Acme Org',
        'is_active' => true,
    ]);

    $client = Client::create([
        'company_name' => 'Exact Email Co',
        'billing_email' => 'exact@example.com',
        'company_id' => $company->id,
        'stripe_customer_id' => 'cus_123',
    ]);

    StripeCustomer::create([
        'stripe_customer_id' => 'cus_123',
        'stripe_account_id' => $account->id,
        'name' => 'Exact Email Co',
        'email' => 'exact@example.com',
        'stripe_data' => [],
    ]);

    $emailContact = XeroContact::create([
        'xero_tenant_id' => $tenant->id,
        'xero_contact_id' => 'contact-email',
        'name' => 'Totally Different Name Ltd',
        'email' => 'exact@example.com',
        'is_customer' => true,
        'xero_contact_status' => 'ACTIVE',
    ]);

    return compact('connection', 'company', 'otherCompany', 'account', 'otherAccount', 'tenant', 'client', 'emailContact');
}

test('organisations index shows tenants with their links', function () {
    $admin = makeXeroOrgAdmin(['view-xero']);
    ['tenant' => $tenant, 'company' => $company, 'account' => $account] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id, 'default_stripe_account_id' => $account->id]);

    $this->actingAs($admin)->get(route('admin.xero.organisations.index'))
        ->assertOk()
        ->assertSee('Acme Org', false)
        ->assertSee('Acme Group', false)
        ->assertSee('Acme Main', false);
});

test('organisations index is forbidden without view-xero', function () {
    makeXeroOrgFixtures();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.xero.organisations.index'))->assertForbidden();
});

test('admin can link a tenant to a company and stripe account', function () {
    $admin = makeXeroOrgAdmin(['view-xero', 'configure-xero']);
    ['tenant' => $tenant, 'company' => $company, 'account' => $account] = makeXeroOrgFixtures();

    $this->actingAs($admin)->put(route('admin.xero.organisations.update', $tenant), [
        'company_id' => $company->id,
        'default_stripe_account_id' => $account->id,
        'is_active' => true,
    ])->assertRedirect(route('admin.xero.organisations.show', $tenant));

    $tenant->refresh();
    expect($tenant->company_id)->toBe($company->id)
        ->and($tenant->default_stripe_account_id)->toBe($account->id);
});

test('linking a stripe account from another company fails validation', function () {
    $admin = makeXeroOrgAdmin(['view-xero', 'configure-xero']);
    ['tenant' => $tenant, 'company' => $company, 'otherAccount' => $otherAccount] = makeXeroOrgFixtures();

    $this->actingAs($admin)->put(route('admin.xero.organisations.update', $tenant), [
        'company_id' => $company->id,
        'default_stripe_account_id' => $otherAccount->id,
    ])->assertSessionHasErrors('default_stripe_account_id');

    expect($tenant->fresh()->default_stripe_account_id)->toBeNull();
});

test('clearing the company drops a company-owned default account', function () {
    $admin = makeXeroOrgAdmin(['view-xero', 'configure-xero']);
    ['tenant' => $tenant, 'company' => $company, 'account' => $account] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id, 'default_stripe_account_id' => $account->id]);

    $this->actingAs($admin)->put(route('admin.xero.organisations.update', $tenant), [
        'company_id' => null,
        'default_stripe_account_id' => $account->id,
    ])->assertRedirect();

    expect($tenant->fresh()->default_stripe_account_id)->toBeNull();
});

test('organisation hub shows stripe customers with match state', function () {
    $admin = makeXeroOrgAdmin(['view-xero']);
    ['tenant' => $tenant, 'company' => $company, 'account' => $account, 'emailContact' => $contact] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id, 'default_stripe_account_id' => $account->id]);

    $this->actingAs($admin)->get(route('admin.xero.organisations.show', $tenant))
        ->assertOk()
        ->assertSee('Acme Org', false)
        ->assertSee('Acme Group', false)
        ->assertSee('cus_123', false)
        ->assertSee('Exact Email Co', false)
        ->assertSee('No Xero link', false);
});

test('reconcile index lists unmatched contacts with suggestions', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['tenant' => $tenant] = makeXeroOrgFixtures();

    XeroContact::create([
        'xero_tenant_id' => $tenant->id,
        'xero_contact_id' => 'contact-nomatch',
        'name' => 'Zzz Qqq Xyzzy',
        'email' => 'nomatch@example.com',
        'is_customer' => true,
    ]);

    $this->actingAs($admin)->get(route('admin.xero.reconcile.index'))
        ->assertOk()
        ->assertSee('Totally Different Name Ltd', false)
        ->assertSee('Exact Email Co', false)
        ->assertSee('Auto-match exact', false);
});

test('auto-match links abn matches with auto method', function () {
    $admin = makeXeroOrgAdmin(['view-contact', 'edit-contact']);
    ['tenant' => $tenant] = makeXeroOrgFixtures();

    $abnClient = Client::create(['company_name' => 'ABN Pty Ltd', 'abn' => '51 824 753 556']);
    $abnContact = XeroContact::create([
        'xero_tenant_id' => $tenant->id,
        'xero_contact_id' => 'contact-abn',
        'name' => 'Something Else Entirely',
        'email' => 'abn-unrelated@example.com',
        'tax_number' => '51 824 753 556',
        'is_customer' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.xero.reconcile.auto-match'), ['tenant_id' => $tenant->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    $abnContact->refresh();
    expect($abnContact->is_matched)->toBeTrue()
        ->and($abnContact->client_id)->toBe($abnClient->id)
        ->and($abnContact->match_method)->toBe('auto:abn');
});

test('auto-match links exact matches and leaves fuzzy ones for review', function () {
    $admin = makeXeroOrgAdmin(['view-contact', 'edit-contact']);
    ['tenant' => $tenant, 'emailContact' => $emailContact] = makeXeroOrgFixtures();

    Client::create(['company_name' => 'Smith Plumbing Pty Ltd']);
    $nameOnly = XeroContact::create([
        'xero_tenant_id' => $tenant->id,
        'xero_contact_id' => 'contact-name',
        'name' => 'Smith Plumbing',
        'email' => 'smith-unrelated@example.com',
        'is_customer' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.xero.reconcile.auto-match'), ['tenant_id' => $tenant->id])
        ->assertRedirect();

    expect($emailContact->fresh()->is_matched)->toBeTrue()
        ->and($emailContact->fresh()->match_method)->toBe('auto:email')
        ->and($nameOnly->fresh()->is_matched)->toBeFalse();
});

test('per-tenant auto-match json endpoint links exact matches', function () {
    $admin = makeXeroOrgAdmin(['edit-contact']);
    ['tenant' => $tenant, 'emailContact' => $emailContact] = makeXeroOrgFixtures();

    $response = $this->actingAs($admin)->postJson(route('admin.xero.contacts.auto-match'), [
        'tenant_id' => $tenant->id,
    ]);

    $response->assertOk()->assertJson(['ok' => true, 'linked' => 1]);
    expect($emailContact->fresh()->is_matched)->toBeTrue();
});

test('reconcile filters by company', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['tenant' => $tenant, 'company' => $company, 'connection' => $connection] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id]);

    $otherTenant = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-other',
        'tenant_name' => 'Other Org',
        'is_active' => true,
    ]);
    XeroContact::create([
        'xero_tenant_id' => $otherTenant->id,
        'xero_contact_id' => 'contact-other',
        'name' => 'Other Org Contact',
        'email' => 'other-org@example.com',
        'is_customer' => true,
    ]);

    $this->actingAs($admin)->get(route('admin.xero.reconcile.index', ['company' => $company->id]))
        ->assertOk()
        ->assertSee('Totally Different Name Ltd', false)
        ->assertDontSee('Other Org Contact', false);
});

test('reconcile filters by stripe account through default or company', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['tenant' => $tenant, 'company' => $company, 'account' => $account, 'connection' => $connection] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id, 'default_stripe_account_id' => $account->id]);

    $companyTenant = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-company-only',
        'tenant_name' => 'Company Only Org',
        'company_id' => $company->id,
        'is_active' => true,
    ]);
    XeroContact::create([
        'xero_tenant_id' => $companyTenant->id,
        'xero_contact_id' => 'contact-company-only',
        'name' => 'Company Only Contact',
        'email' => 'company-only@example.com',
        'is_customer' => true,
    ]);

    $outsideTenant = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-outside',
        'tenant_name' => 'Outside Org',
        'is_active' => true,
    ]);
    XeroContact::create([
        'xero_tenant_id' => $outsideTenant->id,
        'xero_contact_id' => 'contact-outside',
        'name' => 'Outside Contact',
        'email' => 'outside@example.com',
        'is_customer' => true,
    ]);

    $this->actingAs($admin)->get(route('admin.xero.reconcile.index', ['stripe_account' => $account->id]))
        ->assertOk()
        ->assertSee('Totally Different Name Ltd', false)
        ->assertSee('Company Only Contact', false)
        ->assertDontSee('Outside Contact', false);
});

test('auto-match respects the company scope', function () {
    $admin = makeXeroOrgAdmin(['view-contact', 'edit-contact']);
    ['tenant' => $tenant, 'company' => $company, 'connection' => $connection, 'client' => $client] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id]);

    $outsideTenant = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-outside',
        'tenant_name' => 'Outside Org',
        'is_active' => true,
    ]);
    $outsideContact = XeroContact::create([
        'xero_tenant_id' => $outsideTenant->id,
        'xero_contact_id' => 'contact-outside-exact',
        'name' => 'Outside Exact',
        'email' => $client->billing_email,
        'is_customer' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.xero.reconcile.auto-match'), ['company_id' => $company->id])
        ->assertRedirect();

    expect($outsideContact->fresh()->is_matched)->toBeFalse();
});

test('reconcile dropdown is scoped to the filtered company', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['tenant' => $tenant, 'company' => $company, 'otherCompany' => $otherCompany] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id]);

    $outsideClient = Client::create([
        'company_name' => 'Outside Client Co',
        'billing_email' => 'outside-client@example.com',
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.xero.reconcile.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertSee('Exact Email Co — exact@example.com · cus_123', false);
    $response->assertDontSee('Outside Client Co', false);
    $response->assertSee('for Acme Group', false);
});

test('reconcile dropdown is scoped to the filtered organisation', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['tenant' => $tenant, 'company' => $company, 'account' => $account, 'otherCompany' => $otherCompany] = makeXeroOrgFixtures();

    $tenant->update(['company_id' => $company->id, 'default_stripe_account_id' => $account->id]);

    $outsideClient = Client::create([
        'company_name' => 'Outside Client Co',
        'billing_email' => 'outside-client@example.com',
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.xero.reconcile.index', ['tenant' => $tenant->id]));

    $response->assertOk();
    $response->assertSee('Exact Email Co — exact@example.com · cus_123', false);
    $response->assertDontSee('Outside Client Co', false);
    $response->assertSee('for Acme Org', false);
});

test('reconcile dropdown is scoped to the filtered stripe account', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['tenant' => $tenant, 'company' => $company, 'otherCompany' => $otherCompany, 'otherAccount' => $otherAccount] = makeXeroOrgFixtures();

    $acmeAccount = StripeAccount::where('display_name', 'Acme Main')->firstOrFail();
    $tenant->update(['company_id' => $company->id, 'default_stripe_account_id' => $acmeAccount->id]);

    StripeCustomer::create([
        'stripe_customer_id' => 'cus_other',
        'stripe_account_id' => $otherAccount->id,
        'name' => 'Other Stripe Co',
        'email' => 'other-stripe@example.com',
        'stripe_data' => [],
    ]);
    $outsideClient = Client::create([
        'company_name' => 'Other Stripe Client',
        'billing_email' => 'other-stripe@example.com',
        'company_id' => $otherCompany->id,
        'stripe_customer_id' => 'cus_other',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.xero.reconcile.index', ['stripe_account' => $acmeAccount->id]));

    $response->assertOk();
    $response->assertSee('Exact Email Co — exact@example.com · cus_123', false);
    $response->assertDontSee('Other Stripe Client', false);
    expect($company->id)->not->toBe($otherCompany->id);
});

test('reconcile dropdown shows every customer without a scope', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['otherCompany' => $otherCompany] = makeXeroOrgFixtures();

    $outsideClient = Client::create([
        'company_name' => 'Outside Client Co',
        'billing_email' => 'outside-client@example.com',
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.xero.reconcile.index'));

    $response->assertOk();
    $response->assertSee('Outside Client Co — outside-client@example.com', false);
    $response->assertSee('Exact Email Co — exact@example.com · cus_123', false);
    $response->assertSee('All customers', false);
});

test('reconcile dropdown shows company and stripe account ownership', function () {
    $admin = makeXeroOrgAdmin(['view-contact']);
    ['otherCompany' => $otherCompany] = makeXeroOrgFixtures();

    Client::create([
        'company_name' => 'Outside Client Co',
        'billing_email' => 'outside-client@example.com',
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.xero.reconcile.index'));

    $response->assertOk();
    // Client linked to Acme Group with cus_123 on the Acme Main stripe account.
    $response->assertSee('Exact Email Co — exact@example.com · cus_123 [Acme Group · Acme Main]', false);
    // Client with only a company link still shows where it belongs.
    $response->assertSee('Outside Client Co — outside-client@example.com [Other Co]', false);
});

test('assigning a match requires edit-contact', function () {
    $plain = User::factory()->create();
    ['emailContact' => $contact, 'tenant' => $tenant, 'client' => $client] = makeXeroOrgFixtures();

    $this->actingAs($plain)->postJson(route('admin.xero.contacts.assign'), [
        'contact_id' => $contact->xero_contact_id,
        'customer_id' => $client->id,
        'tenant_id' => $tenant->id,
    ])->assertForbidden();

    $editor = makeXeroOrgAdmin(['edit-contact']);

    $this->actingAs($editor)->postJson(route('admin.xero.contacts.assign'), [
        'contact_id' => $contact->xero_contact_id,
        'customer_id' => $client->id,
        'tenant_id' => $tenant->id,
    ])->assertOk()->assertJson(['ok' => true]);

    expect($contact->fresh()->is_matched)->toBeTrue();
});
