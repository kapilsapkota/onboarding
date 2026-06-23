<?php

namespace App\Http\Controllers;

use App\Jobs\HandleFailedDirectDebitPayment;
use App\Jobs\WriteXeroPayment;
use App\Models\DirectDebitPayment;
use App\Services\StripeBecsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(private StripeBecsService $stripe) {}

    public function __invoke(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('StripeWebhook: invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        Log::info('StripeWebhook: received event', ['type' => $event->type]);

        try {
            match ($event->type) {
                'payment_intent.succeeded'      => $this->handleSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handleFailed($event->data->object),
                'mandate.updated'               => $this->handleMandateUpdated($event->data->object),
                default                         => null,
            };
        } catch (\Throwable $e) {
            Log::error('StripeWebhook: unhandled exception in event handler', [
                'type'  => $event->type,
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------

    private function handleSucceeded(object $intent): void
    {
        $ddPayment = $this->findByIntent($intent);

        if (! $ddPayment) {
            return;
        }

        if ($ddPayment->status === 'settled') {
            Log::info('StripeWebhook: payment_intent.succeeded — already settled, skipping', [
                'id' => $ddPayment->id,
            ]);
            return;
        }

        $balanceTx = $this->stripe->getBalanceTransaction($intent['id']);

        $ddPayment->markSettled($balanceTx);
        $ddPayment->invoice->markPaymentSettled();

        Log::info('StripeWebhook: payment_intent.succeeded — marked settled', [
            'id'                => $ddPayment->id,
            'payment_intent_id' => $intent->id,
        ]);

        WriteXeroPayment::dispatch($ddPayment->id);
    }

    private function handleFailed(object $intent): void
    {
        $ddPayment = $this->findByIntent($intent);

        if (! $ddPayment) {
            return;
        }

        // Idempotency guard — Stripe can deliver the same event more than once.
        if ($ddPayment->status === 'failed') {
            Log::info('StripeWebhook: payment_intent.payment_failed — already failed, skipping', [
                'id' => $ddPayment->id,
            ]);
            return;
        }

        $lastError = $intent->last_payment_error;
        $reason    = $lastError?->message ?? 'Payment failed';
        $code      = $lastError?->code    ?? null;

        $ddPayment->markFailed($reason, $code);
        $ddPayment->invoice->markPaymentFailed($reason);

        Log::warning('StripeWebhook: payment_intent.payment_failed', [
            'id'                => $ddPayment->id,
            'payment_intent_id' => $intent->id,
            'code'              => $code,
            'reason'            => $reason,
        ]);

        // Hand off to a job — email delivery and Xero invoice creation are
        // too slow and fallible to run synchronously in the webhook handler.
        HandleFailedDirectDebitPayment::dispatch($ddPayment->id);
    }

    private function handleMandateUpdated(object $mandate): void
    {
        if (($mandate->status ?? null) !== 'inactive') {
            return;
        }

        $paymentMethodId = is_string($mandate->payment_method)
            ? $mandate->payment_method
            : ($mandate->payment_method->id ?? null);

        if (! $paymentMethodId) {
            return;
        }

        \App\Models\Client::where('stripe_payment_method_id', $paymentMethodId)
            ->update([
                'stripe_payment_method_id' => null,
                'mandate_status'           => 'inactive',
            ]);

        Log::warning('StripeWebhook: mandate.updated — inactive, cleared from client', [
            'payment_method_id' => $paymentMethodId,
        ]);
    }

    // -------------------------------------------------------------------------

    private function findByIntent(object $intent): ?DirectDebitPayment
    {
        $ddPaymentId = $intent->metadata['dd_payment_id'] ?? null;

        if ($ddPaymentId) {
            $ddPayment = DirectDebitPayment::with([
                'invoice',
                'invoice.client',
                'invoice.tenant',
                'invoice.tenant.connection',
            ])->find((int) $ddPaymentId);

            if ($ddPayment) {
                return $ddPayment;
            }
        }

        return DirectDebitPayment::with([
            'invoice',
            'invoice.client',
            'invoice.tenant',
            'invoice.tenant.connection',
        ])
            ->where('gateway_payment_id', $intent->id)
            ->first();
    }
}
