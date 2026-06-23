<?php


namespace App\Services;

use App\Models\DirectDebitPayment;
use App\Models\XeroConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroPaymentWriteBackService
{
    private const BASE_URL = 'https://api.xero.com/api.xro/2.0';

    public function __construct(
        private XeroService $xero,
    )
    {
    }

    /**
     * POST a payment to Xero for a settled DirectDebitPayment.
     *
     * Returns the Xero PaymentID (UUID) on success.
     * Throws on API error.
     */
    public function post(DirectDebitPayment $ddPayment): string
    {
        $invoice = $ddPayment->invoice;
        $tenant = $invoice->tenant;
        $connection = $this->xero->refreshToken($tenant->connection);

        $bankAccountId = $tenant->dd_bank_account_id
            ?? config('xero.dd_bank_account_id');

        if (!$bankAccountId) {
            throw new \RuntimeException(
                "No DD bank account configured for tenant [{$tenant->id}]"
            );
        }

        $payload = [
            'Invoice' => [
                'InvoiceID' => $ddPayment->xero_invoice_xero_id,
            ],
            'Account' => [
                'AccountID' => $bankAccountId,
            ],
            'Date' => $ddPayment->settled_at->format('Y-m-d'),
            'Amount' => (float) $ddPayment->amount,
            'Reference' => $this->buildReference($ddPayment),
            'IsReconciled' => false,
        ];

        if ($invoice->currency_rate && $invoice->currency_rate != 1.0) {
            $payload['CurrencyRate'] = (float)$invoice->currency_rate;
        }

        Log::info('XeroPaymentWritebackService: posting payment to Xero', [
            'dd_payment_id' => $ddPayment->id,
            'xero_invoice_id' => $ddPayment->xero_invoice_xero_id,
            'amount' => $ddPayment->amount,
        ]);

        $response = Http::withToken($connection->access_token)
            ->withHeaders([
                'Xero-tenant-id' => $tenant->tenant_id,
                'Idempotency-Key' => $this->idempotencyKey($ddPayment),
            ])
            ->put(self::BASE_URL . '/Payments', $payload);

        if ($response->failed()) {
            $body = $response->json();

            Log::error('XeroPaymentWritebackService: Xero API error', [
                'dd_payment_id' => $ddPayment->id,
                'status' => $response->status(),
                'body' => $body,
            ]);

            $message = $body['Detail'] ?? $body['Message'] ?? $response->body();
            throw new \RuntimeException("Xero payment POST failed: {$message}");
        }

        $payments = $response->json('Payments', []);

        if (empty($payments)) {
            throw new \RuntimeException('Xero returned empty Payments array — cannot confirm PaymentID');
        }

        $xeroPaymentId = $payments[0]['PaymentID'] ?? null;

        if (!$xeroPaymentId) {
            throw new \RuntimeException('Xero response missing PaymentID');
        }

        return $xeroPaymentId;
    }

    // -------------------------------------------------------------------------

    private function buildReference(DirectDebitPayment $ddPayment): string
    {
        // e.g. "DD INV-0042 2024-07-15"
        return implode(' ', array_filter([
            'DD',
            $ddPayment->xero_invoice_number,
            $ddPayment->settled_at?->format('Y-m-d'),
            $ddPayment->gateway_payment_id,
        ]));
    }

    /**
     * Stable idempotency key so a retry of this job won't double-post.
     * Based on the DirectDebitPayment's own ID — not the invoice — so that
     * a manual retry payment for the same invoice gets a fresh key.
     */
    private function idempotencyKey(DirectDebitPayment $ddPayment): string
    {
        return 'ddp-' . $ddPayment->id;
    }
}
