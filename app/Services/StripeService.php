<?php
namespace App\Services;

use Stripe\StripeClient;
use App\Models\Client;

class StripeService
{
    protected StripeClient $client; // ← rename to avoid confusion

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function createSetupIntent(Client $model): array
    {
        if (!$model->stripe_customer_id) {
            $customer = $this->client->customers->create([
                'name'     => $model->company_name,
                'email'    => $model->billing_email,
                'metadata' => ['client_id' => $model->id],
            ]);

            $model->update(['stripe_customer_id' => $customer->id]);
        }

        $setupIntent = $this->client->setupIntents->create([
            'customer'             => $model->stripe_customer_id,
            'payment_method_types' => ['au_becs_debit'],
            'metadata'             => ['client_id' => $model->id],
        ]);

        return [
            'client_secret'   => $setupIntent->client_secret,
            'setup_intent_id' => $setupIntent->id,
        ];
    }

    public function chargeClient(Client $model, int $amountCents, string $description): \Stripe\PaymentIntent
    {
        if (!$model->stripe_payment_method_id || $model->mandate_status !== 'active') {
            throw new \Exception('Client does not have an active mandate.');
        }

        return $this->client->paymentIntents->create([
            'amount'               => $amountCents,
            'currency'             => 'aud',
            'customer'             => $model->stripe_customer_id,
            'payment_method'       => $model->stripe_payment_method_id,
            'payment_method_types' => ['au_becs_debit'],
            'confirm'              => true,
            'description'          => $description,
        ]);
    }
}
