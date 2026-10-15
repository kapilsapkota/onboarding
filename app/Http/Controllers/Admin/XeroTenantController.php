<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateXeroTenantRequest;
use App\Models\Client;
use App\Models\Company;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\XeroTenant;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Xero organisations as the source of truth for billing identity.
 *
 * Tenants originate from Xero OAuth (never created/deleted here); this
 * hub manages the local links that bind an organisation to a Company,
 * its default Stripe account for collections, and the customer graph
 * (Xero contacts ↔ Clients ↔ Stripe customers) reconciled from it.
 */
class XeroTenantController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAction('view-xero');

        $query = XeroTenant::query()
            ->with(['company', 'defaultStripeAccount', 'connection'])
            ->withCount(['contacts', 'invoices', 'repeatingInvoices'])
            ->orderBy('tenant_name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('tenant_name', 'like', "%{$search}%")
                    ->orWhere('tenant_id', 'like', "%{$search}%");
            });
        }

        if ($request->query('link') === 'unlinked') {
            $query->whereNull('company_id');
        } elseif ($request->query('link') === 'linked') {
            $query->whereNotNull('company_id');
        }

        $tenants = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => XeroTenant::count(),
            'active' => XeroTenant::where('is_active', true)->count(),
            'linked' => XeroTenant::whereNotNull('company_id')->count(),
            'unlinked' => XeroTenant::whereNull('company_id')->count(),
        ];

        return view('admin.xero.organisations.index', compact('tenants', 'stats'));
    }

    public function show(XeroTenant $organisation): View
    {
        $this->authorizeAction('view-xero');

        $organisation->load([
            'company.stripeAccounts',
            'defaultStripeAccount.company',
            'connection',
        ]);

        $contactStats = [
            'total' => $organisation->contacts()->count(),
            'matched' => (clone $organisation->contacts())->where('is_matched', true)->count(),
            'unmatched' => (clone $organisation->contacts())->where('is_matched', false)->count(),
        ];

        $invoiceStats = [
            'total' => $organisation->invoices()->count(),
            'outstanding' => (clone $organisation->invoices())->where('amount_due', '>', 0)->whereNotIn('status', ['VOIDED', 'DELETED'])->sum('amount_due'),
            'overdue' => (clone $organisation->invoices())->overdue()->count(),
        ];

        $stripeCustomers = $this->stripeCustomersFor($organisation);

        return view('admin.xero.organisations.show', compact('organisation', 'contactStats', 'invoiceStats', 'stripeCustomers'));
    }

    public function edit(XeroTenant $organisation): View
    {
        $this->authorizeAction('configure-xero');

        $organisation->load(['company', 'defaultStripeAccount']);

        $companies = Company::orderBy('name')->get(['id', 'name']);
        $stripeAccounts = StripeAccount::with('company:id,name')->orderBy('display_name')->get();

        return view('admin.xero.organisations.edit', compact('organisation', 'companies', 'stripeAccounts'));
    }

    public function update(UpdateXeroTenantRequest $request, XeroTenant $organisation): RedirectResponse
    {
        $validated = $request->validated();

        $companyId = $validated['company_id'] ?? null;
        $stripeAccountId = $validated['default_stripe_account_id'] ?? null;

        // Clearing the company orphans any company-owned default account.
        if ($stripeAccountId && ! $companyId) {
            $account = StripeAccount::find($stripeAccountId);

            if ($account?->company_id) {
                $stripeAccountId = null;
            }
        }

        $organisation->update([
            'company_id' => $companyId,
            'default_stripe_account_id' => $stripeAccountId,
            'is_active' => $request->boolean('is_active', $organisation->is_active),
        ]);

        Activity::record(
            description: "Updated links for Xero organisation {$organisation->tenant_name}",
            subject: $organisation,
            event: 'updated',
            properties: [
                'company_id' => $organisation->company_id,
                'default_stripe_account_id' => $organisation->default_stripe_account_id,
            ],
            logName: 'xero',
        );

        return redirect()
            ->route('admin.xero.organisations.show', $organisation)
            ->with('success', "Organisation {$organisation->tenant_name} updated.");
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    /**
     * Stripe customers reachable from this organisation: the default
     * account's customers when linked, otherwise the linked company's
     * accounts' customers. Each row carries its Client + Xero-contact
     * match state so mismatches are visible in one place.
     *
     * @return Collection<int, array{customer: StripeCustomer, client: ?Client}>
     */
    private function stripeCustomersFor(XeroTenant $organisation): Collection
    {
        $accounts = $organisation->defaultStripeAccount
            ? collect([$organisation->defaultStripeAccount])
            : $organisation->company?->stripeAccounts ?? collect();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $customers = StripeCustomer::query()
            ->whereIn('stripe_account_id', $accounts->pluck('id'))
            ->with(['stripeAccount', 'paymentMethods'])
            ->orderBy('name')
            ->limit(25)
            ->get();

        if ($customers->isEmpty()) {
            return collect();
        }

        $clients = Client::query()
            ->whereIn('stripe_customer_id', $customers->pluck('stripe_customer_id'))
            ->with('xeroContacts')
            ->get()
            ->keyBy('stripe_customer_id');

        return $customers->map(fn ($customer) => [
            'customer' => $customer,
            'client' => $clients->get($customer->stripe_customer_id),
        ]);
    }
}
