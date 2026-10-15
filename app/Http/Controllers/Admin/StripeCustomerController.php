<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncStripeBecsCustomersJob;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Services\StripeAccountResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StripeCustomerController extends Controller
{
    public function __construct(
        private StripeAccountResolver $accounts,
    ) {}

    /** Lists mirrored Stripe customers with search and account filters. */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('view-direct-debit'), 403);

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'stripe_account' => ['nullable', 'string', 'max:50'],
        ]);

        $selectedAccount = $this->resolveSelectedAccount($request);
        $search = trim($request->input('search', ''));

        $customers = StripeCustomer::query()
            ->with([
                'stripeAccount.company',
                'paymentMethods' => fn ($q) => $q
                    ->where('type', 'au_becs_debit')
                    ->orderByDesc('is_default'),
                'clients.company:id,name',
            ])
            ->withCount([
                'paymentMethods as active_becs_count' => fn ($q) => $q
                    ->where('type', 'au_becs_debit')
                    ->where('status', 'active'),
            ])
            ->when($selectedAccount === 'unassigned', fn ($q) => $q->whereNull('stripe_account_id'))
            ->when($selectedAccount instanceof StripeAccount, fn ($q) => $q->where('stripe_account_id', $selectedAccount->id))
            ->when($search, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('stripe_customer_id', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.stripe.customers.index', [
            'customers' => $customers,
            'search' => $search,
            'stripeAccounts' => $this->visibleAccounts($request),
            'selectedAccount' => $selectedAccount,
        ]);
    }

    /** Shows a single mirrored Stripe customer and its payment methods. */
    public function show(Request $request, StripeCustomer $customer): View
    {
        abort_unless($request->user()?->can('view-direct-debit'), 403);

        if ($customer->stripe_account_id) {
            $this->accounts->assertAccountVisible($request->user(), $customer->stripeAccount);
        }

        $customer->load([
            'stripeAccount.company',
            'paymentMethods' => fn ($q) => $q->orderByDesc('is_default'),
            'clients.company:id,name',
            'batchItems' => fn ($q) => $q->latest()->limit(10),
            'batchItems.batch',
        ]);

        return view('admin.stripe.customers.show', compact('customer'));
    }

    /** Queues one sync job per Stripe account into the local mirror. */
    public function sync(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view-direct-debit'), 403);

        $data = $request->validate([
            'stripe_account_id' => ['nullable', 'integer', 'exists:stripe_accounts,id'],
        ]);

        if (! empty($data['stripe_account_id'])) {
            $account = $this->accounts->forAccount((int) $data['stripe_account_id']);
            $this->accounts->assertAccountVisible($request->user(), $account);

            SyncStripeBecsCustomersJob::dispatch($account->id);

            return back()->with(
                'success',
                "Stripe customer sync queued for {$account->display_name}. It runs in the background."
            );
        }

        $accountIds = $this->accounts->visibleAccounts($request->user())
            ->where('is_legacy', false)
            ->pluck('id')
            ->all();

        foreach ($accountIds as $accountId) {
            SyncStripeBecsCustomersJob::dispatch($accountId);
        }

        SyncStripeBecsCustomersJob::dispatch(null);

        $total = count($accountIds) + 1;

        return back()->with(
            'success',
            "Stripe customer sync queued across all accounts ({$total} jobs). It runs in the background."
        );
    }

    private function visibleAccounts(Request $request): Collection
    {
        return $this->accounts->visibleAccounts($request->user());
    }

    /**
     * Returns 'all', 'unassigned', or the resolved account for the filter.
     */
    private function resolveSelectedAccount(Request $request): string|StripeAccount
    {
        $filter = $request->input('stripe_account', 'all');

        if ($filter === 'unassigned' || $filter === 'all') {
            return $filter;
        }

        if (! is_numeric($filter)) {
            return 'all';
        }

        $account = $this->accounts->forAccount((int) $filter);
        $this->accounts->assertAccountVisible($request->user(), $account);

        return $account;
    }
}
