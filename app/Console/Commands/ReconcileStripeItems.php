<?php

namespace App\Console\Commands;

use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatchItem;
use App\Services\StripeAccountResolver;
use App\Services\StripePayoutSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Safety net behind automatic reconciliation.
 *
 * Re-fetches the balance transaction for every succeeded-but-unreconciled
 * batch item (and settled direct-debit payments missing their BT id) by
 * PaymentIntent id. Covers webhooks that never arrived, endpoints missing
 * the `charge.succeeded` subscription, and transient Stripe API failures.
 *
 *  php artisan stripe:reconcile-items
 *  php artisan stripe:reconcile-items --days=14 --limit=500
 *  php artisan stripe:reconcile-items --account=3
 */
class ReconcileStripeItems extends Command
{
    protected $signature = 'stripe:reconcile-items
        {--days=7 : Only consider records created in the last N days}
        {--limit=200 : Max batch items to attempt (500 max)}
        {--account= : Only reconcile records for this Stripe account id}';

    protected $description = 'Reconcile succeeded Stripe items missed by webhooks (nightly safety net).';

    public function handle(StripeAccountResolver $accounts): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, min(500, (int) $this->option('limit')));
        $since = now()->subDays($days);

        /** @var Collection<int, StripeAccount|null> $targets */
        if ($accountId = $this->option('account')) {
            $account = StripeAccount::find($accountId);

            if (! $account) {
                $this->error("Stripe account [{$accountId}] not found.");

                return self::FAILURE;
            }

            $targets = collect([$account]);
            $includeLegacy = false;
        } else {
            $targets = StripeAccount::where('status', 'active')->orderBy('id')->get();
            $includeLegacy = true;
        }

        $attempted = 0;
        $reconciled = 0;

        foreach ($targets as $account) {
            $remaining = $limit - $attempted;

            if ($remaining <= 0) {
                break;
            }

            $sync = new StripePayoutSyncService($accounts->clientFor($account), $account);

            $items = $this->pendingItems($account->id, $since, $remaining);

            foreach ($items as $item) {
                $attempted++;

                try {
                    $record = $sync->reconcilePaymentIntent((string) $item->stripe_payment_intent_id);
                } catch (\Throwable) {
                    continue;
                }

                if ($record && $item->fresh()->isReconciled()) {
                    $reconciled++;
                }

                if ($attempted >= $limit) {
                    break;
                }
            }

            $this->backfillSettledDirectDebits($accounts, $account, $since);
        }

        if ($includeLegacy) {
            $remaining = $limit - $attempted;

            if ($remaining > 0) {
                $sync = app(StripePayoutSyncService::class);

                foreach ($this->pendingItems(null, $since, $remaining) as $item) {
                    $attempted++;

                    try {
                        $record = $sync->reconcilePaymentIntent((string) $item->stripe_payment_intent_id);
                    } catch (\Throwable) {
                        continue;
                    }

                    if ($record && $item->fresh()->isReconciled()) {
                        $reconciled++;
                    }
                }
            }

            $this->backfillSettledDirectDebits($accounts, null, $since);
        }

        $this->info("Done. {$reconciled}/{$attempted} item(s) reconciled (last {$days} day(s)).");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, StripeChargeBatchItem>
     */
    private function pendingItems(?int $accountId, \DateTimeInterface $since, int $limit): Collection
    {
        return StripeChargeBatchItem::query()
            ->when($accountId === null,
                fn ($q) => $q->whereNull('stripe_account_id'),
                fn ($q) => $q->where('stripe_account_id', $accountId))
            ->where('status', 'succeeded')
            ->where(fn ($q) => $q
                ->where('reconciliation_status', '!=', 'reconciled')
                ->orWhereNull('reconciliation_status'))
            ->whereNotNull('stripe_payment_intent_id')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function backfillSettledDirectDebits(StripeAccountResolver $accounts, ?StripeAccount $account, \DateTimeInterface $since): void
    {
        $sync = new StripePayoutSyncService($accounts->clientFor($account), $account);

        $payments = DirectDebitPayment::query()
            ->when($account === null,
                fn ($q) => $q->whereNull('stripe_account_id'),
                fn ($q) => $q->where('stripe_account_id', $account->id))
            ->where('status', 'settled')
            ->whereNull('stripe_balance_transaction_id')
            ->whereNotNull('gateway_payment_id')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($payments as $payment) {
            try {
                $record = $sync->reconcilePaymentIntent((string) $payment->gateway_payment_id);
            } catch (\Throwable) {
                continue;
            }

            if ($record) {
                $payment->update([
                    'stripe_fee' => ((float) ($record->fee ?? 0)) / 100,
                    'stripe_net' => ((float) ($record->net ?? 0)) / 100,
                    'stripe_balance_transaction_id' => $record->stripe_balance_transaction_id,
                ]);
            }
        }
    }
}
