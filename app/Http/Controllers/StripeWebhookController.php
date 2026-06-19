<?php

namespace App\Http\Controllers;

use App\Jobs\WriteXeroPayment;
use App\Models\DirectDebitPayment;
use App\Services\StripeBecsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Receives Stripe webhook events and routes them to the right action.
 *
 * Register in routes/api.php (outside the 'auth' middleware):
 *   Route::post('/webhooks/stripe', StripeWebhookController::class)
 *       ->name('webhooks.stripe');
 *
 * Add to config/app.php middleware exception list (or VerifyCsrfToken):
 *   '/api/webhooks/stripe'
 *
 * Stripe Dashboard → Developers → Webhooks → Add endpoint:
 *   URL: https://yourapp.com/api/webhooks/stripe
 *   Events to listen for:
 *     - payment_intent.succeeded
 *     - payment_intent.payment_failed
 *     - mandate.updated
 */
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

        match ($event->type) {
            'payment_intent.succeeded'       => $this->handleSucceeded($event->data->object),
            'payment_intent.payment_failed'  => $this->handleFailed($event->data->object),
            'mandate.updated'                => $this->handleMandateUpdated($event->data->object),
            default                          => null,
        };

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
            'id'               => $ddPayment->id,
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

        $lastError = $intent->last_payment_error;
        $reason    = $lastError?->message ?? 'Payment failed';
        $code      = $lastError?->code ?? null;

        $ddPayment->markFailed($reason, $code);
        $ddPayment->invoice->markPaymentFailed($reason);

        Log::warning('StripeWebhook: payment_intent.payment_failed', [
            'id'               => $ddPayment->id,
            'payment_intent_id' => $intent->id,
            'code'             => $code,
            'reason'           => $reason,
        ]);
    }

    private function handleMandateUpdated(object $mandate): void
    {
        // If a BECS mandate becomes inactive (customer cancelled with their bank),
        // we should flag the client so we don't attempt future debits.
        if (($mandate->status ?? null) !== 'inactive') {
            return;
        }

        $paymentMethodId = is_string($mandate->payment_method)
            ? $mandate->payment_method
            : ($mandate->payment_method->id ?? null);

        if (! $paymentMethodId) {
            return;
        }

        // Null out the payment method on the client so the next scheduled
        // debit is skipped and staff are alerted.
        \App\Models\Client::where('stripe_payment_method_id', $paymentMethodId)
            ->update([
                'stripe_payment_method_id' => null,
                'stripe_mandate_status'    => 'inactive',
            ]);

        Log::warning('StripeWebhook: mandate.updated — mandate inactive, cleared from client', [
            'payment_method_id' => $paymentMethodId,
        ]);
    }

    // -------------------------------------------------------------------------

    private function findByIntent(object $intent): ?DirectDebitPayment
    {
        // Primary lookup: metadata we stamped at PaymentIntent creation time
        $ddPaymentId = $intent->metadata['dd_payment_id'] ?? null;

        if ($ddPaymentId) {
            $ddPayment = DirectDebitPayment::with(['invoice', 'invoice.tenant', 'invoice.tenant.connection'])
                ->find((int) $ddPaymentId);

            if ($ddPayment) {
                return $ddPayment;
            }
        }

        // Fallback: look up by the gateway_payment_id column
        return DirectDebitPayment::with(['invoice', 'invoice.tenant', 'invoice.tenant.connection'])
            ->where('gateway_payment_id', $intent->id)
            ->first();
    }
}
