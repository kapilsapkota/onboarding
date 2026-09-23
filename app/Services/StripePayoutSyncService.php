<?php

namespace App\Services;

use App\Models\StripeBalanceTransaction;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Single place that knows how to turn Stripe API objects
 * (payouts + balance transactions) into local rows.
 *
 * Used by:
 *  - the one-time backfill command (stripe:sync-payouts)
 *  - the webhook handlers (payout.*, charge.succeeded)
 *
 * Reconciliation rule for "ours vs theirs":
 *  a balance transaction is_app_transaction = true when its
 *  charge / payment_intent / customer can be tied back to a
 *  local StripeChargeBatchItem, StripeCustomer or DirectDebitPayment.
 *  Everything else (other apps on the same Stripe account) stays
 *  stored but flagged false so the UI can filter it.
 */
class StripePayoutSyncService
{
    public function __construct(private ?StripeClient $stripe = null)
    {
        $this->stripe = $stripe ?? new StripeClient(config('services.stripe.secret'));
    }

    public function client(): StripeClient
    {
        return $this->stripe;
    }

    // -----------------------------------------------------------------
    // One-time sync entry points
    // -----------------------------------------------------------------

    /**
     * Pull all payouts (newest first), then pull each payout's
     * balance transactions. Returns [payouts, transactions] counts.
     */
    public function syncAll(int $payoutLimit = 100, bool $withTransactions = true, ?int $createdAfter = null): array
    {
        $payouts = 0;
        $transactions = 0;
        $params = ['limit' => min($payoutLimit, 100)];

        if ($createdAfter) {
            $params['created']['gte'] = $createdAfter;
        }

        do {
            $page = $this->stripe->payouts->all($params);

            foreach ($page->data as $payout) {
                $local = $this->upsertPayout($payout);
                $payouts++;

                if ($withTransactions) {
                    $transactions += $this->syncPayoutTransactions($local->stripe_payout_id);
                }
            }

            $params['starting_after'] = $page->has_more
                ? $page->data[count($page->data) - 1]->id
                : null;
        } while (! empty($params['starting_after']));

        return [$payouts, $transactions];
    }

