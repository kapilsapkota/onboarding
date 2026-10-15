<?php

namespace App\Console\Commands;

use App\Jobs\SyncStripeBecsCustomersJob;
use App\Models\StripeAccount;
use App\Services\StripeAccountResolver;
use App\Services\StripeBecsCustomerSyncService;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SyncStripeBecsCustomers extends Command
{
    protected $signature = 'stripe:sync-becs-customers
        {--account= : Only sync customers through this Stripe account id}
        {--queue : Dispatch queued sync jobs instead of syncing inline}';

    protected $description = 'Sync Stripe customers and their BECS payment methods into the local database.';

    public function handle(StripeAccountResolver $accounts, StripeBecsCustomerSyncService $sync): int
    {
        if ($accountId = $this->option('account')) {
            $account = StripeAccount::find($accountId);

            if (! $account) {
                $this->error("Stripe account [{$accountId}] not found.");

                return self::FAILURE;
            }

            if ($this->option('queue')) {
                SyncStripeBecsCustomersJob::dispatch((int) $account->id);
                $this->info("Sync queued for {$account->display_name}.");

                return self::SUCCESS;
            }

            $targets = collect([$account]);
            $includeLegacy = false;
        } else {
            if ($this->option('queue')) {
                $accountIds = StripeAccount::where('status', 'active')
                    ->where('is_legacy', false)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                foreach ($accountIds as $id) {
                    SyncStripeBecsCustomersJob::dispatch($id);
                }

                SyncStripeBecsCustomersJob::dispatch(null);

                $total = count($accountIds) + 1;
                $this->info("Sync queued across all accounts ({$total} jobs).");

                return self::SUCCESS;
            }

            // Real accounts first; the legacy/global pass last. The legacy
            // account row shares the global keys, so it is covered by the
            // legacy pass and skipped here to avoid syncing it twice.
            $targets = StripeAccount::where('status', 'active')
                ->where('is_legacy', false)
                ->orderBy('id')
                ->get();
            $includeLegacy = true;
        }

        $synced = 0;

        foreach ($targets as $account) {
            try {
                $n = $sync->syncThrough($accounts->clientFor($account), $account);
                $this->info("{$account->display_name}: {$n} BECS payment method(s).");
                $synced += $n;
            } catch (\Throwable $e) {
                $this->warn("{$account->display_name}: skipped ({$e->getMessage()}).");
            }
        }

        if ($includeLegacy) {
            try {
                $n = $sync->syncThrough(new StripeClient(config('services.stripe.secret')), null);
                $this->info("Legacy pool: {$n} BECS payment method(s).");
                $synced += $n;
            } catch (\Throwable $e) {
                $this->warn("Legacy pool: skipped ({$e->getMessage()}).");
            }
        }

        $this->info("Sync complete. {$synced} BECS payment method(s) processed.");

        return self::SUCCESS;
    }
}
