<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\XeroContact;
use App\Models\XeroTenant;
use App\Services\XeroMatchService;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Global customer reconciliation across every Xero organisation.
 *
 * The tenant is the source of truth: this page rolls up all unmatched
 * Xero contacts, pairs each with its best client suggestion, and lets an
 * admin confirm matches in bulk or auto-link exact (email/ABN) matches.
 * Individual assignment still flows through XeroContactController's
 * JSON endpoints so both UIs stay in sync.
 */
class XeroReconcileController extends Controller
{
    public function __construct(private readonly XeroMatchService $matcher) {}

    public function index(Request $request): View
    {
        $this->authorizeAction('view-contact');

        $companyId = $request->query('company') ? (int) $request->query('company') : null;
        $stripeAccountId = $request->query('stripe_account') ? (int) $request->query('stripe_account') : null;

        $companies = Company::orderBy('name')->get(['id', 'name']);
        $stripeAccounts = StripeAccount::with('company:id,name')->orderBy('display_name')->get();

        $company = $companyId ? $companies->firstWhere('id', $companyId) : null;
        $stripeAccount = $stripeAccountId ? $stripeAccounts->firstWhere('id', $stripeAccountId) : null;

        if (($companyId && ! $company) || ($stripeAccountId && ! $stripeAccount)) {
            abort(404, 'Unknown company or Stripe account.');
        }

        $tenants = XeroTenant::where('is_active', true)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('tenant_name')
            ->get();
        $tenant = null;

        if ($request->query('tenant')) {
            $tenant = $tenants->firstWhere('id', (int) $request->query('tenant'));

            if (! $tenant) {
                abort(404, 'Unknown Xero organisation.');
            }
        }

        $base = XeroContact::query()->with(['tenant', 'client']);

        $scopedTenantIds = $this->scopedTenantIds($tenant?->id, $companyId, $stripeAccountId);

        if ($scopedTenantIds !== null) {
            $base->whereIn('xero_tenant_id', $scopedTenantIds);
        }

        $stats = [
            'total' => (clone $base)->count(),
            'matched' => (clone $base)->where('is_matched', true)->count(),
            'unmatched' => (clone $base)->where('is_matched', false)->count(),
        ];

        $query = (clone $base)->where('is_matched', false);

        if ($request->query('filter', 'needs-review') === 'all-unmatched') {
            // No extra constraint: every unmatched contact.
        } else {
            $query->where(fn ($q) => $q->whereNull('xero_contact_status')->orWhere('xero_contact_status', '!=', 'ARCHIVED'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $unmatched = $query->orderBy('name')->paginate(25)->withQueryString();

        [$clients, $clientsScoped, $clientScopeNote] = $this->clientsForScope($tenant, $companyId, $stripeAccountId, $scopedTenantIds);

        $suggestions = [];
        foreach ($unmatched as $contact) {
            $suggestions[$contact->id] = $this->matcher->matchContact($this->matcher->toPayload($contact), $clients);
        }

        $stats['auto_ready'] = collect($suggestions)->filter(
            fn ($match) => $match['customer'] && $match['score'] >= 95 && in_array($match['method'], ['email', 'abn'], true)
        )->count();

        return view('admin.xero.reconcile.index', compact('tenants', 'tenant', 'companies', 'company', 'stripeAccounts', 'stripeAccount', 'stats', 'unmatched', 'clients', 'clientsScoped', 'clientScopeNote', 'suggestions'));
    }

    public function autoMatch(Request $request): RedirectResponse
    {
        $this->authorizeAction('edit-contact');

        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:xero_tenants,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'stripe_account_id' => ['nullable', 'integer', 'exists:stripe_accounts,id'],
        ]);

        $tenantIds = $this->scopedTenantIds(
            $data['tenant_id'] ?? null,
            $data['company_id'] ?? null,
            $data['stripe_account_id'] ?? null,
        );

        $result = $this->matcher->autoMatchUnmatched(null, 95, $tenantIds);

        $scope = $this->scopeLabel($data);

        Activity::record(
            description: "Auto-matched {$result['linked']} Xero contacts{$scope}",
            event: 'auto-match',
            properties: $result + [
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'stripe_account_id' => $data['stripe_account_id'] ?? null,
            ],
            logName: 'xero',
        );

        return back()->with(
            'success',
            "Auto-matched {$result['linked']} contact(s){$scope} ({$result['suggested']} still need review, {$result['skipped']} with no suggestion)."
        );
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    /**
     * Client dropdown pool for the selected scope.
     *
     * When an organisation, company or Stripe account is filtered, only
     * that scope's customers are offered: clients of the scoped companies
     * plus clients linked to the scoped Stripe accounts' customers. This
     * keeps the mapping target obvious (our Stripe/client record) without
     * touching the Xero contact itself. With no usable link (e.g. an
     * unlinked organisation on its own) every client is shown instead so
     * matching is never blocked.
     *
     * @param  int[]|null  $scopedTenantIds
     * @return array{0: Collection<int, Client>, 1: bool, 2: string}
     */
    private function clientsForScope(?XeroTenant $tenant, ?int $companyId, ?int $stripeAccountId, ?array $scopedTenantIds): array
    {
        $base = Client::with('company:id,name')->orderBy('company_name');
        $columns = ['id', 'company_name', 'billing_email', 'stripe_customer_id', 'company_id'];

        if (! $tenant && ! $companyId && ! $stripeAccountId) {
            return [$base->get($columns), false, ''];
        }

        $scopeTenants = $tenant
            ? collect([$tenant])
            : XeroTenant::query()->whereIn('id', $scopedTenantIds ?? [])->get(['id', 'company_id', 'default_stripe_account_id']);

        $companyIds = $companyId
            ? [$companyId]
            : $scopeTenants->pluck('company_id')->filter()->unique()->values()->all();

        $accountIds = $stripeAccountId
            ? [$stripeAccountId]
            : $scopeTenants->pluck('default_stripe_account_id')->filter()->unique()->values()->all();

        if (! $stripeAccountId && $companyIds !== []) {
            $accountIds = array_values(array_unique(array_merge(
                $accountIds,
                StripeAccount::whereIn('company_id', $companyIds)->pluck('id')->all(),
            )));
        }

        // An explicit company / Stripe-account filter is always honoured,
        // even when it (truthfully) has no customers yet. A lone unlinked
        // organisation falls back to every client so matching still works.
        if ($companyIds === [] && $accountIds === []) {
            if ($companyId || $stripeAccountId) {
                return [collect(), true, $this->clientScopeNote($tenant, $companyId, $stripeAccountId)];
            }

            return [$base->get($columns), false, 'Organisation not linked — showing all customers'];
        }

        $stripeCustomerIds = $accountIds === []
            ? []
            : StripeCustomer::whereIn('stripe_account_id', $accountIds)->pluck('stripe_customer_id')->all();

        $clients = (clone $base)
            ->where(fn ($q) => $q
                ->when($companyIds !== [], fn ($q) => $q->whereIn('company_id', $companyIds))
                ->when($stripeCustomerIds !== [], fn ($q) => $q->orWhereIn('stripe_customer_id', $stripeCustomerIds)))
            ->get($columns);

        return [$clients, true, $this->clientScopeNote($tenant, $companyId, $stripeAccountId)];
    }

    private function clientScopeNote(?XeroTenant $tenant, ?int $companyId, ?int $stripeAccountId): string
    {
        if ($tenant) {
            return $tenant->tenant_name;
        }

        $parts = [];

        if ($companyId) {
            $parts[] = Company::find($companyId)?->name;
        }

        if ($stripeAccountId) {
            $parts[] = StripeAccount::find($stripeAccountId)?->display_name;
        }

        return implode(' · ', array_filter($parts));
    }

    /**
     * Tenant ids for the selected company / Stripe-account scope.
     *
     * A Stripe account covers organisations that use it as their default
     * plus organisations of companies that own the account. Null means
     * "every organisation" (no scope); an empty array means the scope
     * matches nothing.
     *
     * @return int[]|null
     */
    private function scopedTenantIds(?int $tenantId, ?int $companyId, ?int $stripeAccountId): ?array
    {
        if ($tenantId) {
            return [$tenantId];
        }

        if (! $companyId && ! $stripeAccountId) {
            return null;
        }

        return XeroTenant::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($stripeAccountId, fn ($query) => $query->where(
                fn ($q) => $q->where('default_stripe_account_id', $stripeAccountId)
                    ->orWhereHas('company.stripeAccounts', fn ($a) => $a->where('stripe_accounts.id', $stripeAccountId))
            ))
            ->pluck('id')
            ->all();
    }

    /**
     * Human-readable scope for flash messages and the activity log.
     *
     * @param  array{tenant_id?: int, company_id?: int, stripe_account_id?: int}  $data
     */
    private function scopeLabel(array $data): string
    {
        if (! empty($data['tenant_id'])) {
            return ' for '.XeroTenant::find($data['tenant_id'])?->tenant_name;
        }

        $parts = [];

        if (! empty($data['company_id'])) {
            $parts[] = Company::find($data['company_id'])?->name;
        }

        if (! empty($data['stripe_account_id'])) {
            $parts[] = StripeAccount::find($data['stripe_account_id'])?->display_name;
        }

        return $parts !== [] ? ' for '.implode(' · ', array_filter($parts)) : ' across all organisations';
    }
}
