<?php

namespace App\Console\Commands;

use App\Models\StripeAccount;
use App\Services\StripeAccountResolver;
use App\Services\StripePayoutSyncService;
use Illuminate\Console\Command;

/**
 * Backfill of Stripe payouts + balance transactions into local tables.
 * Webhooks keep things up to date afterwards, with stripe:reconcile-items
 * as the nightly safety net for anything missed.
 *
 *  php artisan stripe:sync-payouts                    (legacy pool + every active account)
 *  php artisan stripe:sync-payouts --payout=po_xxx   (re-sync one payout + its lines)
 *  php artisan stripe:sync-payouts --no-transactions (payouts only, faster)
 *  php artisan stripe:sync-payouts --days=30         (only payouts created in last 30d)
 *  php artisan stripe:sync-payouts --account=3       (sync through + stamp one Stripe account)
 */
class SyncStripePayouts extends Command
{
    protected $signature = 'stripe:sync-payouts
        {--payout= : Re-sync a single payout by its Stripe id (po_xxx)}
        {--no-transactions : Skip balance-transaction backfill}
        {--days= : Only sync payouts created in the last N days}
        {--account= : Stripe account id to sync through (stamps rows with it)}';

    protected $description = 'Backfill of Stripe payouts + balance transactions into local tables.';

    public function handle(StripePayoutSyncService $sync, StripeAccountResolver $accounts): int
    {
        if ($accountId = $this->option('account')) {
            $account = StripeAccount::find($accountId);

            if (! $account) {
                $this->error("Stripe account [{$accountId}] not found.");

                return self::FAILURE;
            }

            $sync = new StripePayoutSyncService($accounts->clientFor($account), $account);
            $this->info("Syncing through {$account->display_name}.");

            return $this->runSync($sync);
        }

        // No account narrowed: cover the legacy pool plus every real account.
        // The legacy account row shares the global keys, so it is covered by
        // the legacy pass and skipped here to avoid syncing it twice.
        $totalPayouts = 0;
        $totalTransactions = 0;

        foreach (StripeAccount::where('status', 'active')->where('is_legacy', false)->orderBy('id')->get() as $account) {
            try {
                $accountSync = new StripePayoutSyncService($accounts->clientFor($account), $account);
                [$payouts, $transactions] = $this->syncCounts($accountSync);
                $this->info("{$account->display_name}: {$payouts} payout(s), {$transactions} transaction(s).");
                $totalPayouts += $payouts;
                $totalTransactions += $transactions;
            } catch (\Throwable $e) {
                $this->warn("{$account->display_name}: skipped ({$e->getMessage()}).");
            }
        }

        try {
            [$payouts, $transactions] = $this->syncCounts($sync);
            $this->info("Legacy pool: {$payouts} payout(s), {$transactions} transaction(s).");
            $totalPayouts += $payouts;
            $totalTransactions += $transactions;
        } catch (\Throwable $e) {
            $this->warn("Legacy pool: skipped ({$e->getMessage()}).");
        }

        $this->info("Done. {$totalPayouts} payout(s), {$totalTransactions} balance transaction(s).");
        $this->line('Webhooks will keep these tables up to date from here.');

        return self::SUCCESS;
    }

    private function runSync(StripePayoutSyncService $sync): int
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

        [$payouts, $transactions] = $this->syncCounts($sync);

        $this->info("Done. {$payouts} payout(s), {$transactions} balance transaction(s).");
        $this->line('Webhooks will keep these tables up to date from here.');

        return self::SUCCESS;
    }

    /**
     * @return array{int, int}
     */
    private function syncCounts(StripePayoutSyncService $sync): array
    {
        $createdAfter = null;

        if ($days = $this->option('days')) {
            $createdAfter = now()->subDays((int) $days)->timestamp;
            $this->info("Syncing payouts from the last {$days} day(s)…");
        } else {
            $this->info('Syncing all payouts from Stripe…');
        }

        return $sync->syncAll(
            withTransactions: ! $this->option('no-transactions'),
            createdAfter: $createdAfter,
        );
    }
}
