<?php

namespace App\Console\Commands;

use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SyncStripeBecsCustomers extends Command
{
    protected $signature   = 'stripe:sync-becs-customers';
    protected $description = 'Sync Stripe customers and their BECS payment methods into the local database.';

    public function handle(): int
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $this->info('Syncing BECS customers from Stripe...');

        $customerParams  = ['limit' => 100];
        $hasMoreCustomers = true;
        $synced           = 0;

        while ($hasMoreCustomers) {
            $customerPage = $stripe->customers->all($customerParams);

            foreach ($customerPage->data as $stripeCustomer) {
                $synced += $this->syncCustomerBecsPaymentMethods($stripe, $stripeCustomer);
            }

            $hasMoreCustomers = $customerPage->has_more;

            if ($hasMoreCustomers) {
                $customerParams['starting_after'] = $customerPage->data[count($customerPage->data) - 1]->id;
            }
        }

        $this->info("Sync complete. {$synced} BECS payment method(s) processed.");

        return self::SUCCESS;
    }

    /** Lists and upserts all BECS payment methods for a single Stripe customer. */
    private function syncCustomerBecsPaymentMethods(StripeClient $stripe, object $stripeCustomer): int
    {
        if ($stripeCustomer->deleted ?? false) {
            return 0;
        }

        $pmParams   = ['type' => 'au_becs_debit', 'limit' => 100];
        $hasMorePms = true;
        $count      = 0;

        // Resolve the customer's default payment method ID
        $defaultPmId = is_string($stripeCustomer->invoice_settings->default_payment_method ?? null)
            ? $stripeCustomer->invoice_settings->default_payment_method
            : ($stripeCustomer->invoice_settings->default_payment_method->id ?? null);

        // Only upsert the local customer record if they have at least one BECS method
        $localCustomer = null;

        while ($hasMorePms) {
            $pmPage = $stripe->customers->allPaymentMethods($stripeCustomer->id, $pmParams);

            if (! empty($pmPage->data)) {
                // Upsert the customer once we know they have BECS methods
                if (! $localCustomer) {
                    $localCustomer = StripeCustomer::updateOrCreate(
                        ['stripe_customer_id' => $stripeCustomer->id],
                        [
                            'name'                      => $stripeCustomer->name,
                            'email'                     => $stripeCustomer->email,
                            'default_payment_method_id' => $defaultPmId,
                            'stripe_data'               => $stripeCustomer->toArray(),
                            'last_synced_at'            => now(),
                        ]
                    );
                }

                foreach ($pmPage->data as $pm) {
                    StripePaymentMethod::updateOrCreate(
                        ['stripe_payment_method_id' => $pm->id],
                        [
                            'stripe_customer_id'  => $localCustomer->id,
                            'type'                => $pm->type,
                            'last4'               => $pm->au_becs_debit->last4 ?? null,
                            'account_holder_name' => $pm->billing_details->name ?? null,
                            'is_default'          => $pm->id === $defaultPmId,
                            'status'              => 'active',
                            'stripe_data'         => $pm->toArray(),
                            'last_synced_at'      => now(),
                        ]
                    );

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
}
