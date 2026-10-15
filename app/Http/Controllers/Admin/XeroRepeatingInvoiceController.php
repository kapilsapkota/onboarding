<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesXeroTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Xero\StoreXeroRepeatingInvoiceRequest;
use App\Http\Requests\Admin\Xero\UpdateXeroRepeatingInvoiceRequest;
use App\Jobs\SyncXeroRepeatingInvoices;
use App\Models\XeroContact;
use App\Models\XeroRepeatingInvoice;
use App\Models\XeroTenant;
use App\Services\XeroRepeatingSyncService;
use App\Services\XeroService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Full CRUD over Xero repeating-invoice templates.
 *
 * Xero is the source of truth: every write goes to the API first
 * (with an Idempotency-Key), then the record is re-pulled via syncOne.
 *
 * API limits enforced:
 *   - Only DRAFT templates are editable.
 *   - Templates are removed via Status → DELETED (kept locally for history).
 */
class XeroRepeatingInvoiceController extends Controller
{
    use ResolvesXeroTenant;

    public function __construct(private readonly XeroService $xero) {}

    public function index(Request $request)
    {
        $this->authorizeAction('view-repeating-invoice');

        $tenants = $this->activeTenants();
        $tenant = null;

        if ($request->query('tenant')) {
            $tenant = $tenants->firstWhere('id', (int) $request->query('tenant'));

            if (! $tenant) {
                abort(404, 'Unknown Xero organisation.');
            }
        }

        $query = XeroRepeatingInvoice::query()->with(['tenant', 'xeroContact']);

        if ($tenant) {
            $query->where('xero_tenant_id', $tenant->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('xeroContact', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $templates = $query->orderByDesc('schedule_next_scheduled_date')->paginate(25)->withQueryString();

        $statsQuery = XeroRepeatingInvoice::query();
        if ($tenant) {
            $statsQuery->where('xero_tenant_id', $tenant->id);
        }

        $stats = [
            'authorised' => (clone $statsQuery)->where('status', 'AUTHORISED')->count(),
            'draft' => (clone $statsQuery)->where('status', 'DRAFT')->count(),
            'monthly_total' => (clone $statsQuery)->where('status', 'AUTHORISED')->sum('total'),
        ];

        return view('admin.xero.repeating.index', compact('templates', 'tenants', 'tenant', 'stats'));
    }

    public function show(XeroRepeatingInvoice $repeatingInvoice)
    {
        $this->authorizeAction('view-repeating-invoice');

        $repeatingInvoice->load(['tenant.connection', 'xeroContact', 'generatedInvoices' => fn ($q) => $q->orderByDesc('invoice_date')->limit(25)]);

        return view('admin.xero.repeating.show', ['template' => $repeatingInvoice]);
    }

    public function create(Request $request)
    {
        $this->authorizeAction('create-repeating-invoice');

        $tenants = $this->activeTenants();

        if ($tenants->isEmpty()) {
            return redirect()->route('admin.xero.index')->with('error', 'Connect Xero before creating repeating invoices.');
        }

        $tenant = $this->resolveTenant($request);
        $contacts = XeroContact::where('xero_tenant_id', $tenant->id)
            ->where('xero_contact_status', '!=', 'ARCHIVED')
            ->orderBy('name')
            ->get();

        $accounts = $this->lookup($tenant, 'accounts');
        $taxRates = $this->lookup($tenant, 'tax');

        return view('admin.xero.repeating.form', compact('tenants', 'tenant', 'contacts', 'accounts', 'taxRates'));
    }

    public function store(StoreXeroRepeatingInvoiceRequest $request, XeroRepeatingSyncService $sync)
    {
        $data = $request->validated();
        $tenant = $this->findTenant($data['xero_tenant_id']);

        $contact = XeroContact::where('xero_tenant_id', $tenant->id)->findOrFail($data['xero_contact_id']);

        $payload = [
            'Type' => $data['type'],
            'Contact' => ['ContactID' => $contact->xero_contact_id],
            'Status' => $data['status'],
            'Schedule' => array_filter([
                'Period' => $data['schedule_period'],
                'Unit' => $data['schedule_unit'],
                'DueDate' => $data['schedule_due_date'],
                'DueDateType' => $data['schedule_due_date_type'],
                'StartDate' => $data['schedule_start_date'],
                'EndDate' => $data['schedule_end_date'] ?? null,
            ], fn ($v) => $v !== null),
            'LineAmountTypes' => 'Exclusive',
            'LineItems' => collect($data['line_items'])->map(fn (array $line) => array_filter([
                'Description' => $line['description'],
                'Quantity' => $line['quantity'],
                'UnitAmount' => $line['unit_amount'],
                'AccountCode' => $line['account_code'],
                'TaxType' => $line['tax_type'] ?? null,
            ], fn ($v) => $v !== null))->all(),
        ];

        foreach (['reference' => 'Reference', 'branding_theme_id' => 'BrandingThemeID'] as $field => $xeroKey) {
            if (! empty($data[$field])) {
                $payload[$xeroKey] = $data[$field];
            }
        }

        try {
            $created = $this->xero->createRepeatingInvoice($tenant, $payload, (string) Str::uuid());
            $template = $sync->syncOne($tenant->connection, $tenant, $created['RepeatingInvoiceID']);
        } catch (\Throwable $e) {
            Log::error('Xero repeating invoice create failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Xero rejected the template: '.$e->getMessage());
        }

        Activity::record(
            description: "Created Xero repeating invoice for tenant {$tenant->tenant_name}",
            subject: $template,
            event: 'created',
            properties: ['tenant' => $tenant->tenant_name, 'status' => $template->status],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.repeating-invoices.show', $template)->with('success', 'Repeating invoice created in Xero.');
    }

    public function edit(XeroRepeatingInvoice $repeatingInvoice)
    {
        $this->authorizeAction('edit-repeating-invoice');

        $repeatingInvoice->load('tenant');

        if ($repeatingInvoice->status !== 'DRAFT') {
            abort(422, "Only draft templates can be edited in Xero (current: {$repeatingInvoice->status}).");
        }

        $tenants = $this->activeTenants();
        $contacts = XeroContact::where('xero_tenant_id', $repeatingInvoice->xero_tenant_id)
            ->where('xero_contact_status', '!=', 'ARCHIVED')
            ->orderBy('name')
            ->get();

        $accounts = $this->lookup($repeatingInvoice->tenant, 'accounts');
        $taxRates = $this->lookup($repeatingInvoice->tenant, 'tax');

        return view('admin.xero.repeating.form', [
            'template' => $repeatingInvoice,
            'tenants' => $tenants,
            'tenant' => $repeatingInvoice->tenant,
            'contacts' => $contacts,
            'accounts' => $accounts,
            'taxRates' => $taxRates,
        ]);
    }

    public function update(UpdateXeroRepeatingInvoiceRequest $request, XeroRepeatingInvoice $repeatingInvoice, XeroRepeatingSyncService $sync)
    {
        $repeatingInvoice->load('tenant');

        if ($repeatingInvoice->status !== 'DRAFT') {
            return back()->with('error', "Only draft templates can be edited in Xero (current: {$repeatingInvoice->status}).");
        }

        $data = $request->validated();
        $tenant = $repeatingInvoice->tenant;

        $payload = [];

        if (array_key_exists('xero_contact_id', $data)) {
            $contact = XeroContact::where('xero_tenant_id', $tenant->id)->findOrFail($data['xero_contact_id']);
            $payload['Contact'] = ['ContactID' => $contact->xero_contact_id];
        }

        $scheduleMap = ['schedule_period' => 'Period', 'schedule_unit' => 'Unit', 'schedule_due_date' => 'DueDate', 'schedule_due_date_type' => 'DueDateType', 'schedule_start_date' => 'StartDate', 'schedule_end_date' => 'EndDate'];
        $schedule = [];

        foreach ($scheduleMap as $field => $xeroKey) {
            if (array_key_exists($field, $data)) {
                $schedule[$xeroKey] = $data[$field];
            }
        }

        if ($schedule !== []) {
            $payload['Schedule'] = $schedule;
        }

        foreach (['status' => 'Status', 'reference' => 'Reference', 'branding_theme_id' => 'BrandingThemeID'] as $field => $xeroKey) {
            if (array_key_exists($field, $data)) {
                $payload[$xeroKey] = $data[$field];
            }
        }

        if (array_key_exists('line_items', $data)) {
            $payload['LineItems'] = collect($data['line_items'])->map(fn (array $line) => array_filter([
                'Description' => $line['description'],
                'Quantity' => $line['quantity'],
                'UnitAmount' => $line['unit_amount'],
                'AccountCode' => $line['account_code'],
                'TaxType' => $line['tax_type'] ?? null,
            ], fn ($v) => $v !== null))->all();
        }

        try {
            $this->xero->updateRepeatingInvoice($tenant, $repeatingInvoice->xero_repeating_invoice_id, $payload);
            $template = $sync->syncOne($tenant->connection, $tenant, $repeatingInvoice->xero_repeating_invoice_id);
        } catch (\Throwable $e) {
            Log::error('Xero repeating invoice update failed', ['id' => $repeatingInvoice->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Xero rejected the update: '.$e->getMessage());
        }

        Activity::record(
            description: 'Updated Xero repeating invoice template',
            subject: $template,
            event: 'updated',
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.repeating-invoices.show', $template)->with('success', 'Repeating invoice updated in Xero.');
    }

    /**
     * Delete a template (Status → DELETED). The local row is kept with its
     * terminal status so generated-invoice history stays intact.
     */
    public function destroy(XeroRepeatingInvoice $repeatingInvoice, XeroRepeatingSyncService $sync)
    {
        $this->authorizeAction('delete-repeating-invoice');

        $repeatingInvoice->load('tenant');
        $tenant = $repeatingInvoice->tenant;

        if ($repeatingInvoice->status === 'DELETED') {
            return back()->with('error', 'This template is already deleted.');
        }

        try {
            $this->xero->deleteRepeatingInvoice($tenant, $repeatingInvoice->xero_repeating_invoice_id);
            $template = $sync->syncOne($tenant->connection, $tenant, $repeatingInvoice->xero_repeating_invoice_id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xero rejected the delete: '.$e->getMessage());
        }

        Activity::record(
            description: "Deleted Xero repeating invoice for tenant {$tenant->tenant_name}",
            subject: $template,
            event: 'deleted',
            properties: ['tenant' => $tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.repeating-invoices.show', $template)->with('success', 'Repeating invoice deleted in Xero.');
    }

    public function authorise(XeroRepeatingInvoice $repeatingInvoice, XeroRepeatingSyncService $sync)
    {
        $this->authorizeAction('edit-repeating-invoice');

        $repeatingInvoice->load('tenant');

        if ($repeatingInvoice->status !== 'DRAFT') {
            return back()->with('error', "Only draft templates can be authorised (current: {$repeatingInvoice->status}).");
        }

        try {
            $this->xero->updateRepeatingInvoice($repeatingInvoice->tenant, $repeatingInvoice->xero_repeating_invoice_id, ['Status' => 'AUTHORISED']);
            $template = $sync->syncOne($repeatingInvoice->tenant->connection, $repeatingInvoice->tenant, $repeatingInvoice->xero_repeating_invoice_id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xero rejected the authorisation: '.$e->getMessage());
        }

        Activity::record(
            description: 'Authorised Xero repeating invoice template',
            subject: $template,
            event: 'authorised',
            properties: ['tenant' => $repeatingInvoice->tenant->tenant_name],
            logName: 'xero',
        );

        return redirect()->route('admin.xero.repeating-invoices.show', $template)->with('success', 'Repeating invoice authorised in Xero.');
    }

    public function sync(Request $request, XeroTenant $tenant)
    {
        $this->authorizeAction('sync-repeating-invoice');

        SyncXeroRepeatingInvoices::dispatch($tenant->connection->id, $tenant->id, Activity::getBatchUuid());

        return back()->with('success', "Repeating invoice sync queued for {$tenant->tenant_name}.");
    }

    public function syncAll(Request $request)
    {
        $this->authorizeAction('sync-repeating-invoice');

        $tenants = XeroTenant::with('connection')->where('is_active', true)->get();

        if ($tenants->isEmpty()) {
            return back()->with('error', 'No active Xero organisations to sync.');
        }

        foreach ($tenants as $index => $tenant) {
            if (! $tenant->connection) {
                continue;
            }

            SyncXeroRepeatingInvoices::dispatch($tenant->connection->id, $tenant->id, Activity::getBatchUuid())
                ->delay(now()->addSeconds($index * 5));
        }

        Activity::record(
            description: 'Queued Xero repeating invoice sync across all tenants',
            event: 'sync',
            properties: ['tenants' => $tenants->count()],
            logName: 'xero',
        );

        return back()->with('success', "Repeating invoice sync queued for {$tenants->count()} organisation(s).");
    }

    // -------------------------------------------------------------------------

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    private function lookup(XeroTenant $tenant, string $kind): array
    {
        try {
            return match ($kind) {
                'accounts' => $this->xero->getSalesAccounts($tenant),
                'tax' => $this->xero->getTaxRates($tenant),
            };
        } catch (\Throwable $e) {
            Log::warning('Xero form lookup failed', ['tenant_id' => $tenant->id, 'kind' => $kind, 'error' => $e->getMessage()]);

            return [];
        }
    }
}
