<?php

namespace App\Services;

use App\Models\DirectDebitPayment;

/**
 * Thin wrapper around your actual DD gateway (Ezidebit, GoCardless, Stripe, etc.).
 *
 * Replace the body of charge() with your gateway's SDK or HTTP call.
 * The contract: return ['gateway_payment_id' => string, 'batch_id' => ?string]
 * on success, or throw on failure.
 */
class DirectDebitGatewayService
{
    public function charge(DirectDebitPayment $ddPayment): array
    {
        // -----------------------------------------------------------------
        // Example using a hypothetical gateway HTTP API.
        // Swap this out for your real gateway (Ezidebit, GoCardless, etc.)
        // -----------------------------------------------------------------

        // $response = Http::withToken(config('services.gateway.key'))
        //     ->post('https://api.yourgateway.com/payments', [
        //         'amount'      => (int) ($ddPayment->amount * 100), // cents
        //         'currency'    => strtolower($ddPayment->currency_code),
        //         'reference'   => $ddPayment->our_reference,
        //         'customer_id' => $ddPayment->client->gateway_customer_id,
        //     ]);
        //
        // if ($response->failed()) {
        //     throw new \RuntimeException($response->json('error.message') ?? 'Gateway error');
        // }
        //
        // return [
        //     'gateway_payment_id' => $response->json('id'),
        //     'batch_id'           => $response->json('batch_id'),
        // ];

        throw new \RuntimeException(
            'DirectDebitGatewayService::charge() is not implemented. ' .
            'Replace this stub with your gateway integration.'
        );
    }
}
