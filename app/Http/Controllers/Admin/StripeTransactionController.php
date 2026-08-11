<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\StripeClient;

class StripeTransactionController extends Controller
{
    private const CACHE_TTL = 600; // 10 minutes

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'starting_after' => ['nullable', 'string', 'max:255'],
            'ending_before' => ['nullable', 'string', 'max:255'],
        ]);

        $params = [
            'limit' => 100,
        ];

        /*
         * Stripe transaction type.
         */
        if (!empty($validated['type'])) {
            $params['type'] = $validated['type'];
        }

        /*
         * Created date range.
         */
        if (!empty($validated['created_from'])) {
            $params['created']['gte'] = strtotime(
                $validated['created_from'] . ' 00:00:00'
            );
        }

        if (!empty($validated['created_to'])) {
            $params['created']['lte'] = strtotime(
                $validated['created_to'] . ' 23:59:59'
            );
        }

        /*
         * Stripe cursor pagination.
         *
         * A request should use either starting_after OR ending_before.
         */
        if (!empty($validated['starting_after'])) {
            $params['starting_after'] = $validated['starting_after'];
        } elseif (!empty($validated['ending_before'])) {
            $params['ending_before'] = $validated['ending_before'];
        }

        /*
         * Create a deterministic cache key from the actual Stripe
         * request parameters.
         *
         * Search is intentionally NOT included here because search
         * is performed locally against the cached Stripe response.
         */
        $cacheKey = $this->buildListCacheKey($params);

        $transactions = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($params) {
                $stripe = new StripeClient(
                    config('services.stripe.secret')
                );

                return $stripe->balanceTransactions->all($params);
            }
        );

        /*
         * Free-text search.
         *
         * Stripe Balance Transactions does not provide arbitrary
         * text search, so we search the transactions returned for
         * this page.
         */
        $search = trim(
            strtolower($validated['search'] ?? '')
        );

        $filteredTransactions = collect($transactions->data);

        if ($search !== '') {
            $filteredTransactions = $filteredTransactions
                ->filter(function ($transaction) use ($search) {

                    $sourceId = $this->getSourceId($transaction);

                    $searchable = strtolower(
                        implode(' ', [
                            $transaction->id ?? '',
                            $transaction->description ?? '',
                            $transaction->type ?? '',
                            $sourceId,
                        ])
                    );

                    return str_contains($searchable, $search);
                })
                ->values();
        }

        return view('admin.stripe.transactions.index', [
            'transactions' => $filteredTransactions,

            'stripeResponse' => $transactions,

            'hasMore' => $transactions->has_more,

            'firstId' => $transactions->data[0]->id ?? null,

            'lastId' => !empty($transactions->data)
                ? $transactions->data[count($transactions->data) - 1]->id
                : null,

            'search' => $validated['search'] ?? null,

            'type' => $validated['type'] ?? null,

            'createdFrom' => $validated['created_from'] ?? null,

            'createdTo' => $validated['created_to'] ?? null,
        ]);
    }


    public function show(string $transactionId)
    {
        $cacheKey = 'stripe:balance-transaction:'
            . $transactionId;

        $transaction = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($transactionId) {

                $stripe = new StripeClient(
                    config('services.stripe.secret')
                );

                return $stripe->balanceTransactions->retrieve(
                    $transactionId
                );
            }
        );

        return view('admin.stripe.transactions.show', [
            'transaction' => $transaction,
        ]);
    }


    /**
     * Build a deterministic cache key for a Stripe transaction list.
     */
    private function buildListCacheKey(array $params): string
    {
        return 'stripe:balance-transactions:list:'
            . md5(json_encode($params));
    }


    /**
     * Get the source/payment ID from a Stripe balance transaction.
     */
    private function getSourceId($transaction): string
    {
        if (!isset($transaction->source)) {
            return '';
        }

        if (is_string($transaction->source)) {
            return $transaction->source;
        }

        return $transaction->source->id ?? '';
    }
}
