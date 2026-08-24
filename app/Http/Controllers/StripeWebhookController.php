<?php

namespace App\Http\Controllers;

use App\Jobs\HandleFailedDirectDebitPayment;
use App\Jobs\WriteXeroPayment;
use App\Models\Client;
use App\Models\DirectDebitPayment;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
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
                // Payment intents
                'payment_intent.succeeded'      => $this->handleSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handleFailed($event->data->object),
                'payment_intent.processing',
                'payment_intent.canceled'       => $this->handleBulkChargeStatusUpdate($event->data->object),

                // Customer sync
                'customer.created',
                'customer.updated'              => $this->handleCustomerUpsert($event->data->object),
                'customer.deleted'              => $this->handleCustomerDeleted($event->data->object),

                // Payment method sync
                'payment_method.attached'       => $this->handlePaymentMethodAttached($event->data->object),
                'payment_method.updated'        => $this->handlePaymentMethodUpdated($event->data->object),
                'payment_method.detached'       => $this->handlePaymentMethodDetached($event->data->object),

                // Mandate
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
    // Customer sync handlers
    // -------------------------------------------------------------------------

    /** Upserts a local StripeCustomer - only if they already exist locally or have BECS methods. */
    private function handleCustomerUpsert(object $customer): void
    {
        $defaultPmId = $this->resolveDefaultPmId($customer);

        $existing = StripeCustomer::where('stripe_customer_id', $customer->id)->first();

        if (! $existing && ! $defaultPmId) {
            return;
        }

        if ($existing) {
            $existing->update([
                'name'                      => $customer->name,
                'email'                     => $customer->email,
                'default_payment_method_id' => $defaultPmId,
                'stripe_data'               => $customer->toArray(),
                'last_synced_at'            => now(),
            ]);

            Log::info('StripeWebhook: customer updated in local DB', ['stripe_id' => $customer->id]);
        }
    }

    /** Removes the local StripeCustomer record when deleted in Stripe. */
    private function handleCustomerDeleted(object $customer): void
    {
        $deleted = StripeCustomer::where('stripe_customer_id', $customer->id)->delete();

        if ($deleted) {
            Log::info('StripeWebhook: customer deleted from local DB', ['stripe_id' => $customer->id]);
        }
    }

    // -------------------------------------------------------------------------
    // Payment method sync handlers
    // -------------------------------------------------------------------------

    /** Syncs a payment method when attached to a customer - creates customer record if needed. */
    private function handlePaymentMethodAttached(object $pm): void
    {
        if ($pm->type !== 'au_becs_debit') {
            return;
        }

        $stripeCustomerId = $this->resolveId($pm->customer);

        if (! $stripeCustomerId) {
            Log::warning('StripeWebhook: payment_method.attached has no customer', ['pm_id' => $pm->id]);
            return;
        }

        $localCustomer = $this->upsertCustomerFromStripe($stripeCustomerId);

        if (! $localCustomer) {
            return;
        }

        $this->upsertPaymentMethod($pm, $localCustomer);

        Log::info('StripeWebhook: BECS payment method attached and synced', [
            'pm_id'       => $pm->id,
            'customer_id' => $stripeCustomerId,
        ]);
    }

    /** Updates an existing local BECS payment method when changed in Stripe. */
    private function handlePaymentMethodUpdated(object $pm): void
    {
        if ($pm->type !== 'au_becs_debit') {
            return;
        }

        $local = StripePaymentMethod::where('stripe_payment_method_id', $pm->id)->first();

        if (! $local) {
            $this->handlePaymentMethodAttached($pm);
            return;
        }

        $local->update([
            'last4'               => $pm->au_becs_debit->last4 ?? null,
            'account_holder_name' => $pm->billing_details->name ?? null,
            'stripe_data'         => $pm->toArray(),
            'last_synced_at'      => now(),
        ]);

        Log::info('StripeWebhook: BECS payment method updated', ['pm_id' => $pm->id]);
    }

    /** Marks a local payment method inactive when detached from a customer. */
    private function handlePaymentMethodDetached(object $pm): void
    {
        $updated = StripePaymentMethod::where('stripe_payment_method_id', $pm->id)
            ->update([
                'status'         => 'inactive',
                'is_default'     => false,
                'stripe_data'    => $pm->toArray(),
                'last_synced_at' => now(),
            ]);

        if ($updated) {
            Log::info('StripeWebhook: payment method detached, marked inactive', ['pm_id' => $pm->id]);
        }
    }

    // -------------------------------------------------------------------------
    // Payment intent handlers
    // -------------------------------------------------------------------------

    private function handleSucceeded(object $intent): void
    {
        $ddPayment = $this->findDirectDebitPayment($intent);

        if ($ddPayment) {
            if ($ddPayment->status === 'settled') {
                Log::info('StripeWebhook: payment_intent.succeeded — already settled, skipping', [
                    'id' => $ddPayment->id,
                ]);
            } else {
                $balanceTx = $this->stripe->getBalanceTransaction($intent['id']);

                $ddPayment->markSettled($balanceTx);
                $ddPayment->invoice->markPaymentSettled();

                Log::info('StripeWebhook: payment_intent.succeeded — DD payment marked settled', [
                    'id'                => $ddPayment->id,
                    'payment_intent_id' => $intent->id,
                ]);

                WriteXeroPayment::dispatch($ddPayment->id);
            }
        }

        $batchItem = $this->findBatchItem($intent);

        if ($batchItem) {
            if ($batchItem->status === 'succeeded') {
                Log::info('StripeWebhook: payment_intent.succeeded — batch item already succeeded, skipping', [
                    'id' => $batchItem->id,
                ]);
            } else {
                $batchItem->update([
                    'status'      => 'succeeded',
                    'stripe_data' => $intent->toArray(),
                ]);

                $batchItem->batch->recalculateStatus();

                Log::info('StripeWebhook: payment_intent.succeeded — batch item marked succeeded', [
                    'id'                => $batchItem->id,
                    'payment_intent_id' => $intent->id,
                ]);
            }
        }
    }

    private function handleFailed(object $intent): void
    {
        $lastError = $intent->last_payment_error;
        $reason    = $lastError?->message ?? 'Payment failed';
        $code      = $lastError?->code    ?? null;

        $ddPayment = $this->findDirectDebitPayment($intent);

        if ($ddPayment) {
            if ($ddPayment->status === 'failed') {
                Log::info('StripeWebhook: payment_intent.payment_failed — DD payment already failed, skipping', [
                    'id' => $ddPayment->id,
                ]);
            } else {
                $ddPayment->markFailed($reason, $code);
                $ddPayment->invoice->markPaymentFailed($reason);

                Log::warning('StripeWebhook: payment_intent.payment_failed — DD payment marked failed', [
                    'id'                => $ddPayment->id,
                    'payment_intent_id' => $intent->id,
                    'code'              => $code,
                    'reason'            => $reason,
                ]);

                HandleFailedDirectDebitPayment::dispatch($ddPayment->id);
            }
        }

        $batchItem = $this->findBatchItem($intent);

        if ($batchItem) {
            if ($batchItem->status === 'failed') {
                Log::info('StripeWebhook: payment_intent.payment_failed — batch item already failed, skipping', [
                    'id' => $batchItem->id,
                ]);
            } else {
                $batchItem->update([
                    'status'        => 'failed',
                    'stripe_data'   => $intent->toArray(),
                    'error_message' => $reason,
                ]);

                $batchItem->batch->recalculateStatus();

                Log::warning('StripeWebhook: payment_intent.payment_failed — batch item marked failed', [
                    'id'                => $batchItem->id,
                    'payment_intent_id' => $intent->id,
                    'code'              => $code,
                    'reason'            => $reason,
                ]);
            }
        }
    }

    /** Handles processing/canceled events - only relevant to bulk charge items. */
    private function handleBulkChargeStatusUpdate(object $intent): void
    {
        $batchItem = $this->findBatchItem($intent);

        if (! $batchItem) {
            return;
        }

        $status = $intent->status === 'processing' ? 'processing' : 'failed';

        $batchItem->update([
            'status'      => $status,
            'stripe_data' => $intent->toArray(),
        ]);

        $batchItem->batch->recalculateStatus();

        Log::info('StripeWebhook: batch item status updated', [
            'id'                => $batchItem->id,
            'payment_intent_id' => $intent->id,
            'status'            => $status,
        ]);
    }

    private function handleMandateUpdated(object $mandate): void
    {
        if (($mandate->status ?? null) !== 'inactive') {
            return;
        }

        $paymentMethodId = $this->resolveId($mandate->payment_method);

        if (! $paymentMethodId) {
            return;
        }

        Client::where('stripe_payment_method_id', $paymentMethodId)
            ->update([
                'stripe_payment_method_id' => null,
                'mandate_status'           => 'inactive',
            ]);

        StripePaymentMethod::where('stripe_payment_method_id', $paymentMethodId)
            ->update(['status' => 'inactive', 'is_default' => false]);

        Log::warning('StripeWebhook: mandate.updated — inactive, cleared from client and local PM', [
            'payment_method_id' => $paymentMethodId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Sync helpers
    // -------------------------------------------------------------------------

    /** Fetches the Stripe customer and upserts the local record. */
    private function upsertCustomerFromStripe(string $stripeCustomerId): ?StripeCustomer
    {
        try {
            $stripeCustomer = $this->stripe->client()->customers->retrieve($stripeCustomerId);

            if ($stripeCustomer->deleted ?? false) {
                return null;
            }

            $defaultPmId = $this->resolveDefaultPmId($stripeCustomer);

            return StripeCustomer::updateOrCreate(
                ['stripe_customer_id' => $stripeCustomerId],
                [
                    'name'                      => $stripeCustomer->name,
                    'email'                     => $stripeCustomer->email,
                    'default_payment_method_id' => $defaultPmId,
                    'stripe_data'               => $stripeCustomer->toArray(),
                    'last_synced_at'            => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('StripeWebhook: failed to retrieve customer from Stripe', [
                'stripe_customer_id' => $stripeCustomerId,
                'error'              => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Upserts a local StripePaymentMethod from a Stripe PaymentMethod object. */
    private function upsertPaymentMethod(object $pm, StripeCustomer $localCustomer): void
    {
        $defaultPmId = $localCustomer->default_payment_method_id;

        StripePaymentMethod::updateOrCreate(
            ['stripe_payment_method_id' => $pm->id],
            [
                'stripe_customer_id'  => $localCustomer->id,
                'type'                => $pm->type,
                'last4'               => $pm->au_becs_debit->last4 ?? null,
                'account_holder_name' => $pm->billing_details->name ?? null,
                'is_default'          => $pm->id === $defaultPmId,
                'status'              => 'active',
                'stripe_data'         => $pm->toArray(),
                'last_synced_at'      => now(),
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Lookup helpers
    // -------------------------------------------------------------------------

    /** Finds a DirectDebitPayment by metadata or gateway_payment_id. */
    private function findDirectDebitPayment(object $intent): ?DirectDebitPayment
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

    /** Finds a StripeChargeBatchItem by stripe_payment_intent_id. */
    private function findBatchItem(object $intent): ?StripeChargeBatchItem
    {
        return StripeChargeBatchItem::where('stripe_payment_intent_id', $intent->id)->first();
    }

    /** Resolves a Stripe object or string ID to a string ID. */
    private function resolveId(mixed $value): ?string
    {
        if (is_string($value) && ! empty($value)) {
            return $value;
        }

        return $value->id ?? null;
    }

    /** Resolves the default payment method ID from a Stripe customer object. */
    private function resolveDefaultPmId(object $customer): ?string
    {
        $raw = $customer->invoice_settings->default_payment_method ?? null;

        return $this->resolveId($raw);
    }
}
