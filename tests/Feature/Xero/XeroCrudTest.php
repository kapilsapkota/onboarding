<?php

use App\Models\User;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroRepeatingInvoice;
use App\Models\XeroTenant;
use App\Services\XeroContactSyncService;
use App\Services\XeroInvoiceSyncService;
use App\Services\XeroRepeatingSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeXeroCrudAdmin(array $permissions): User
{
    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'xero-crud-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeXeroCrudFixtures(): array
{
    $admin = User::factory()->create();

    $connection = XeroConnection::create([
        'user_id' => $admin->id,
        'access_token' => 'fake-access',
        'refresh_token' => 'fake-refresh',
        'token_expires_at' => now()->addHour(),
        'is_active' => true,
        'needs_reauth' => false,
    ]);

    $tenantA = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-aaa',
        'tenant_name' => 'Org A',
        'is_active' => true,
    ]);

    $tenantB = XeroTenant::create([
        'xero_connection_id' => $connection->id,
        'tenant_id' => 'tenant-bbb',
        'tenant_name' => 'Org B',
        'is_active' => true,
    ]);

    $contact = XeroContact::create([
        'xero_tenant_id' => $tenantA->id,
        'xero_contact_id' => 'contact-111',
        'name' => 'Acme Pty Ltd',
        'email' => 'acme@example.com',
        'is_customer' => true,
        'xero_contact_status' => 'ACTIVE',
    ]);

    return compact('connection', 'tenantA', 'tenantB', 'contact');
}

function xeroInvoicePayload(string $id, string $contactId, string $status = 'DRAFT'): array
{
    return [
        'InvoiceID' => $id,
        'InvoiceNumber' => 'INV-0001',
        'Type' => 'ACCREC',
        'Status' => $status,
        'Contact' => ['ContactID' => $contactId, 'Name' => 'Acme Pty Ltd'],
        'Date' => '/Date(1756684800000+0000)/',
        'DueDate' => '/Date(1757376000000+0000)/',
        'CurrencyCode' => 'AUD',
        'SubTotal' => 100.00,
        'TotalTax' => 10.00,
        'Total' => 110.00,
        'AmountDue' => 110.00,
        'AmountPaid' => 0,
        'LineItems' => [[
            'LineItemID' => 'line-1',
            'Description' => 'Consulting',
            'Quantity' => 1,
            'UnitAmount' => 100,
            'AccountCode' => '200',
            'TaxType' => 'OUTPUT',
            'LineAmount' => 100,
        ]],
        'UpdatedDateUTC' => '/Date(1756684800000+0000)/',
    ];
}

test('invoices index shows all tenants and filters by tenant', function () {
    $admin = makeXeroCrudAdmin(['view-invoice']);
    ['tenantA' => $a, 'tenantB' => $b, 'contact' => $contact] = makeXeroCrudFixtures();

    XeroInvoice::create(['xero_tenant_id' => $a->id, 'xero_invoice_id' => 'inv-a', 'xero_invoice_number' => 'INV-A', 'type' => 'ACCREC', 'status' => 'DRAFT', 'total' => 10, 'amount_due' => 10]);
    XeroInvoice::create(['xero_tenant_id' => $b->id, 'xero_invoice_id' => 'inv-b', 'xero_invoice_number' => 'INV-B', 'type' => 'ACCREC', 'status' => 'AUTHORISED', 'total' => 20, 'amount_due' => 20]);

    $this->actingAs($admin)->get(route('admin.xero.invoices.index'))
        ->assertOk()->assertSee('INV-A', false)->assertSee('INV-B', false);

    $this->actingAs($admin)->get(route('admin.xero.invoices.index', ['tenant' => $a->id]))
        ->assertOk()->assertSee('INV-A', false)->assertDontSee('INV-B', false);
});

test('guests cannot view invoices without permission', function () {
    makeXeroCrudFixtures();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.xero.invoices.index'))->assertForbidden();
});

