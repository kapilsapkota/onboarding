<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesXeroTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Xero\StoreXeroContactRequest;
use App\Http\Requests\Admin\Xero\UpdateXeroContactRequest;
use App\Jobs\SyncXeroTenantContacts;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Services\XeroContactSyncService;
use App\Services\XeroService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Full CRUD over Xero contacts ("Customers" in the sidebar).
 *
 * Xero is the source of truth: every write goes to the API first
 * (with an Idempotency-Key), then the record is re-pulled via syncOne.
 *
 * API limits enforced (https://developer.xero.com/documentation/api/accounting/contacts):
 *   - Contacts cannot be hard-deleted — archive sets ContactStatus=ARCHIVED.
 *   - Local client links (is_matched) survive archive/restore untouched.
 */
class XeroCustomerController extends Controller
{
    use ResolvesXeroTenant;

    public function __construct(private readonly XeroService $xero) {}

    public function index(Request $request)
    {
        $this->authorizeAction('view-contact');

        $tenants = $this->activeTenants();
        $tenant = null;

        if ($request->query('tenant')) {
            $tenant = $tenants->firstWhere('id', (int) $request->query('tenant'));

            if (! $tenant) {
                abort(404, 'Unknown Xero organisation.');
            }
        }

        $query = XeroContact::query()->with(['tenant', 'client']);

        if ($tenant) {
            $query->where('xero_tenant_id', $tenant->id);
        }

        match ($request->query('state', 'active')) {
            'archived' => $query->where('xero_contact_status', 'ARCHIVED'),
            'all' => null,
            default => $query->where(fn ($q) => $q->whereNull('xero_contact_status')->orWhere('xero_contact_status', '!=', 'ARCHIVED')),
        };

        if ($request->filled('kind')) {
            $query->when($request->query('kind') === 'customer', fn ($q) => $q->where('is_customer', true))
                ->when($request->query('kind') === 'supplier', fn ($q) => $q->where('is_supplier', true));
        }

        if ($request->filled('matched')) {
            $query->when($request->query('matched') === 'yes', fn ($q) => $q->where('is_matched', true))
                ->when($request->query('matched') === 'no', fn ($q) => $q->where('is_matched', false));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('xero_contact_number', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('name')->paginate(25)->withQueryString();

        $statsQuery = XeroContact::query();
        if ($tenant) {
            $statsQuery->where('xero_tenant_id', $tenant->id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'customers' => (clone $statsQuery)->where('is_customer', true)->count(),
            'suppliers' => (clone $statsQuery)->where('is_supplier', true)->count(),
            'matched' => (clone $statsQuery)->where('is_matched', true)->count(),
        ];

        return view('admin.xero.customers.index', compact('contacts', 'tenants', 'tenant', 'stats'));
    }

    public function show(XeroContact $customer)
    {
        $this->authorizeAction('view-contact');

        $customer->load(['tenant.connection', 'client']);

        $invoices = $customer->tenant
            ? XeroInvoice::where('xero_tenant_id', $customer->xero_tenant_id)
                ->where('xero_contact_xero_id', $customer->xero_contact_id)
                ->orderByDesc('invoice_date')
                ->limit(25)
                ->get()
            : collect();

        return view('admin.xero.customers.show', compact('customer', 'invoices'));
    }

    public function create(Request $request)
    {
        $this->authorizeAction('create-contact');

        $tenants = $this->activeTenants();

        if ($tenants->isEmpty()) {
            return redirect()->route('admin.xero.index')->with('error', 'Connect Xero before creating customers.');
        }

        $tenant = $this->resolveTenant($request);

        return view('admin.xero.customers.form', compact('tenants', 'tenant'));
    }

    public function store(StoreXeroContactRequest $request, XeroContactSyncService $sync)
    {
        $data = $request->validated();
        $tenant = $this->findTenant($data['xero_tenant_id']);

        $payload = array_filter([
            'Name' => $data['name'],
            'FirstName' => $data['first_name'] ?? null,
            'LastName' => $data['last_name'] ?? null,
            'EmailAddress' => $data['email'] ?? null,
            'BankAccountDetails' => $data['bank_account_details'] ?? null,
            'TaxNumber' => $data['tax_number'] ?? null,
            'AccountNumber' => $data['account_number'] ?? null,
            'ContactNumber' => $data['contact_number'] ?? null,
            'DefaultCurrency' => $data['default_currency'] ?? null,
            'IsCustomer' => $data['is_customer'] ?? true,
            'IsSupplier' => $data['is_supplier'] ?? false,
        ], fn ($v) => $v !== null);

        if (! empty($data['phone'])) {
            $payload['Phones'] = [['PhoneType' => 'DEFAULT', 'PhoneNumber' => $data['phone']]];
        }

        try {
            $created = $this->xero->createContactForTenant($tenant, $payload, (string) Str::uuid());
            $customer = $sync->syncOne($tenant->connection, $tenant, $created['ContactID']);
        } catch (\Throwable $e) {
            Log::error('Xero contact create failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Xero rejected the customer: '.$e->getMessage());
        }

        Activity::record(
            description: "Created Xero customer {$customer->name} for tenant {$tenant->tenant_name}",
            subject: $customer,
            event: 'created',
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.customers.show', $customer)->with('success', "Customer {$customer->name} created in Xero.");
    }

    public function edit(XeroContact $customer)
    {
        $this->authorizeAction('edit-contact');

        $customer->load('tenant');
        $tenants = $this->activeTenants();

        return view('admin.xero.customers.form', [
            'customer' => $customer,
            'tenants' => $tenants,
            'tenant' => $customer->tenant,
        ]);
    }

    public function update(UpdateXeroContactRequest $request, XeroContact $customer, XeroContactSyncService $sync)
    {
        $customer->load('tenant');

        $data = $request->validated();
        $tenant = $customer->tenant;

        $payload = [];

        foreach (['name' => 'Name', 'first_name' => 'FirstName', 'last_name' => 'LastName', 'email' => 'EmailAddress', 'bank_account_details' => 'BankAccountDetails', 'tax_number' => 'TaxNumber', 'account_number' => 'AccountNumber', 'contact_number' => 'ContactNumber', 'default_currency' => 'DefaultCurrency'] as $field => $xeroKey) {
            if (array_key_exists($field, $data)) {
                $payload[$xeroKey] = $data[$field];
            }
        }

        foreach (['is_customer' => 'IsCustomer', 'is_supplier' => 'IsSupplier'] as $field => $xeroKey) {
            if (array_key_exists($field, $data)) {
                $payload[$xeroKey] = (bool) $data[$field];
            }
        }

        if (array_key_exists('phone', $data)) {
            $payload['Phones'] = $data['phone']
                ? [['PhoneType' => 'DEFAULT', 'PhoneNumber' => $data['phone']]]
                : [];
        }

        try {
            $this->xero->updateContactForTenant($tenant, $customer->xero_contact_id, $payload);
            $customer = $sync->syncOne($tenant->connection, $tenant, $customer->xero_contact_id);
        } catch (\Throwable $e) {
            Log::error('Xero contact update failed', ['contact_id' => $customer->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Xero rejected the update: '.$e->getMessage());
        }

        Activity::record(
            description: "Updated Xero customer {$customer->name}",
            subject: $customer,
            event: 'updated',
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.customers.show', $customer)->with('success', "Customer {$customer->name} updated in Xero.");
    }

    /**
     * Archive the contact in Xero (no hard delete exists in the API).
     * The local row is kept with status ARCHIVED so history and the
     * client link stay intact.
     */
    public function destroy(XeroContact $customer, XeroContactSyncService $sync)
    {
        $this->authorizeAction('delete-contact');

        $customer->load('tenant');
        $tenant = $customer->tenant;

        if ($customer->xero_contact_status === 'ARCHIVED') {
            return back()->with('error', 'This customer is already archived in Xero.');
        }

        try {
            $this->xero->archiveContact($tenant, $customer->xero_contact_id);
            $customer = $sync->syncOne($tenant->connection, $tenant, $customer->xero_contact_id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xero rejected the archive: '.$e->getMessage());
        }

        Activity::record(
            description: "Archived Xero customer {$customer->name}",
            subject: $customer,
            event: 'archived',
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.customers.show', $customer)->with('success', "Customer {$customer->name} archived in Xero.");
    }

    public function restore(XeroContact $customer, XeroContactSyncService $sync)
    {
        $this->authorizeAction('edit-contact');

        $customer->load('tenant');

        if ($customer->xero_contact_status !== 'ARCHIVED') {
            return back()->with('error', 'Only archived customers can be restored.');
        }

        try {
            $this->xero->restoreContact($customer->tenant, $customer->xero_contact_id);
            $customer = $sync->syncOne($customer->tenant->connection, $customer->tenant, $customer->xero_contact_id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xero rejected the restore: '.$e->getMessage());
        }

        Activity::record(
            description: "Restored Xero customer {$customer->name}",
            subject: $customer,
            event: 'restored',
            properties: ['tenant' => $customer->tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.customers.show', $customer)->with('success', "Customer {$customer->name} restored in Xero.");
    }

    public function sync(Request $request, XeroTenant $tenant)
    {
        $this->authorizeAction('sync-xero-contacts');

        SyncXeroTenantContacts::dispatch(
            $tenant->connection->id,
            $tenant->id,
            null,
            Activity::getBatchUuid(),
        );

        return back()->with('success', "Customer sync queued for {$tenant->tenant_name}.");
    }

    public function syncAll(Request $request)
    {
        $this->authorizeAction('sync-xero-contacts');

        $tenants = XeroTenant::with('connection')->where('is_active', true)->get();

        if ($tenants->isEmpty()) {
            return back()->with('error', 'No active Xero organisations to sync.');
        }

        foreach ($tenants as $index => $tenant) {
            if (! $tenant->connection) {
                continue;
            }

            SyncXeroTenantContacts::dispatch(
                $tenant->connection->id,
                $tenant->id,
                null,
                Activity::getBatchUuid(),
            )->delay(now()->addSeconds($index * 5));
        }

        Activity::record(
            description: 'Queued Xero customer sync across all tenants',
            event: 'sync',
            properties: ['tenants' => $tenants->count()],
            logName: 'xero',
        );

        return back()->with('success', "Customer sync queued for {$tenants->count()} organisation(s).");
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
