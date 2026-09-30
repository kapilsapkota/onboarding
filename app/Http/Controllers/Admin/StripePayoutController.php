<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePayout;
use App\Services\StripeAccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Reconciled (DB-backed) payout views.
 *
 * Run `php artisan stripe:sync-payouts` once to backfill, then
 * webhooks (payout.* + charge.succeeded) keep these tables fresh.
 * No live Stripe calls here — the old live-API version is kept in
 * git history if you need it.
 */
class StripePayoutController extends Controller
{
    public function index(Request $request, StripeAccountResolver $accounts)
    {
        $stripeAccounts = $accounts->visibleAccounts($request->user());
        $selectedAccount = $this->resolveSelectedAccount($request, $accounts);

        $payouts = StripePayout::query()
            ->with('stripeAccount.company')
            ->when($selectedAccount === 'unassigned', fn ($q) => $q->whereNull('stripe_account_id'))
            ->when($selectedAccount instanceof StripeAccount, fn ($q) => $q->where('stripe_account_id', $selectedAccount->id))
            ->withCount([
                'balanceTransactions as app_count' => fn ($q) => $q->where('is_app_transaction', true),
                'balanceTransactions as external_count' => fn ($q) => $q->where('is_app_transaction', false),
                // Charge lines only — excludes Stripe's own `payout` debit row.
                'balanceTransactions as charges_count' => fn ($q) => $q->where('type', '!=', 'payout'),
                'items as items_count',
            ])
            ->withSum([
                'balanceTransactions as charges_gross' => fn ($q) => $q->where('type', '!=', 'payout'),
            ], 'amount')
            ->withSum([
                'balanceTransactions as charges_fees' => fn ($q) => $q->where('type', '!=', 'payout'),
            ], 'fee')
            ->withSum([
                'balanceTransactions as charges_net' => fn ($q) => $q->where('type', '!=', 'payout'),
            ], 'net')
            ->withSum('items as items_gross', 'gross_amount')
            ->withSum('items as items_fees', 'fee_amount')
            ->withSum('items as items_net', 'net_amount')
            ->orderByDesc('stripe_created_at')
            ->orderByDesc('id')
            ->paginate(25);

        $payoutIds = $payouts->getCollection()->pluck('id')->all();
        $itemsMap = $this->itemsMapForPayoutIds($payoutIds);

        return view('admin.payouts.index-db', [
            'payouts' => $payouts,
            'itemsMap' => $itemsMap,
            'stripeAccounts' => $stripeAccounts,
            'selectedAccount' => $selectedAccount,
        ]);
    }

    /**
     * Returns 'all', 'unassigned', or the resolved account for the filter.
     */
    private function resolveSelectedAccount(Request $request, StripeAccountResolver $accounts): string|StripeAccount
    {
        $filter = $request->input('stripe_account', 'all');

        if ($filter === 'unassigned' || $filter === 'all') {
            return $filter;
        }

        if (! is_numeric($filter)) {
            return 'all';
        }

        $account = $accounts->forAccount((int) $filter);
        $accounts->assertAccountVisible($request->user(), $account);

        return $account;
    }

    public function show(Request $request, string $payout)
    {
        $local = StripePayout::with('stripeAccount.company')
            ->where('stripe_payout_id', $payout)
            ->orWhere('id', $payout)
            ->firstOrFail();

        $scope = $request->string('scope')->toString(); // '', 'app', 'external'
        $type = $request->string('type')->toString();

        $txQuery = $local->balanceTransactions()
            ->with(['batchItem.batch', 'batchItem.stripeCustomer'])
            ->orderByDesc('occurred_at');

        if ($scope === 'app') {
            $txQuery->where('is_app_transaction', true);
        } elseif ($scope === 'external') {
            $txQuery->where('is_app_transaction', false);
        }

        if ($type !== '') {
            $txQuery->where('type', $type);
        }

        $transactions = $txQuery->paginate(100)->withQueryString();

        $this->attachCustomerDetails($transactions);

        // Full-payout totals over charge lines only (excludes Stripe's own
        // `payout` debit row, which would otherwise zero-out the sums).
        $chargesQuery = $local->balanceTransactions()->where('type', '!=', 'payout');

        $summary = [
            'count' => (clone $txQuery)->count(),
            'gross' => (clone $txQuery)->sum('amount'),
            'fees' => (clone $txQuery)->sum('fee'),
            'net' => (clone $txQuery)->sum('net'),
            'charges_count' => (clone $chargesQuery)->count(),
            'charges_gross' => (clone $chargesQuery)->sum('amount'),
            'charges_fees' => (clone $chargesQuery)->sum('fee'),
            'charges_net' => (clone $chargesQuery)->sum('net'),
            'app_count' => $local->balanceTransactions()->where('is_app_transaction', true)->count(),
            'external_count' => $local->balanceTransactions()->where('is_app_transaction', false)->count(),
        ];

        $batchItems = $local->items()
            ->with(['batch', 'stripeCustomer', 'stripePaymentMethod', 'balanceTransaction'])
            ->orderBy('id')
            ->get();

        $itemsSummary = [
            'count' => $batchItems->count(),
            'gross' => $batchItems->sum('gross_amount'),
            'fees' => $batchItems->sum('fee_amount'),
            'net' => $batchItems->sum('net_amount'),
        ];

        return view('admin.payouts.show-db', [
            'payout' => $local,
            'transactions' => $transactions,
            'summary' => $summary,
            'scope' => $scope,
            'type' => $type,
            'batchItems' => $batchItems,
            'itemsSummary' => $itemsSummary,
        ]);
    }

    /**
     * Maps local payout ids => collection of StripeChargeBatchItems (with
     * batch + customer) belonging to the payout.
     *
     * @param  int[]  $payoutIds
     * @return array<int, Collection>
     */
    private function itemsMapForPayoutIds(array $payoutIds): array
    {
        if (empty($payoutIds)) {
            return [];
        }

        $grouped = StripeChargeBatchItem::query()
            ->with(['batch:id,reference', 'stripeCustomer:id,name,email'])
            ->whereIn('stripe_payout_id', $payoutIds)
            ->orderBy('id')
            ->get()
            ->groupBy('stripe_payout_id');

        $map = [];

        foreach ($payoutIds as $id) {
            $map[$id] = $grouped->get($id, collect());
        }

        return $map;
    }

    /**
     * Attaches local customer details (name + email) to each balance
     * transaction — same role as the live view's attachLocalCustomers().
     * Falls back to the linked batch item's customer when the BT itself
     * carries no customer id (e.g. refunds/adjustments).
     */
    private function attachCustomerDetails($transactions): void
    {
        if ($transactions->isEmpty()) {
            return;
        }

        $customerIds = $transactions->getCollection()
            ->map(fn ($tx) => $tx->customer_stripe_id)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $customers = StripeCustomer::query()
            ->whereIn('stripe_customer_id', $customerIds)
            ->get()
            ->keyBy('stripe_customer_id');

        foreach ($transactions as $tx) {
            $customer = $tx->customer_stripe_id
                ? $customers->get($tx->customer_stripe_id)
                : null;

            $customer ??= $tx->batchItem?->stripeCustomer;

            $tx->customer_name = $customer?->name;
            $tx->customer_email = $customer?->email;
            $tx->is_app_transaction_flag = $tx->is_app_transaction
                || $customer !== null;
        }
    }
}
