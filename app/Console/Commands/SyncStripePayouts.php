<?php

namespace App\Console\Commands;

use App\Services\StripePayoutSyncService;
use Illuminate\Console\Command;

/**
 * One-time backfill: run once after deploying the new tables,
 * then rely on webhooks for everything after.
 *
 *  php artisan stripe:sync-payouts
 *  php artisan stripe:sync-payouts --payout=po_xxx   (re-sync one payout + its lines)
 *  php artisan stripe:sync-payouts --no-transactions (payouts only, faster)
 *  php artisan stripe:sync-payouts --days=30         (only payouts created in last 30d)
 */
class SyncStripePayouts extends Command
{
    protected $signature = 'stripe:sync-payouts
        {--payout= : Re-sync a single payout by its Stripe id (po_xxx)}
        {--no-transactions : Skip balance-transaction backfill}
        {--days= : Only sync payouts created in the last N days}';

    protected $description = 'One-time backfill of Stripe payouts + balance transactions into local tables.';

    public function handle(StripePayoutSyncService $sync): int
    {
        if ($payoutId = $this->option('payout')) {
            $this->info("Syncing payout {$payoutId}…");

            $stripePayout = $sync->client()->payouts->retrieve($payoutId);
            $sync->upsertPayout($stripePayout);

            if (! $this->option('no-transactions')) {
                $n = $sync->syncPayoutTransactions($payoutId);
                $this->info("Synced {$n} balance transaction(s) for {$payoutId}.");
            }

            return self::SUCCESS;
        }

        $createdAfter = null;

        if ($days = $this->option('days')) {
            $createdAfter = now()->subDays((int) $days)->timestamp;
            $this->info("Syncing payouts from the last {$days} day(s)…");
        } else {
            $this->info('Syncing all payouts from Stripe (one-time backfill)…');
        }

        [$payouts, $transactions] = $sync->syncAll(
            withTransactions: ! $this->option('no-transactions'),
            createdAfter: $createdAfter,
        );

        $this->info("Done. {$payouts} payout(s), {$transactions} balance transaction(s).");
        $this->line('Webhooks will keep these tables up to date from here.');

        return self::SUCCESS;
    }
}
