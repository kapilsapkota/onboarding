<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\StripeClient;

class StripePayoutController extends Controller
{
    /**
     * Stripe data is cached for 10 minutes.
     */
    private const CACHE_TTL = 600;

    public function index(Request $request)
    {
        $params = [
            'limit' => 25,
        ];

        if ($request->filled('starting_after')) {
            $params['starting_after'] = $request->string('starting_after')->toString();
        }

        if ($request->filled('ending_before')) {
            $params['ending_before'] = $request->string('ending_before')->toString();
        }

        /*
         * The pagination cursor is part of the cache key.
         *
         * This means:
         *
         * Page 1 -> cache A
         * Page 2 -> cache B
         * Previous -> cache A
         */
        $cacheKey = $this->buildListCacheKey($params);

        $payouts = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($params) {
                return $this->stripe()
                    ->payouts
                    ->all($params);
            }
        );

        $data = $payouts->data;

        return view('admin.payouts.index', [
            'payouts' => $data,

            'hasMore' => $payouts->has_more,

            'firstId' => $data[0]->id ?? null,

            'lastId' => !empty($data)
                ? $data[count($data) - 1]->id
                : null,
        ]);
    }


    public function show(Request $request, string $payoutId)
    {
        /*
         * ---------------------------------------------------------
         * PAYOUT
         * ---------------------------------------------------------
         */

        $payout = Cache::remember(
            $this->payoutCacheKey($payoutId),
            self::CACHE_TTL,
            function () use ($payoutId) {
                return $this->stripe()
                    ->payouts
                    ->retrieve($payoutId);
            }
        );


        /*
         * ---------------------------------------------------------
         * PAYOUT TRANSACTIONS
         * ---------------------------------------------------------
         */

        $transactions = null;

        $canListTransactions = false;

        $transactionMessage = null;

        if ($payout->reconciliation_status === 'completed') {

            $canListTransactions = true;

            $params = [
                'payout' => $payoutId,
                'limit' => 100,
                'expand' => ['data.source'],
            ];

            if ($request->filled('starting_after')) {
                $params['starting_after'] =
                    $request->string('starting_after')->toString();
            }

            if ($request->filled('ending_before')) {
                $params['ending_before'] =
                    $request->string('ending_before')->toString();
            }
            /*
             * Important:
             *
             * The payout ID and pagination cursor are both included
             * in the cache key.
             */
            $transactionsCacheKey =
                $this->payoutTransactionsCacheKey($payoutId, $params);

            $transactions = Cache::remember(
                $transactionsCacheKey,
                self::CACHE_TTL,
                function () use ($params) {
                    return $this->stripe()
                        ->balanceTransactions
                        ->all($params);
                }
            );
            $this->attachLocalCustomers($transactions);

        } elseif ($payout->reconciliation_status === 'in_progress') {

            $transactionMessage =
                'Stripe is still reconciling this payout. '
                . 'The associated transactions may become available later.';

        } else {

            /*
             * This can happen with instant/non-standard payouts where
             * Stripe does not expose the individual funding transactions
             * through the payout relationship.
             */
            $transactionMessage =
                'Stripe does not provide the individual balance '
                . 'transactions for this payout.';
        }


        /*
         * ---------------------------------------------------------
         * PAYOUT'S OWN BALANCE TRANSACTION
         * ---------------------------------------------------------
         *
         * This is different from the transactions that funded the
         * payout.
         *
         * It represents the payout itself leaving the Stripe balance.
         */

        $payoutBalanceTransaction = null;

        if (!empty($payout->balance_transaction)) {

            $balanceTransactionId =
                is_string($payout->balance_transaction)
                    ? $payout->balance_transaction
                    : $payout->balance_transaction->id;

            $payoutBalanceTransaction = Cache::remember(
                $this->balanceTransactionCacheKey(
                    $balanceTransactionId
                ),
                self::CACHE_TTL,
                function () use ($balanceTransactionId) {
                    return $this->stripe()
                        ->balanceTransactions
                        ->retrieve(
                            $balanceTransactionId
                        );
                }
            );
        }


        return view('admin.payouts.show', [
            'payout' => $payout,

            /*
             * null when Stripe does not expose the individual
             * transactions for this payout.
             */
            'transactions' => $transactions,

            'canListTransactions' => $canListTransactions,

            'transactionMessage' => $transactionMessage,

            /*
             * The payout's own balance transaction.
             */
            'payoutBalanceTransaction' => $payoutBalanceTransaction,
        ]);
    }

    private function attachLocalCustomers($transactions): void
    {
        if (!$transactions || empty($transactions->data)) {
            return;
        }

        // Collect customer IDs from transactions that have
        // both a source and a customer.
        $customerIds = collect($transactions->data)
            ->map(function ($transaction) {
                return $transaction->source?->customer;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // One local database query.
        $customers = StripeCustomer::query()
            ->whereIn('stripe_customer_id', $customerIds)
            ->get()
            ->keyBy('stripe_customer_id');

        // Add customer_name to each transaction.
        foreach ($transactions->data as $transaction) {
            $customerId = $transaction->source?->customer;

            $transaction->customer_name =
                $customerId
                    ? $customers->get($customerId)?->name
                    : null;
        }
    }


    /**
     * Create a Stripe client.
     *
     * Keeping this in one place avoids repeatedly constructing
     * the client throughout the controller.
     */
    private function stripe(): StripeClient
    {
        return new StripeClient(
            config('services.stripe.secret')
        );
    }


    /**
     * Cache key for payout listing.
     */
    private function buildListCacheKey(array $params): string
    {
        return 'stripe:payouts:list:'
            . md5(json_encode($params));
    }


    /**
     * Cache key for an individual payout.
     */
    private function payoutCacheKey(string $payoutId): string
    {
        return 'stripe:payout:' . $payoutId;
    }


    /**
     * Cache key for payout transactions.
     *
     * Includes pagination parameters so different pages don't
     * accidentally share the same cached response.
     */
    private function payoutTransactionsCacheKey(
        string $payoutId,
        array  $params
    ): string
    {
        return 'stripe:payout:'
            . $payoutId
            . ':transactions:'
            . md5(json_encode($params));
    }


    /**
     * Cache key for a balance transaction.
     */
    private function balanceTransactionCacheKey(
        string $transactionId
    ): string
    {
        return 'stripe:balance-transaction:'
            . $transactionId;
    }
}
