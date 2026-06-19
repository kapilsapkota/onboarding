<?php

namespace App\Services;

use App\Models\Client;
use App\Models\DirectDebitPayment;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeBecsService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create and confirm an off-session PaymentIntent for a BECS direct debit.
     *
     * The client must already have a saved au_becs_debit PaymentMethod ID
     * stored on their record (pm_xxxxxxxxxxxx).
     *
     * BECS is a delayed-notification method: Stripe immediately returns status
     * "processing" — success/failure arrives 1-2 business days later via webhook.
     *
     * Returns the Stripe PaymentIntent ID (pi_xxxx).
     *
     * @throws ApiErrorException|\RuntimeException
     */
    public function charge(DirectDebitPayment $ddPayment): string
    {
        $client = Client::find($ddPayment->client_id);
        if (! $client) {
            throw new \RuntimeException(
                "Client [{$client->id}] not found."
            );
        }

        if (! $client->stripe_payment_method_id) {
            throw new \RuntimeException(
                "Client [{$client->id}] has no Stripe BECS payment method on file."
            );
        }

        // Amount must be in cents
        $amountCents = (int) round($ddPayment->amount * 100);

        $intent = $this->stripe->paymentIntents->create([
            'amount'               => $amountCents,
            'currency'             => strtolower($ddPayment->currency_code),
            'customer'             => $client->stripe_customer_id,
            'payment_method'       => $client->stripe_payment_method_id,
            'payment_method_types' => ['au_becs_debit'],
            'confirm'              => true,
            'off_session'          => true,
            'metadata'             => [
                'dd_payment_id'       => $ddPayment->id,
                'xero_invoice_number' => $ddPayment->xero_invoice_number,
                'our_reference'       => $ddPayment->our_reference,
            ],
            'description' => "Direct debit for {$ddPayment->xero_invoice_number}",
        ], [
            'idempotency_key' => 'ddp-' . $ddPayment->id,
        ]);

        if (! in_array($intent->status, ['processing', 'succeeded'])) {
            throw new \RuntimeException(
                "Unexpected Stripe PaymentIntent status [{$intent->status}] for ddp:{$ddPayment->id}"
            );
        }

        return $intent->id;
    }

    public function getBalanceTransaction(string $paymentIntentId): array
    {
        $intent = $this->stripe->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['latest_charge.balance_transaction'],
        ]);

        $bt = $intent->latest_charge?->balance_transaction;

        if (! $bt) {
            Log::warning('StripeBecsService: no balance transaction on intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return [];
        }

        return [
            'gross'        => $bt->amount / 100,
            'fee'          => $bt->fee / 100,
            'net'          => $bt->net / 100,
            'currency'     => strtoupper($bt->currency),
            'stripe_bt_id' => $bt->id,
        ];
    }
}