    /**
     * Pull every balance transaction that Stripe attributes to one payout.
     * This is the ONLY reliable way to build the reconciled view —
     * the payout object itself does not embed its line items.
     */
    public function syncPayoutTransactions(string $payoutStripeId): int
    {
        $localPayout = StripePayout::where('stripe_payout_id', $payoutStripeId)->first();
        $count = 0;
        $params = [
            'payout' => $payoutStripeId,
            'limit' => 100,
            'expand' => ['data.source'],
        ];

        do {
            $page = $this->stripe->balanceTransactions->all($params);

            foreach ($page->data as $bt) {
                $this->upsertBalanceTransaction($bt, $payoutStripeId);
                $count++;
            }

            $params['starting_after'] = $page->has_more
                ? $page->data[count($page->data) - 1]->id
                : null;
        } while (! empty($params['starting_after']));

        // Refresh payout's reconciliation_status from Stripe after backfill,
        // so the UI stops showing "in_progress".
        if ($localPayout) {
            try {
                $fresh = $this->stripe->payouts->retrieve($payoutStripeId);
                $this->upsertPayout($fresh);
            } catch (\Throwable $e) {
                Log::warning('StripePayoutSync: payout refresh failed', [
                    'payout' => $payoutStripeId, 'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // -----------------------------------------------------------------
    // Upserts (idempotent — safe to call from sync AND webhooks)
    // -----------------------------------------------------------------

    public function upsertPayout(object|array $payout): StripePayout
    {
        $arr = $this->normalize($payout);

        return StripePayout::updateOrCreate(
            ['stripe_payout_id' => $arr['id']],
            [
                'status' => $this->strOrNull($arr['status'] ?? null),
                'reconciliation_status' => $this->strOrNull($arr['reconciliation_status'] ?? null),
                'type' => $this->strOrNull($arr['type'] ?? null),
                'method' => $this->strOrNull($arr['method'] ?? null),
                'currency' => strtolower($this->strOrNull($arr['currency'] ?? null) ?? 'aud'),
                'amount' => $arr['amount'] ?? 0,
                'arrival_at' => isset($arr['arrival_date']) ? Carbon::createFromTimestamp($arr['arrival_date']) : null,
                'paid_at' => (! empty($arr['arrival_date']) && ($arr['status'] ?? null) === 'paid')
                    ? Carbon::createFromTimestamp($arr['arrival_date'])
                    : null,
                'stripe_created_at' => isset($arr['created']) ? Carbon::createFromTimestamp($arr['created']) : null,
                'destination' => $this->strOrNull($this->resolveId($arr['destination'] ?? null) ?? ($arr['destination'] ?? null)),
                'description' => $this->strOrNull($arr['description'] ?? null),
                'statement_descriptor' => $this->strOrNull($arr['statement_descriptor'] ?? null),
                'automatic' => is_bool($arr['automatic'] ?? null) ? $arr['automatic'] : true,
                'trace_id' => $this->strOrNull($arr['trace_id'] ?? null),
                'failure_code' => $this->strOrNull($arr['failure_code'] ?? null),
                'failure_message' => $this->strOrNull($arr['failure_message'] ?? null),
                'failure_balance_transaction_stripe_id' => $this->strOrNull(
                    $this->resolveId($arr['failure_balance_transaction'] ?? null) ?? ($arr['failure_balance_transaction'] ?? null)
                ),
                'balance_transaction_stripe_id' => $this->strOrNull(
                    $this->resolveId($arr['balance_transaction'] ?? null) ?? ($arr['balance_transaction'] ?? null)
                ),
                'stripe_data' => $arr,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * @param  object  $bt  Stripe BalanceTransaction (expanded source if available)
     * @param  string|null  $payoutStripeId  The po_xxx we listed by (Stripe does not
     *   always echo it back on the BT, so the caller passes it explicitly).
     */
    public function upsertBalanceTransaction(object|array $bt, ?string $payoutStripeId = null): StripeBalanceTransaction
    {
        $arr = $this->normalize($bt);

        $source = $arr['source'] ?? null;
        $sourceId = $this->strOrNull($this->resolveId($source));
        $sourceType = $this->strOrNull($this->guessSourceType($source, $arr['type'] ?? null));

        // Pull charge / PI / customer out of the expanded source when we have it.
        $chargeId = $paymentIntentId = $customerId = null;

        if (is_array($source)) {
            if (($source['object'] ?? null) === 'charge') {
                $chargeId = $this->strOrNull($this->resolveId($source['id'] ?? null));
                // payment_intent may be a string or an expanded object — never store the array.
                $paymentIntentId = $this->strOrNull($this->resolveId($source['payment_intent'] ?? null));
                $customerId = $this->strOrNull($this->resolveId($source['customer'] ?? null));
            } elseif (($source['object'] ?? null) === 'payment_intent') {
                $paymentIntentId = $this->strOrNull($this->resolveId($source['id'] ?? null));
                $customerId = $this->strOrNull($this->resolveId($source['customer'] ?? null));
                $chargeId = $this->strOrNull($this->resolveId($source['latest_charge'] ?? null));
            }
        }

        // Fallback: source is just "ch_xxx" string — fetch the charge once
        // so we can still resolve PI + customer for the app/external split.
        if ($sourceType === 'charge' && is_string($source) && (! $paymentIntentId || ! $customerId)) {
            try {
                $charge = $this->stripe->charges->retrieve($sourceId, ['expand' => ['payment_intent']]);
                $chargeId = $charge->id;
                $paymentIntentId = is_string($charge->payment_intent)
                    ? $charge->payment_intent
                    : ($charge->payment_intent->id ?? $paymentIntentId);
                $customerId = is_string($charge->customer)
                    ? $charge->customer
                    : ($charge->customer->id ?? $customerId);
            } catch (\Throwable $e) {
                Log::debug('StripePayoutSync: charge expand failed', ['source' => $sourceId]);
            }
        }

        $payoutStripeId = $this->strOrNull($payoutStripeId ?? $this->resolveId($arr['payout'] ?? null));
        $localPayoutId = $payoutStripeId
            ? StripePayout::where('stripe_payout_id', $payoutStripeId)->value('id')
            : null;

        $isApp = $this->isAppTransaction($chargeId, $paymentIntentId, $customerId, $sourceId);

        $record = StripeBalanceTransaction::updateOrCreate(
            ['stripe_balance_transaction_id' => $arr['id']],
            [
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'type' => $this->strOrNull($arr['type'] ?? null),
                'reporting_category' => $this->strOrNull($arr['reporting_category'] ?? null),
                'status' => $this->strOrNull($arr['status'] ?? null),
                'balance_type' => $this->strOrNull($arr['balance_type'] ?? null),
                'exchange_rate' => $this->strOrNull($arr['exchange_rate'] ?? null),
                'description' => $this->strOrNull(
                    (is_array($source) ? ($source['description'] ?? null) : null) ?? ($arr['description'] ?? null)
                ),
                'currency' => strtolower($this->strOrNull($arr['currency'] ?? null) ?? 'aud'),
                'amount' => $arr['amount'] ?? 0,
                'fee' => $arr['fee'] ?? 0,
                'net' => $arr['net'] ?? 0,
                'fee_details' => is_array($arr['fee_details'] ?? null) ? $arr['fee_details'] : null,
                'available_at' => isset($arr['available_on']) ? Carbon::createFromTimestamp($arr['available_on']) : null,
                'occurred_at' => isset($arr['created']) ? Carbon::createFromTimestamp($arr['created']) : null,
                'stripe_payout_id' => $localPayoutId,
                'payout_stripe_id' => $payoutStripeId,
                'charge_stripe_id' => $chargeId,
                'payment_intent_stripe_id' => $paymentIntentId,
                'customer_stripe_id' => $customerId,
                'is_app_transaction' => $isApp,
                'stripe_data' => $arr,
                'last_synced_at' => now(),
            ]
        );

        $this->linkBatchItem($record);

        return $record->fresh();
    }

    // -----------------------------------------------------------------
    // Reconciliation helpers
    // -----------------------------------------------------------------

    /**
     * Ours = anything we created: a batch item with the same charge / PI / BT,
     * a known StripeCustomer, or a DirectDebitPayment gateway id.
     * Everything else on the Stripe account = external (other apps).
     */
    public function isAppTransaction(?string $chargeId, ?string $paymentIntentId, ?string $customerId, ?string $sourceId): bool
    {
        if ($chargeId && StripeChargeBatchItem::where('stripe_charge_id', $chargeId)->exists()) {
            return true;
        }

        if ($paymentIntentId && StripeChargeBatchItem::where('stripe_payment_intent_id', $paymentIntentId)->exists()) {
            return true;
        }

        if ($sourceId && StripeChargeBatchItem::where('stripe_balance_transaction_id', $sourceId)->exists()) {
            return true;
        }

        if ($customerId && StripeCustomer::where('stripe_customer_id', $customerId)->exists()) {
            return true;
        }

        if ($paymentIntentId && \App\Models\DirectDebitPayment::where('gateway_payment_id', $paymentIntentId)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Attach gross/fee/net + payout to our batch item so the
     * batch show page can display "reconciled".
     */
    public function linkBatchItem(StripeBalanceTransaction $bt): void
    {
        $item = StripeChargeBatchItem::query()
            ->when($bt->stripe_balance_transaction_id, fn ($q) => $q->orWhere('stripe_balance_transaction_id', $bt->stripe_balance_transaction_id))
            ->when($bt->charge_stripe_id, fn ($q) => $q->orWhere('stripe_charge_id', $bt->charge_stripe_id))
            ->when($bt->payment_intent_stripe_id, fn ($q) => $q->orWhere('stripe_payment_intent_id', $bt->payment_intent_stripe_id))
            ->first();

        if (! $item) {
            return;
        }

        $item->update([
            'stripe_balance_transaction_id' => $bt->stripe_balance_transaction_id,
            'stripe_charge_id' => $item->stripe_charge_id ?? $bt->charge_stripe_id,
            'gross_amount' => $bt->amount,
            'fee_amount' => $bt->fee,
            'net_amount' => $bt->net,
            'stripe_payout_id' => $item->stripe_payout_id ?? $bt->stripe_payout_id,
            'charged_at' => $item->charged_at ?? $bt->occurred_at,
            'reconciled_at' => now(),
            'reconciliation_status' => 'reconciled',
        ]);
    }

    private function guessSourceType(mixed $source, mixed $btType): ?string
    {
        $btType = $this->strOrNull($btType);
        $normalized = $source;

        if (is_object($normalized) && method_exists($normalized, 'toArray')) {
            $normalized = $normalized->toArray();
        } elseif (is_object($normalized)) {
            $normalized = (array) $normalized;
        }

        if (is_array($normalized) && isset($normalized['object'])) {
            return $this->strOrNull($normalized['object']); // charge, payment_intent, payout, refund…
        }

        if (is_string($normalized)) {
            return match (true) {
                str_starts_with($normalized, 'ch_') => 'charge',
                str_starts_with($normalized, 'py_') => 'charge',
                str_starts_with($normalized, 'pi_') => 'payment_intent',
                str_starts_with($normalized, 'po_') => 'payout',
                str_starts_with($normalized, 're_') => 'refund',
                str_starts_with($normalized, 'dp_') => 'dispute',
                default => $btType,
            };
        }

        return $btType;
    }

    /**
     * Stripe webhook objects are StripeObject (ArrayAccess), API objects
     * are Payout/BalanceTransaction/Charge models. (array) casting either
     * leaves nested objects intact — which later blows up as
     * "Array to string conversion" on string columns. Normalise to a
     * plain PHP array first.
     */
    private function normalize(object|array $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (method_exists($value, 'toArray')) {
            $out = $value->toArray();

            return is_array($out) ? $out : [];
        }

        return json_decode(json_encode($value), true) ?? [];
    }

    /** Return a string column value or null — never an array/object. */
    private function strOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return null;
    }

    private function resolveId(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_array($value)) {
            $id = $value['id'] ?? null;

            return is_string($id) ? $id : null;
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                $arr = $value->toArray();
                $id = is_array($arr) ? ($arr['id'] ?? null) : null;

                return is_string($id) ? $id : null;
            }

            $id = $value->id ?? null;

            return is_string($id) ? $id : null;
        }

        return null;
    }
}
