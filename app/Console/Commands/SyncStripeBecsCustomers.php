<?php

namespace App\Console\Commands;

use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Services\StripeAccountResolver;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SyncStripeBecsCustomers extends Command
{
    protected $signature = 'stripe:sync-becs-customers
        {--account= : Only sync customers through this Stripe account id}';

    protected $description = 'Sync Stripe customers and their BECS payment methods into the local database.';

    public function handle(StripeAccountResolver $accounts): int
    {
        if ($accountId = $this->option('account')) {
            $account = StripeAccount::find($accountId);

            if (! $account) {
                $this->error("Stripe account [{$accountId}] not found.");

                return self::FAILURE;
            }

            $targets = collect([$account]);
            $includeLegacy = false;
        } else {
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
                $n = $this->syncThrough($accounts->clientFor($account), $account);
                $this->info("{$account->display_name}: {$n} BECS payment method(s).");
                $synced += $n;
            } catch (\Throwable $e) {
                $this->warn("{$account->display_name}: skipped ({$e->getMessage()}).");
            }
        }

        if ($includeLegacy) {
            try {
                $n = $this->syncThrough(new StripeClient(config('services.stripe.secret')), null);
                $this->info("Legacy pool: {$n} BECS payment method(s).");
                $synced += $n;
            } catch (\Throwable $e) {
                $this->warn("Legacy pool: skipped ({$e->getMessage()}).");
            }
        }

        $this->info("Sync complete. {$synced} BECS payment method(s) processed.");

        return self::SUCCESS;
    }

    private function syncThrough(StripeClient $stripe, ?StripeAccount $account): int
    {
        $customerParams = ['limit' => 100];
        $hasMoreCustomers = true;
        $synced = 0;

        while ($hasMoreCustomers) {
            $customerPage = $stripe->customers->all($customerParams);

            foreach ($customerPage->data as $stripeCustomer) {
                $synced += $this->syncCustomerBecsPaymentMethods($stripe, $stripeCustomer, $account);
            }

            $hasMoreCustomers = $customerPage->has_more;

            if ($hasMoreCustomers) {
                $customerParams['starting_after'] = $customerPage->data[count($customerPage->data) - 1]->id;
            }
        }

        return $synced;
    }

    /** Lists and upserts all BECS payment methods for a single Stripe customer. */
    private function syncCustomerBecsPaymentMethods(StripeClient $stripe, object $stripeCustomer, ?StripeAccount $account): int
    {
        if ($stripeCustomer->deleted ?? false) {
            return 0;
        }

        $pmParams = ['type' => 'au_becs_debit', 'limit' => 100];
        $hasMorePms = true;
        $count = 0;

        // Resolve the customer's default payment method ID
        $defaultPmId = is_string($stripeCustomer->invoice_settings->default_payment_method ?? null)
            ? $stripeCustomer->invoice_settings->default_payment_method
            : ($stripeCustomer->invoice_settings->default_payment_method->id ?? null);

        // Only upsert the local customer record if they have at least one BECS method
        $localCustomer = null;

        while ($hasMorePms) {
            $pmPage = $stripe->customers->allPaymentMethods($stripeCustomer->id, $pmParams);

            if (! empty($pmPage->data)) {
                if (! $localCustomer) {
                    $localCustomer = $this->localCustomer($stripeCustomer, $defaultPmId, $account);
                }

                foreach ($pmPage->data as $pm) {
                    $this->localPaymentMethod($pm, $localCustomer, $defaultPmId, $account);

                    $count++;
                }
            }

            $hasMorePms = $pmPage->has_more;

            if ($hasMorePms) {
                $pmParams['starting_after'] = $pmPage->data[count($pmPage->data) - 1]->id;
            }
        }

        return $count;
    }

    /**
     * Find the local customer for this Stripe id + account, claiming an
     * unstamped (legacy pool) row when one exists so we never duplicate it.
     * Rows stamped to a different account are never touched.
     */
    private function localCustomer(object $stripeCustomer, ?string $defaultPmId, ?StripeAccount $account): StripeCustomer
    {
        $existing = $this->findCustomer($stripeCustomer->id, $account);

        $values = [
            'name' => $stripeCustomer->name,
            'email' => $stripeCustomer->email,
            'default_payment_method_id' => $defaultPmId,
            'stripe_data' => $stripeCustomer->toArray(),
            'last_synced_at' => now(),
        ];

        if ($existing) {
            if ($existing->stripe_account_id === null && $account) {
                $values['stripe_account_id'] = $account->id;
            }

            $existing->update($values);

            return $existing->fresh();
        }

        if ($account) {
            $values['stripe_account_id'] = $account->id;
        }

        return StripeCustomer::create(array_merge(
            ['stripe_customer_id' => $stripeCustomer->id],
            $values
        ));
    }

    private function findCustomer(string $stripeCustomerId, ?StripeAccount $account): ?StripeCustomer
    {
        if ($account) {
            return StripeCustomer::where('stripe_customer_id', $stripeCustomerId)
                ->where('stripe_account_id', $account->id)
                ->first()
                ?? StripeCustomer::where('stripe_customer_id', $stripeCustomerId)
                    ->whereNull('stripe_account_id')
                    ->first();
        }

        // Legacy pass keeps the historical behaviour: match by Stripe id
        // alone so pre-multi-tenant rows update in place.
        return StripeCustomer::where('stripe_customer_id', $stripeCustomerId)->first();
    }

    private function localPaymentMethod(object $pm, StripeCustomer $localCustomer, ?string $defaultPmId, ?StripeAccount $account): void
    {
        $existing = $account
            ? StripePaymentMethod::where('stripe_payment_method_id', $pm->id)
                ->where('stripe_account_id', $account->id)
                ->first()
                ?? StripePaymentMethod::where('stripe_payment_method_id', $pm->id)
                    ->whereNull('stripe_account_id')
                    ->first()
            : StripePaymentMethod::where('stripe_payment_method_id', $pm->id)->first();

        $values = [
            'stripe_customer_id' => $localCustomer->id,
            'type' => $pm->type,
            'last4' => $pm->au_becs_debit->last4 ?? null,
            'account_holder_name' => $pm->billing_details->name ?? null,
            'is_default' => $pm->id === $defaultPmId,
            'status' => 'active',
            'stripe_data' => $pm->toArray(),
            'last_synced_at' => now(),
        ];

        if ($existing) {
            if ($existing->stripe_account_id === null && $account) {
                $values['stripe_account_id'] = $account->id;
            }

            $existing->update($values);

            return;
        }

        if ($account) {
            $values['stripe_account_id'] = $account->id;
        }

        StripePaymentMethod::create(array_merge(
            ['stripe_payment_method_id' => $pm->id],
            $values
        ));
    }
}
