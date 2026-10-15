<?php

namespace App\Services;

use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use Stripe\StripeClient;

class StripeBecsCustomerSyncService
{
    public function __construct(private StripeAccountResolver $accounts) {}

    /**
     * Synchronously sync all BECS payment methods through one credential set.
     */
    public function syncThrough(StripeClient $stripe, ?StripeAccount $account): int
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

    /**
     * Sync a single already-fetched Stripe customer payload and its BECS methods.
     *
     * Accepts the raw Stripe customer array (as queued from the fan-out job)
     * so the child job does not need to re-fetch the customer itself.
     */
    public function syncCustomerArray(array $stripeCustomerData, ?StripeAccount $account, ?StripeClient $client = null): int
    {
        $stripe = $client ?? $this->accounts->clientFor($account);

        if (($stripeCustomerData['deleted'] ?? false) === true) {
            return 0;
        }

        $stripeCustomerId = $stripeCustomerData['id'] ?? null;

        if (! is_string($stripeCustomerId) || $stripeCustomerId === '') {
            return 0;
        }

        $defaultPmId = $this->defaultPaymentMethodId($stripeCustomerData);

        $localCustomer = null;
        $count = 0;
        $pmParams = ['type' => 'au_becs_debit', 'limit' => 100];
        $hasMorePms = true;

        while ($hasMorePms) {
            $pmPage = $stripe->customers->allPaymentMethods($stripeCustomerId, $pmParams);

            if (! empty($pmPage->data)) {
                if (! $localCustomer) {
                    $localCustomer = $this->localCustomerArray($stripeCustomerData, $defaultPmId, $account);
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

    /** Lists and upserts all BECS payment methods for a single Stripe customer. */
    public function syncCustomerBecsPaymentMethods(StripeClient $stripe, object $stripeCustomer, ?StripeAccount $account): int
    {
        if ($stripeCustomer->deleted ?? false) {
            return 0;
        }

        return $this->syncCustomerArray($stripeCustomer->toArray(), $account, $stripe);
    }

    private function defaultPaymentMethodId(array $stripeCustomerData): ?string
    {
        $default = $stripeCustomerData['invoice_settings']['default_payment_method'] ?? null;

        if (is_string($default)) {
            return $default;
        }

        if (is_array($default) && isset($default['id']) && is_string($default['id'])) {
            return $default['id'];
        }

        return null;
    }

    /**
     * Find the local customer for this Stripe id + account, claiming an
     * unstamped (legacy pool) row when one exists so we never duplicate it.
     * Rows stamped to a different account are never touched.
     */
    public function localCustomer(object $stripeCustomer, ?string $defaultPmId, ?StripeAccount $account): StripeCustomer
    {
        return $this->localCustomerArray($stripeCustomer->toArray(), $defaultPmId, $account);
    }

    private function localCustomerArray(array $stripeCustomerData, ?string $defaultPmId, ?StripeAccount $account): StripeCustomer
    {
        $existing = $this->findCustomer($stripeCustomerData['id'], $account);

        $values = [
            'name' => $stripeCustomerData['name'] ?? null,
            'email' => $stripeCustomerData['email'] ?? null,
            'default_payment_method_id' => $defaultPmId,
            'stripe_data' => $stripeCustomerData,
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
            ['stripe_customer_id' => $stripeCustomerData['id']],
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

    private function localPaymentMethod(object|array $pm, StripeCustomer $localCustomer, ?string $defaultPmId, ?StripeAccount $account): void
    {
        $pmId = is_array($pm) ? ($pm['id'] ?? null) : ($pm->id ?? null);
        $pmType = is_array($pm) ? ($pm['type'] ?? null) : ($pm->type ?? null);
        $last4 = is_array($pm) ? ($pm['au_becs_debit']['last4'] ?? null) : ($pm->au_becs_debit->last4 ?? null);
        $holderName = is_array($pm) ? ($pm['billing_details']['name'] ?? null) : ($pm->billing_details->name ?? null);
        $pmArray = is_array($pm) ? $pm : $pm->toArray();

        $existing = $account
            ? StripePaymentMethod::where('stripe_payment_method_id', $pmId)
                ->where('stripe_account_id', $account->id)
                ->first()
                ?? StripePaymentMethod::where('stripe_payment_method_id', $pmId)
                    ->whereNull('stripe_account_id')
                    ->first()
            : StripePaymentMethod::where('stripe_payment_method_id', $pmId)->first();

        $values = [
            'stripe_customer_id' => $localCustomer->id,
            'type' => $pmType,
            'last4' => $last4,
            'account_holder_name' => $holderName,
            'is_default' => $pmId === $defaultPmId,
            'status' => 'active',
            'stripe_data' => $pmArray,
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
            ['stripe_payment_method_id' => $pmId],
            $values
        ));
    }
}
