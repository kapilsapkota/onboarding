<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeCustomer;
use App\Models\StripePayout;
use Illuminate\Http\Request;

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
    public function index(Request $request)
    {
        $payouts = StripePayout::query()
            ->withCount([
                'balanceTransactions as app_count' => fn ($q) => $q->where('is_app_transaction', true),
                'balanceTransactions as external_count' => fn ($q) => $q->where('is_app_transaction', false),
            ])
            ->orderByDesc('stripe_created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.payouts.index-db', [
            'payouts' => $payouts,
        ]);
    }

    public function show(Request $request, string $payout)
    {
        $local = StripePayout::where('stripe_payout_id', $payout)
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

        $summary = [
            'count' => (clone $txQuery)->count(),
            'gross' => (clone $txQuery)->sum('amount'),
            'fees' => (clone $txQuery)->sum('fee'),
            'net' => (clone $txQuery)->sum('net'),
            'app_count' => $local->balanceTransactions()->where('is_app_transaction', true)->count(),
            'external_count' => $local->balanceTransactions()->where('is_app_transaction', false)->count(),
        ];

        return view('admin.payouts.show-db', [
            'payout' => $local,
            'transactions' => $transactions,
            'summary' => $summary,
            'scope' => $scope,
            'type' => $type,
        ]);
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
