<?php

namespace App\Jobs;

use App\Models\XeroInvoice;
use App\Models\DirectDebitPayment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled daily (e.g. 6am) to find all AUTHORISED AR invoices whose
 * due_date is today, create a DirectDebitPayment record for each one,
 * and dispatch a ProcessSingleDirectDebit job per invoice.
 *
 * Add to your scheduler in routes/console.php:
 *   Schedule::job(new ProcessDueDirectDebits)->dailyAt('06:00');
 */
class ProcessDueDirectDebitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $invoices = XeroInvoice::query()
            ->receivable()
            ->authorised()
            ->whereDate('due_date', today())
            ->where('amount_due', '>', 0)
            ->whereNotNull('client_id')
            ->where(function ($q) {
                $q->whereDoesntHave('directDebitPayments', function ($q2) {
                    $q2->whereIn('status', ['pending', 'processing', 'settled']);
                });
            })
            ->with(['tenant.connection', 'client'])
            ->get();

        Log::info('ProcessDueDirectDebits: found invoices to collect', [
            'count' => $invoices->count(),
            'date'  => today()->toDateString(),
        ]);

        foreach ($invoices as $invoice) {
            if (! $invoice->tenant?->connection) {
                Log::warning('ProcessDueDirectDebits: skipping invoice — no active Xero connection', [
                    'invoice_id'     => $invoice->id,
                    'xero_invoice_number' => $invoice->xero_invoice_number,
                ]);
                continue;
            }

            $ddPayment = DirectDebitPayment::create([
                'xero_invoice_id'      => $invoice->id,
                'xero_tenant_id'       => $invoice->xero_tenant_id,
                'client_id'            => $invoice->client_id,
                'xero_invoice_xero_id' => $invoice->xero_invoice_id,
                'xero_invoice_number'  => $invoice->xero_invoice_number,
                'amount'               => $invoice->amount_due,
                'currency_code'        => $invoice->currency_code ?? 'AUD',
                'payment_method'       => 'direct_debit',
                'our_reference'        => 'DD-' . $invoice->xero_invoice_number . '-' . today()->format('Ymd'),
                'status'               => 'pending',
                'initiated_at'         => now(),
            ]);

            ProcessSingleDirectDebit::dispatch($ddPayment->id);
        }
    }
}
