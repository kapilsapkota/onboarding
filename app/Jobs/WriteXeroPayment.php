<?php


namespace App\Jobs;

use App\Models\DirectDebitPayment;
use App\Services\XeroPaymentWritebackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Posts a settled DirectDebitPayment to Xero as a Payment record.
 *
 * Dispatched by StripeWebhookController when Stripe fires
 * payment_intent.succeeded — NOT immediately after gateway submission,
 * because BECS settlement takes 1-2 business days.
 *
 * Can also be dispatched manually to retry failed write-backs:
 *   DirectDebitPayment::awaitingXeroPostback()
 *       ->each(fn ($p) => WriteXeroPayment::dispatch($p->id));
 */
class WriteXeroPayment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;
    public int $backoff = 60;

    public function __construct(public int $directDebitPaymentId)
    {
    }

    public function handle(XeroPaymentWritebackService $service): void
    {
        $ddPayment = DirectDebitPayment::with(['invoice.tenant.connection'])
            ->findOrFail($this->directDebitPaymentId);

        if ($ddPayment->xero_payment_id !== null) {
            Log::info('WriteXeroPayment: already posted to Xero, skipping', [
                'id' => $ddPayment->id,
                'xero_payment_id' => $ddPayment->xero_payment_id,
            ]);
            return;
        }

        if ($ddPayment->status !== 'settled') {
            Log::warning('WriteXeroPayment: payment not yet settled, skipping', [
                'id' => $ddPayment->id,
                'status' => $ddPayment->status,
            ]);
            return;
        }

        try {
            $xeroPaymentId = $service->post($ddPayment);
            $ddPayment->markXeroPosted($xeroPaymentId);

            Log::info('WriteXeroPayment: payment posted to Xero', [
                'id' => $ddPayment->id,
                'xero_payment_id' => $xeroPaymentId,
            ]);

        } catch (\Throwable $e) {
            $ddPayment->markXeroPostFailed($e->getMessage());

            Log::error('WriteXeroPayment: failed to post to Xero', [
                'id' => $ddPayment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('WriteXeroPayment: permanently failed — manual intervention required', [
            'id' => $this->directDebitPaymentId,
            'error' => $e->getMessage(),
        ]);
    }
}