test('creating an invoice writes through to xero then mirrors locally', function () {
    $admin = makeXeroCrudAdmin(['view-invoice', 'create-invoice']);
    ['tenantA' => $tenant, 'contact' => $contact] = makeXeroCrudFixtures();

    $raw = xeroInvoicePayload('inv-new', 'contact-111');

    Http::fake(function (Request $request) use ($raw) {
        $url = $request->url();

        if ($request->method() === 'PUT' && str_ends_with($url, '/Invoices')) {
            return Http::response(['Invoices' => [$raw]], 200);
        }

        if ($request->method() === 'GET' && str_contains($url, '/Invoices/inv-new')) {
            return Http::response(['Invoices' => [$raw]], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($admin)->post(route('admin.xero.invoices.store'), [
        'xero_tenant_id' => $tenant->id,
        'xero_contact_id' => $contact->id,
        'type' => 'ACCREC',
        'status' => 'DRAFT',
        'invoice_date' => '2026-09-01',
        'due_date' => '2026-09-15',
        'line_items' => [[
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_amount' => 100,
            'account_code' => '200',
            'tax_type' => 'OUTPUT',
        ]],
    ])->assertRedirect();

    $invoice = XeroInvoice::where('xero_invoice_id', 'inv-new')->firstOrFail();
    expect($invoice->xero_tenant_id)->toBe($tenant->id)
        ->and($invoice->total)->toEqual(110.00);

    Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/Invoices'));
});

test('updating a draft invoice syncs the change back', function () {
    $admin = makeXeroCrudAdmin(['view-invoice', 'edit-invoice']);
    ['tenantA' => $tenant, 'contact' => $contact] = makeXeroCrudFixtures();

    $invoice = XeroInvoice::create(['xero_tenant_id' => $tenant->id, 'xero_invoice_id' => 'inv-draft', 'xero_invoice_number' => 'INV-1', 'type' => 'ACCREC', 'status' => 'DRAFT', 'total' => 50, 'amount_due' => 50]);

    $updated = xeroInvoicePayload('inv-draft', 'contact-111');
    $updated['Reference'] = 'Updated ref';

    Http::fake(function (Request $request) use ($updated) {
        if ($request->method() === 'POST' && str_ends_with($request->url(), '/Invoices')) {
            return Http::response(['Invoices' => [$updated]], 200);
        }

        return Http::response(['Invoices' => [$updated]], 200);
    });

    $this->actingAs($admin)->put(route('admin.xero.invoices.update', $invoice), [
        'reference' => 'Updated ref',
        'line_items' => [[
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_amount' => 100,
            'account_code' => '200',
        ]],
    ])->assertRedirect();

    expect($invoice->fresh()->reference)->toBe('Updated ref');
});

test('paid invoices cannot be edited', function () {
    $admin = makeXeroCrudAdmin(['view-invoice', 'edit-invoice']);
    ['tenantA' => $tenant] = makeXeroCrudFixtures();

    $invoice = XeroInvoice::create(['xero_tenant_id' => $tenant->id, 'xero_invoice_id' => 'inv-paid', 'xero_invoice_number' => 'INV-P', 'type' => 'ACCREC', 'status' => 'PAID', 'total' => 10, 'amount_due' => 0]);

    $this->actingAs($admin)->get(route('admin.xero.invoices.edit', $invoice))->assertStatus(422);
    $this->actingAs($admin)->put(route('admin.xero.invoices.update', $invoice), ['reference' => 'x'])->assertStatus(422);
});

test('destroy deletes drafts and voids authorised invoices', function () {
    $admin = makeXeroCrudAdmin(['view-invoice', 'delete-invoice']);
    ['tenantA' => $tenant] = makeXeroCrudFixtures();

    $draft = XeroInvoice::create(['xero_tenant_id' => $tenant->id, 'xero_invoice_id' => 'inv-del', 'xero_invoice_number' => 'INV-D', 'type' => 'ACCREC', 'status' => 'DRAFT', 'total' => 10, 'amount_due' => 10]);
    $auth = XeroInvoice::create(['xero_tenant_id' => $tenant->id, 'xero_invoice_id' => 'inv-void', 'xero_invoice_number' => 'INV-V', 'type' => 'ACCREC', 'status' => 'AUTHORISED', 'total' => 10, 'amount_due' => 10]);

    $deleted = xeroInvoicePayload('inv-del', 'contact-111', 'DELETED');
    $voided = xeroInvoicePayload('inv-void', 'contact-111', 'VOIDED');

    Http::fake(function (Request $request) use ($deleted, $voided) {
        $body = $request->data();

        if ($request->method() === 'POST' && str_ends_with($request->url(), '/Invoices')) {
            $status = $body['Invoices'][0]['Status'] ?? null;

            return Http::response(['Invoices' => [$status === 'VOIDED' ? $voided : $deleted]], 200);
        }

        if (str_contains($request->url(), 'inv-del')) {
            return Http::response(['Invoices' => [$deleted]], 200);
        }

        return Http::response(['Invoices' => [$voided]], 200);
    });

    $this->actingAs($admin)->delete(route('admin.xero.invoices.destroy', $draft))->assertRedirect();
    expect($draft->fresh()->status)->toBe('DELETED');

    $this->actingAs($admin)->delete(route('admin.xero.invoices.destroy', $auth))->assertRedirect();
    expect($auth->fresh()->status)->toBe('VOIDED');
});

test('customers archive and restore through xero', function () {
    $admin = makeXeroCrudAdmin(['view-contact', 'edit-contact', 'delete-contact']);
    ['tenantA' => $tenant, 'contact' => $contact] = makeXeroCrudFixtures();

    $archivedPayload = [
        'ContactID' => 'contact-111',
        'Name' => 'Acme Pty Ltd',
        'EmailAddress' => 'acme@example.com',
        'ContactStatus' => 'ARCHIVED',
        'IsCustomer' => true,
        'UpdatedDateUTC' => '/Date(1756684800000+0000)/',
    ];

    $activePayload = array_merge($archivedPayload, ['ContactStatus' => 'ACTIVE']);
    $current = 'ACTIVE';

    Http::fake(function (Request $request) use ($archivedPayload, $activePayload, &$current) {
        if ($request->method() === 'POST' && str_ends_with($request->url(), '/Contacts')) {
            $current = $request->data()['Contacts'][0]['ContactStatus'] ?? 'ACTIVE';

            return Http::response(['Contacts' => [$current === 'ARCHIVED' ? $archivedPayload : $activePayload]], 200);
        }

        return Http::response(['Contacts' => [$current === 'ARCHIVED' ? $archivedPayload : $activePayload]], 200);
    });

    $this->actingAs($admin)->delete(route('admin.xero.customers.destroy', $contact))->assertRedirect();
    expect($contact->fresh()->xero_contact_status)->toBe('ARCHIVED');

    $this->actingAs($admin)->post(route('admin.xero.customers.restore', $contact))->assertRedirect();
    expect($contact->fresh()->xero_contact_status)->toBe('ACTIVE');
});

test('contact single sync upserts from webhook payload', function () {
    ['connection' => $connection, 'tenantA' => $tenant] = makeXeroCrudFixtures();

    Http::fake([
        'https://api.xero.com/api.xro/2.0/Contacts/contact-999' => Http::response(['Contacts' => [[
            'ContactID' => 'contact-999',
            'Name' => 'Webhook Co',
            'EmailAddress' => 'hook@example.com',
            'ContactStatus' => 'ACTIVE',
            'IsCustomer' => true,
            'Phones' => [['PhoneNumber' => '0400 000 000']],
            'UpdatedDateUTC' => '/Date(1756684800000+0000)/',
        ]]], 200),
    ]);

    $contact = app(XeroContactSyncService::class)->syncOne($connection, $tenant, 'contact-999');

    expect($contact->name)->toBe('Webhook Co')
        ->and(XeroContact::where('xero_contact_id', 'contact-999')->exists())->toBeTrue();
});

test('repeating invoice sync stores templates and links generated invoices', function () {
    ['connection' => $connection, 'tenantA' => $tenant] = makeXeroCrudFixtures();

    XeroInvoice::create([
        'xero_tenant_id' => $tenant->id,
        'xero_invoice_id' => 'inv-gen',
        'xero_invoice_number' => 'INV-G',
        'type' => 'ACCREC',
        'status' => 'AUTHORISED',
        'xero_contact_xero_id' => 'contact-111',
        'invoice_date' => '2026-09-01',
        'total' => 220.00,
        'amount_due' => 220.00,
    ]);

    Http::fake([
        'https://api.xero.com/api.xro/2.0/RepeatingInvoices' => Http::response(['RepeatingInvoices' => [[
            'RepeatingInvoiceID' => 'rep-1',
            'Type' => 'ACCREC',
            'Status' => 'AUTHORISED',
            'Contact' => ['ContactID' => 'contact-111'],
            'Schedule' => ['Period' => 1, 'Unit' => 'MONTHLY', 'DueDate' => 14, 'DueDateType' => 'DAYSAFTERBILLDATE', 'StartDate' => '/Date(1756684800000+0000)/', 'NextScheduledDate' => '/Date(1759276800000+0000)/'],
            'CurrencyCode' => 'AUD',
            'SubTotal' => 200.00,
            'TotalTax' => 20.00,
            'Total' => 220.00,
            'LineItems' => [['Description' => 'Retainer', 'Quantity' => 1, 'UnitAmount' => 200, 'AccountCode' => '200']],
            'Reference' => 'Retainer',
        ]]], 200),
    ]);

    $result = app(XeroRepeatingSyncService::class)->sync($connection, $tenant);

    expect($result['synced'])->toBe(1)
        ->and(XeroRepeatingInvoice::where('xero_repeating_invoice_id', 'rep-1')->exists())->toBeTrue()
        ->and(XeroInvoice::where('xero_invoice_id', 'inv-gen')->first()->xero_repeating_invoice_id)->not->toBeNull();
});

test('invoice single sync upserts line items', function () {
    ['connection' => $connection, 'tenantA' => $tenant] = makeXeroCrudFixtures();

    Http::fake([
        'https://api.xero.com/api.xro/2.0/Invoices/inv-webhook' => Http::response(['Invoices' => [xeroInvoicePayload('inv-webhook', 'contact-111', 'AUTHORISED')]], 200),
    ]);

    app(XeroInvoiceSyncService::class)->syncOne($connection, $tenant, 'inv-webhook');

    $invoice = XeroInvoice::where('xero_invoice_id', 'inv-webhook')->firstOrFail();
    expect($invoice->status)->toBe('AUTHORISED')
        ->and($invoice->line_items)->toHaveCount(1);
});

test('create and edit forms render with live lookups', function () {
    $admin = makeXeroCrudAdmin(['view-invoice', 'create-invoice', 'edit-invoice', 'view-contact', 'create-contact', 'view-repeating-invoice', 'create-repeating-invoice']);
    ['tenantA' => $tenant, 'contact' => $contact] = makeXeroCrudFixtures();

    Http::fake([
        'https://api.xero.com/api.xro/2.0/Accounts*' => Http::response(['Accounts' => [['AccountID' => 'a1', 'Code' => '200', 'Name' => 'Sales']]], 200),
        'https://api.xero.com/api.xro/2.0/TaxRates*' => Http::response(['TaxRates' => [['TaxType' => 'OUTPUT', 'Name' => 'GST on Income']]], 200),
        'https://api.xero.com/api.xro/2.0/BrandingThemes*' => Http::response(['BrandingThemes' => []], 200),
    ]);

    $invoice = XeroInvoice::create(['xero_tenant_id' => $tenant->id, 'xero_contact_id' => $contact->id, 'xero_invoice_id' => 'inv-form', 'xero_invoice_number' => 'INV-F', 'type' => 'ACCREC', 'status' => 'DRAFT', 'total' => 10, 'amount_due' => 10, 'line_items' => [['description' => "Bob's consulting", 'quantity' => 1, 'unit_amount' => 10, 'account_code' => '200']]]);

    $this->actingAs($admin)->get(route('admin.xero.invoices.create', ['tenant' => $tenant->id]))->assertOk()->assertSee('Line items', false);
    $this->actingAs($admin)->get(route('admin.xero.invoices.edit', $invoice))->assertOk()->assertSee('INV-F', false);
    $this->actingAs($admin)->get(route('admin.xero.invoices.show', $invoice))->assertOk()->assertSee('INV-F', false);
    $this->actingAs($admin)->get(route('admin.xero.customers.create', ['tenant' => $tenant->id]))->assertOk();
    $this->actingAs($admin)->get(route('admin.xero.customers.show', $contact))->assertOk()->assertSee('Acme', false);
    $this->actingAs($admin)->get(route('admin.xero.repeating-invoices.create', ['tenant' => $tenant->id]))->assertOk()->assertSee('Schedule', false);
    $this->actingAs($admin)->get(route('admin.xero.repeating-invoices.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.xero.customers.index'))->assertOk()->assertSee('Acme', false);
});
