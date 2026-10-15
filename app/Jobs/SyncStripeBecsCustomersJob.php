<?php

namespace App\Jobs;

use App\Models\StripeAccount;
use App\Services\StripeAccountResolver;
use App\Services\StripeBecsCustomerSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Syncs one Stripe credential set (a single account, or the legacy pool
 * when the account id is null) inline.
 *
 * One job per account keeps Stripe API calls sequential within each job,
 * staying under rate limits while still keeping thousands of customers
 * off the HTTP request. Callers dispatch one job per account.
 */
class SyncStripeBecsCustomersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 3600;

    public function __construct(public ?int $stripeAccountId = null) {}

    public function handle(StripeAccountResolver $accounts, StripeBecsCustomerSyncService $sync): void
    {
        $account = $this->stripeAccountId !== null
            ? StripeAccount::find($this->stripeAccountId)
            : null;

        if ($this->stripeAccountId !== null && ! $account) {
            Log::warning('SyncStripeBecsCustomersJob: unknown account, skipping.', [
                'stripe_account_id' => $this->stripeAccountId,
            ]);

            return;
        }

        try {
            $count = $sync->syncThrough($accounts->clientFor($account), $account);
        } catch (\Throwable $e) {
            Log::warning('SyncStripeBecsCustomersJob: sync failed, will retry.', [
                'stripe_account_id' => $account?->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        Log::info('SyncStripeBecsCustomersJob: sync complete.', [
            'stripe_account_id' => $account?->id,
            'becs_payment_methods' => $count,
        ]);
    }
}
