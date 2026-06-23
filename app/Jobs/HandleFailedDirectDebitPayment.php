<?php

namespace App\Jobs;

use App\Exceptions\XeroAuthException;
use App\Models\DirectDebitPayment;
use App\Models\XeroTenant;
use App\Notifications\DirectDebitFailedAdminNotification;
use App\Notifications\DirectDebitFailedCustomerNotification;
use App\Services\XeroService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;

class HandleFailedDirectDebitPayment implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 120, 240];
    }

    public function __construct(public int $ddPaymentId) {}

    public function handle(XeroService $xero): void
    {
        $ddPayment = DirectDebitPayment::with([
            'invoice',
            'invoice.client',
            'invoice.tenant',
            'invoice.tenant.connection',
        ])->findOrFail($this->ddPaymentId);

        $invoice = $ddPayment->invoice;
        $client  = $invoice->client;
        $tenant  = $invoice->tenant;

        $adminEmail = 'kapils@allinit.com.au';

        // ── 1. Notify admin ────────────────────────────────────────────────
        if ($adminEmail) {
            try {
                Notification::route('mail', $adminEmail)
                    ->notify(new DirectDebitFailedAdminNotification($ddPayment));
            } catch (\Throwable $e) {
                Log::error('Failed DD: admin notification failed', [
                    'dd_payment_id' => $ddPayment->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        // ── 2. Notify customer ─────────────────────────────────────────────
        if ($client?->billing_email) {
            try {
                $client->notify(new DirectDebitFailedCustomerNotification($ddPayment));
            } catch (\Throwable $e) {
                Log::error('Failed DD: customer notification failed', [
                    'dd_payment_id' => $ddPayment->id,
                    'client_id'     => $client->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        if (! $tenant?->connection) {
            Log::warning('Failed DD: no Xero connection — skipping fee invoice', [
                'dd_payment_id' => $ddPayment->id,
            ]);
            return;
        }

        try {
            $this->createFailedDirectDebitFeeInvoice($xero, $ddPayment, $tenant);
        } catch (XeroAuthException $e) {
            Log::error('Failed DD: Xero auth error, skipping invoice creation', [
                'dd_payment_id' => $ddPayment->id,
                'reason'        => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    private function createFailedDirectDebitFeeInvoice(
        XeroService        $xero,
        DirectDebitPayment $ddPayment,
        XeroTenant         $tenant,
    ): void {
        $connection      = $xero->refreshToken($tenant->connection);
        $originalInvoice = $ddPayment->invoice;

        $feeAmount = config('services.xero.direct_debit_failure_fee', 10.00);

        $payload = [
            'Type'         => 'ACCREC',
            'Status'       => 'AUTHORISED',
            'Contact'      => [
                'ContactID' => $originalInvoice->xero_contact_xero_id,
            ],
            'Date'         => now()->format('Y-m-d'),
            'DueDate'      => now()->format('Y-m-d'),
            'Reference'    => 'Direct Debit Failure Fee For ' .
                ($originalInvoice->xero_invoice_number ?? $originalInvoice->xero_invoice_id) . ')',
            'CurrencyCode' => 'AUD',
            'LineItems'    => [
                [
                    'Description' => 'Direct Debit Failed Payment Fee',
                    'Quantity'    => 1,
                    'UnitAmount'  => $feeAmount,
                    'AccountCode' => config('services.xero.default_account_code', '200'),
                ],
            ],
        ];

        $response = Http::withToken($connection->access_token)
            ->withHeaders([
                'Xero-tenant-id' => $tenant->tenant_id,
                'Accept'         => 'application/json',
            ])
            ->put('https://api.xero.com/api.xro/2.0/Invoices', [
                'Invoices' => [$payload],
            ]);

        if ($response->failed()) {
            Log::error('Failed DD: Xero fee invoice creation failed', [
                'dd_payment_id' => $ddPayment->id,
                'status'        => $response->status(),
                'body'          => $response->body(),
            ]);

            throw new \RuntimeException(
                "Xero failed DD fee invoice creation failed [{$response->status()}]"
            );
        }

        $created = $response->json('Invoices.0', []);

//        $ddPayment->update([
//            'xero_invoice_id' => $created['InvoiceID'] ?? null,
//        ]);

        Log::info('Failed DD: fee invoice created', [
            'dd_payment_id'   => $ddPayment->id,
            'xero_invoice_id' => $created['InvoiceID'] ?? null,
            'invoice_number'  => $created['InvoiceNumber'] ?? null,
        ]);
    }

    private function buildLineItems(): array
    {
        // Optional helper if you later expand fee logic
        return [];
    }
}
