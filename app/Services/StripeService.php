<?php

namespace App\Services;

use App\Models\Client;
use App\Models\StripeAccount;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeService
{
    public function __construct(
        private StripeAccountResolver $accounts,
        private ?StripeAccount $account = null,
    ) {}

    /** Bind this service to one account (or null for the legacy global keys). */
    public function forAccount(?StripeAccount $account): self
    {
        return new self($this->accounts, $account);
    }

    private function client(?StripeAccount $override = null): StripeClient
    {
        return $this->accounts->clientFor($override ?? $this->account);
    }

    public function createSetupIntent(Client $model, ?StripeAccount $account = null): array
    {
        $account ??= $this->account ?? $this->accounts->forClient($model);
        $client = $this->client($account);

        if (! $model->stripe_customer_id) {
            $customer = $client->customers->create([
                'name' => $model->company_name,
                'email' => $model->billing_email,
                'metadata' => ['client_id' => $model->id],
            ]);

            $model->update(['stripe_customer_id' => $customer->id]);
        }

        $setupIntent = $client->setupIntents->create([
            'customer' => $model->stripe_customer_id,
            'payment_method_types' => ['au_becs_debit'],
            'metadata' => ['client_id' => $model->id],
        ]);

        return [
            'client_secret' => $setupIntent->client_secret,
            'setup_intent_id' => $setupIntent->id,
        ];
    }

    public function chargeClient(Client $model, int $amountCents, string $description, ?StripeAccount $account = null): PaymentIntent
    {
        if (! $model->stripe_payment_method_id || $model->mandate_status !== 'active') {
            throw new \Exception('Client does not have an active mandate.');
        }

        $account ??= $this->account ?? $this->accounts->forClient($model);

        return $this->client($account)->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'aud',
            'customer' => $model->stripe_customer_id,
            'payment_method' => $model->stripe_payment_method_id,
            'payment_method_types' => ['au_becs_debit'],
            'confirm' => true,
            'description' => $description,
        ]);
    }
}
