<?php

namespace App\Jobs;

use App\Models\DirectDebitPayment;
use App\Services\StripeBecsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

/**
 * Submits one invoice's direct debit charge to Stripe BECS.
 *
 * BECS is async — Stripe returns "processing" immediately.
 * We store the PaymentIntent ID and wait for the webhook:
 *   payment_intent.succeeded  → WriteXeroPayment
 *   payment_intent.payment_failed → mark failed
 */
class ProcessSingleDirectDebit implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 1;
    public int $backoff = 300; // 5 min between retries

    public function __construct(public int $directDebitPaymentId) {}

    public function handle(StripeBecsService $stripe): void
    {
        $ddPayment = DirectDebitPayment::with(['invoice', 'client'])
            ->findOrFail($this->directDebitPaymentId);

        if (in_array($ddPayment->status, ['processing', 'settled', 'cancelled'])) {
            Log::info('ProcessSingleDirectDebit: skipping — already in non-pending state', [
                'id'     => $ddPayment->id,
                'status' => $ddPayment->status,
            ]);
            return;
        }

        try {
            $paymentIntentId = $stripe->charge($ddPayment);

            $ddPayment->markSubmitted(
                gatewayPaymentId: $paymentIntentId,
                batchId:          null,
            );
            $ddPayment->invoice->markPaymentInitiated('direct_debit', $paymentIntentId);

            Log::info('ProcessSingleDirectDebit: submitted to Stripe', [
                'id'               => $ddPayment->id,
                'payment_intent_id' => $paymentIntentId,
                'amount'           => $ddPayment->amount,
            ]);

        } catch (ApiErrorException $e) {
            $ddPayment->markFailed($e->getMessage(), (string) $e->getStripeCode());
            $ddPayment->invoice->markPaymentFailed($e->getMessage());

            Log::error('ProcessSingleDirectDebit: Stripe API error', [
                'id'          => $ddPayment->id,
                'stripe_code' => $e->getStripeCode(),
                'error'       => $e->getMessage(),
            ]);


            throw $e;

        } catch (\Throwable $e) {
            $ddPayment->markFailed($e->getMessage());
            $ddPayment->invoice->markPaymentFailed($e->getMessage());

            Log::error('ProcessSingleDirectDebit: unexpected error', [
                'id'    => $ddPayment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('ProcessSingleDirectDebit: permanently failed after all retries', [
            'id'    => $this->directDebitPaymentId,
            'error' => $e->getMessage(),
        ]);
    }
}
