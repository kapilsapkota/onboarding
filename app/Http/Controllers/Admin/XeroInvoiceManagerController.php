<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesXeroTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Xero\StoreXeroInvoiceRequest;
use App\Http\Requests\Admin\Xero\UpdateXeroInvoiceRequest;
use App\Jobs\SyncTenantInvoiceJob;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Services\XeroInvoiceSyncService;
use App\Services\XeroService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Full CRUD over Xero sales invoices / bills, backed by the local mirror.
 *
 * Xero is the source of truth: every write goes to the API first
 * (with an Idempotency-Key), then the record is re-pulled via syncOne
 * so local state can never drift from Xero.
 *
 * API limits enforced (https://developer.xero.com/documentation/api/accounting/invoices):
 *   - Only DRAFT / SUBMITTED invoices are editable.
 *   - DRAFT / SUBMITTED → DELETED (soft delete, kept for history).
 *   - AUTHORISED → VOIDED (cannot be deleted, cannot be edited).
 *   - PAID / VOIDED / DELETED are read-only.
 */
class XeroInvoiceManagerController extends Controller
{
    use ResolvesXeroTenant;

    public function __construct(private readonly XeroService $xero) {}

    public function index(Request $request)
    {
        $this->authorizeAction('view-invoice');

        $tenants = $this->activeTenants();
        $tenant = null;

        if ($request->query('tenant')) {
            $tenant = $tenants->firstWhere('id', (int) $request->query('tenant'));

            if (! $tenant) {
                abort(404, 'Unknown Xero organisation.');
            }
        }

        $query = XeroInvoice::query()->with(['tenant', 'xeroContact', 'client']);

        if ($tenant) {
            $query->where('xero_tenant_id', $tenant->id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('xero_invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('xeroContact', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderByDesc('invoice_date')->orderByDesc('id')->paginate(25)->withQueryString();

        $statsQuery = XeroInvoice::query();
        if ($tenant) {
            $statsQuery->where('xero_tenant_id', $tenant->id);
        }

        $stats = [
            'outstanding' => (clone $statsQuery)->where('amount_due', '>', 0)->whereNotIn('status', ['VOIDED', 'DELETED'])->sum('amount_due'),
            'overdue' => (clone $statsQuery)->overdue()->count(),
            'draft' => (clone $statsQuery)->where('status', 'DRAFT')->count(),
            'authorised' => (clone $statsQuery)->where('status', 'AUTHORISED')->count(),
        ];

        return view('admin.xero.invoices.index', compact('invoices', 'tenants', 'tenant', 'stats'));
    }

    public function show(XeroInvoice $invoice)
    {
        $this->authorizeAction('view-invoice');

        $invoice->load(['tenant.connection', 'xeroContact', 'client', 'repeatingInvoice', 'latestDirectDebitPayment']);

        return view('admin.xero.invoices.show', compact('invoice'));
    }

    public function create(Request $request)
    {
        $this->authorizeAction('create-invoice');

        $tenants = $this->activeTenants();

        if ($tenants->isEmpty()) {
            return redirect()->route('admin.xero.index')->with('error', 'Connect Xero before creating invoices.');
        }

        $tenant = $this->resolveTenant($request);
        $contacts = XeroContact::where('xero_tenant_id', $tenant->id)
            ->where('xero_contact_status', '!=', 'ARCHIVED')
            ->orderBy('name')
            ->get();

        $accounts = $this->lookup($tenant, 'accounts');
        $taxRates = $this->lookup($tenant, 'tax');
        $themes = $this->lookup($tenant, 'themes');

        return view('admin.xero.invoices.form', compact('tenants', 'tenant', 'contacts', 'accounts', 'taxRates', 'themes'));
    }

    public function store(StoreXeroInvoiceRequest $request, XeroInvoiceSyncService $sync)
    {
        $data = $request->validated();
        $tenant = $this->findTenant($data['xero_tenant_id']);

        $contact = XeroContact::where('xero_tenant_id', $tenant->id)->findOrFail($data['xero_contact_id']);

        $payload = [
            'Type' => $data['type'],
            'Contact' => ['ContactID' => $contact->xero_contact_id],
            'Date' => $data['invoice_date'],
            'Status' => $data['status'],
            'LineAmountTypes' => 'Exclusive',
            'LineItems' => collect($data['line_items'])->map(fn (array $line) => array_filter([
                'Description' => $line['description'],
                'Quantity' => $line['quantity'],
                'UnitAmount' => $line['unit_amount'],
                'AccountCode' => $line['account_code'],
                'TaxType' => $line['tax_type'] ?? null,
                'DiscountRate' => $line['discount_rate'] ?? null,
            ], fn ($v) => $v !== null))->all(),
        ];

        foreach (['due_date' => 'DueDate', 'reference' => 'Reference', 'currency_code' => 'CurrencyCode', 'branding_theme_id' => 'BrandingThemeID'] as $field => $xeroKey) {
            if (! empty($data[$field])) {
                $payload[$xeroKey] = $data[$field];
            }
        }

        try {
            $created = $this->xero->createInvoice($tenant, $payload, (string) Str::uuid());
            $invoice = $sync->syncOne($tenant->connection, $tenant, $created['InvoiceID']);
        } catch (\Throwable $e) {
            Log::error('Xero invoice create failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Xero rejected the invoice: '.$e->getMessage());
        }

        Activity::record(
            description: "Created Xero invoice {$invoice->display_number} for tenant {$tenant->tenant_name}",
            subject: $invoice,
            event: 'created',
            properties: ['tenant' => $tenant->tenant_name, 'status' => $invoice->status],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.invoices.show', $invoice)->with('success', "Invoice {$invoice->display_number} created in Xero.");
    }

    public function edit(XeroInvoice $invoice)
    {
        $this->authorizeAction('edit-invoice');

        $invoice->load(['tenant', 'xeroContact']);
        $this->ensureEditable($invoice);

        $tenants = $this->activeTenants();
        $contacts = XeroContact::where('xero_tenant_id', $invoice->xero_tenant_id)
            ->where('xero_contact_status', '!=', 'ARCHIVED')
            ->orderBy('name')
            ->get();

        $accounts = $this->lookup($invoice->tenant, 'accounts');
        $taxRates = $this->lookup($invoice->tenant, 'tax');
        $themes = $this->lookup($invoice->tenant, 'themes');

        return view('admin.xero.invoices.form', [
            'invoice' => $invoice,
            'tenants' => $tenants,
            'tenant' => $invoice->tenant,
            'contacts' => $contacts,
            'accounts' => $accounts,
            'taxRates' => $taxRates,
            'themes' => $themes,
        ]);
    }

    public function update(UpdateXeroInvoiceRequest $request, XeroInvoice $invoice, XeroInvoiceSyncService $sync)
    {
        $invoice->load('tenant');
        $this->ensureEditable($invoice);

        $data = $request->validated();
        $tenant = $invoice->tenant;

        $payload = [];

        if (array_key_exists('xero_contact_id', $data)) {
            $contact = XeroContact::where('xero_tenant_id', $tenant->id)->findOrFail($data['xero_contact_id']);
            $payload['Contact'] = ['ContactID' => $contact->xero_contact_id];
        }

        foreach (['status' => 'Status', 'invoice_date' => 'Date', 'due_date' => 'DueDate', 'reference' => 'Reference', 'branding_theme_id' => 'BrandingThemeID'] as $field => $xeroKey) {
            if (array_key_exists($field, $data)) {
                $payload[$xeroKey] = $data[$field];
            }
        }

        if (array_key_exists('line_items', $data)) {
            $payload['LineItems'] = collect($data['line_items'])->map(fn (array $line) => array_filter([
                'LineItemID' => $line['line_item_id'] ?? null,
                'Description' => $line['description'],
                'Quantity' => $line['quantity'],
                'UnitAmount' => $line['unit_amount'],
                'AccountCode' => $line['account_code'],
                'TaxType' => $line['tax_type'] ?? null,
                'DiscountRate' => $line['discount_rate'] ?? null,
            ], fn ($v) => $v !== null))->all();
        }

        // Guard the Xero status machine: AUTHORISED invoices only accept AUTHORISED.
        if (isset($payload['Status']) && ! $this->validTransition($invoice->status, $payload['Status'])) {
            return back()->withInput()->with('error', "Xero does not allow {$invoice->status} → {$payload['Status']}.");
        }

        try {
            $this->xero->updateInvoice($tenant, $invoice->xero_invoice_id, $payload);
            $invoice = $sync->syncOne($tenant->connection, $tenant, $invoice->xero_invoice_id);
        } catch (\Throwable $e) {
            Log::error('Xero invoice update failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Xero rejected the update: '.$e->getMessage());
        }

        Activity::record(
            description: "Updated Xero invoice {$invoice->display_number}",
            subject: $invoice,
            event: 'updated',
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.invoices.show', $invoice)->with('success', "Invoice {$invoice->display_number} updated in Xero.");
    }

    /**
     * Delete (DRAFT/SUBMITTED → DELETED) or void (AUTHORISED → VOIDED).
     * Xero has no hard delete for invoices — the record is kept locally
     * with its terminal status so history stays intact.
     */
    public function destroy(XeroInvoice $invoice, XeroInvoiceSyncService $sync)
    {
        $this->authorizeAction('delete-invoice');

        $invoice->load('tenant');
        $tenant = $invoice->tenant;

        try {
            if (in_array($invoice->status, ['DRAFT', 'SUBMITTED'], true)) {
                $this->xero->deleteInvoice($tenant, $invoice->xero_invoice_id, $invoice->xero_invoice_number);
                $message = "Invoice {$invoice->display_number} deleted in Xero.";
                $event = 'deleted';
            } elseif ($invoice->status === 'AUTHORISED') {
                $this->xero->voidInvoice($tenant, $invoice->xero_invoice_id, $invoice->xero_invoice_number);
                $message = "Invoice {$invoice->display_number} voided in Xero.";
                $event = 'voided';
            } else {
                return back()->with('error', "Invoices with status {$invoice->status} cannot be deleted or voided in Xero.");
            }

            $invoice = $sync->syncOne($tenant->connection, $tenant, $invoice->xero_invoice_id);
        } catch (\Throwable $e) {
            Log::error('Xero invoice delete/void failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Xero rejected the request: '.$e->getMessage());
        }

        Activity::record(
            description: "Xero invoice {$invoice->display_number} {$event} for tenant {$tenant->tenant_name}",
            subject: $invoice,
            event: $event,
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.invoices.show', $invoice)->with('success', $message);
    }

    public function authorise(XeroInvoice $invoice, XeroInvoiceSyncService $sync)
    {
        $this->authorizeAction('edit-invoice');

        $invoice->load('tenant');

        if (! in_array($invoice->status, ['DRAFT', 'SUBMITTED'], true)) {
            return back()->with('error', "Only draft or submitted invoices can be authorised (current: {$invoice->status}).");
        }

        try {
            $this->xero->authoriseInvoice($invoice->tenant, $invoice->xero_invoice_id);
            $invoice = $sync->syncOne($invoice->tenant->connection, $invoice->tenant, $invoice->xero_invoice_id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xero rejected the authorisation: '.$e->getMessage());
        }

        Activity::record(
            description: "Authorised Xero invoice {$invoice->display_number}",
            subject: $invoice,
            event: 'authorised',
            properties: ['tenant' => $invoice->tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.invoices.show', $invoice)->with('success', "Invoice {$invoice->display_number} authorised in Xero.");
    }

    public function sync(Request $request, XeroTenant $tenant)
    {
        $this->authorizeAction('sync-invoice');

        SyncTenantInvoiceJob::dispatch(
            connectionId: $tenant->connection->id,
            tenantId: $tenant->id,
            invoiceId: null,
            modifiedAfter: null,
            fullResync: false,
            activityBatch: Activity::getBatchUuid(),
        );

        return back()->with('success', "Invoice sync queued for {$tenant->tenant_name}.");
    }

    public function syncAll(Request $request)
    {
        $this->authorizeAction('sync-invoice');

        $tenants = XeroTenant::with('connection')->where('is_active', true)->get();

        if ($tenants->isEmpty()) {
            return back()->with('error', 'No active Xero organisations to sync.');
        }

        foreach ($tenants as $index => $tenant) {
            if (! $tenant->connection) {
                continue;
            }

            SyncTenantInvoiceJob::dispatch(
                connectionId: $tenant->connection->id,
                tenantId: $tenant->id,
                invoiceId: null,
                modifiedAfter: $tenant->last_invoice_synced_at?->toIso8601String(),
                fullResync: false,
                activityBatch: Activity::getBatchUuid(),
            )->delay(now()->addSeconds($index * 5));
        }

        Activity::record(
            description: 'Queued Xero invoice sync across all tenants',
            event: 'sync',
            properties: ['tenants' => $tenants->count()],
            logName: 'xero',
        );

        return back()->with('success', "Invoice sync queued for {$tenants->count()} organisation(s).");
    }

    // -------------------------------------------------------------------------

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    private function ensureEditable(XeroInvoice $invoice): void
    {
        if (! in_array($invoice->status, ['DRAFT', 'SUBMITTED'], true)) {
            abort(422, "Only draft or submitted invoices can be edited in Xero (current: {$invoice->status}).");
        }
    }

    private function validTransition(string $from, string $to): bool
    {
        return match ($from) {
            'DRAFT', 'SUBMITTED' => in_array($to, ['DRAFT', 'SUBMITTED', 'AUTHORISED'], true),
            'AUTHORISED' => $to === 'AUTHORISED',
            default => false,
        };
    }

    /**
     * Best-effort lookup for form pickers — never breaks the form when
     * Xero is unreachable; falls back to an empty list.
     */
    private function lookup(XeroTenant $tenant, string $kind): array
    {
        try {
            return match ($kind) {
                'accounts' => $this->xero->getSalesAccounts($tenant),
                'tax' => $this->xero->getTaxRates($tenant),
                'themes' => $this->xero->getBrandingThemes($tenant),
            };
        } catch (\Throwable $e) {
            Log::warning('Xero form lookup failed', ['tenant_id' => $tenant->id, 'kind' => $kind, 'error' => $e->getMessage()]);

            return [];
        }
    }
}
