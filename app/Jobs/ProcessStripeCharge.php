<?php

namespace App\Jobs;

use App\Models\StripeChargeBatchItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class ProcessStripeCharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(private StripeChargeBatchItem $item) {}

    public function handle(): void
    {
        // Reload fresh to avoid stale status
        $this->item->refresh();

        if ($this->item->status !== 'pending') {
            return;
        }

        if ($this->item->stripe_payment_intent_id) {
            return;
        }

        $this->item->update(['status' => 'processing']);

        try {
            $customer      = $this->item->stripeCustomer;
            $paymentMethod = $this->item->stripePaymentMethod;

            $stripe = new StripeClient(config('services.stripe.secret'));

            $payload = [
                'amount'               => $this->item->amount,
                'currency'             => $this->item->currency,
                'customer'             => $customer->stripe_customer_id,
                'payment_method'       => $paymentMethod->stripe_payment_method_id,
                'payment_method_types' => ['au_becs_debit'],
                'confirm'              => true,
            ];

            if (! empty($this->item->description)) {
                $payload['description'] = $this->item->description;
            }

            $intent = $stripe->paymentIntents->create(
                $payload,
                ['idempotency_key' => $this->item->idempotencyKey()]
            );

            $this->item->update([
                'stripe_payment_intent_id' => $intent->id,
                'stripe_data'              => $intent->toArray(),
                'status'                   => $this->mapIntentStatus($intent->status),
                'processed_at'             => now(),
            ]);

        } catch (ApiErrorException $e) {
            $this->item->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at'  => now(),
            ]);
        }

        $this->item->batch->recalculateStatus();
    }

    /** Maps a Stripe PaymentIntent status to a local item status. */
    private function mapIntentStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded'                        => 'succeeded',
            'processing', 'requires_action'    => 'processing',
            default                            => 'failed',
        };
    }

    public function failed(\Throwable $e): void
    {
        $this->item->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
            'processed_at'  => now(),
        ]);

        $this->item->batch->recalculateStatus();
    }
}
