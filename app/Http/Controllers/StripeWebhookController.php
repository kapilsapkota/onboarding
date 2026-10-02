<?php

namespace App\Http\Controllers;

use App\Jobs\HandleFailedDirectDebitPayment;
use App\Jobs\WriteXeroPayment;
use App\Models\Client;
use App\Models\DirectDebitPayment;
use App\Models\StripeAccount;
use App\Models\StripeBalanceTransaction;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Notifications\StripePaymentDisputeNotification;
use App\Notifications\StripePaymentFailureNotification;
use App\Notifications\StripePaymentSuccessNotification;
use App\Notifications\StripePayoutSuccessNotification;
use App\Services\StripeAccountResolver;
use App\Services\StripeBecsService;
use App\Services\StripePayoutSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Set when the event arrives on a per-account endpoint. All customer /
     * payment-method / mandate lookups are then scoped to that account and
     * new mirror rows are stamped with it.
     */
    private ?StripeAccount $webhookAccount = null;

    public function __construct(
        private StripeBecsService $stripe,
        private StripeAccountResolver $accounts,
    ) {}

    /** BECS service bound to the webhook account (or the legacy global one). */
    protected function becs(): StripeBecsService
    {
        return $this->webhookAccount
            ? new StripeBecsService($this->webhookAccount)
            : $this->stripe;
    }

    public function __invoke(Request $request): Response
    {
        $this->webhookAccount = null;

        return $this->handle($request, (string) config('services.stripe.webhook_secret'));
    }

    /**
     * Per-account endpoint: POST /webhooks/stripe/{stripeAccount}.
     * Verifies with the account's own webhook secret; all customer and
     * payment-method handling below is then scoped to that account.
     */
    public function account(Request $request, StripeAccount $stripeAccount): Response
    {
        if (! $stripeAccount->webhook_secret) {
            Log::warning('StripeWebhook: account has no webhook secret', $this->ctx([], $stripeAccount));

            return response('Webhook secret not configured', 400);
        }

        $this->webhookAccount = $stripeAccount;

        return $this->handle($request, $stripeAccount->webhook_secret);
    }

    private function handle(Request $request, string $secret): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('StripeWebhook: invalid signature', $this->ctx(['error' => $e->getMessage()]));

            return response('Invalid signature', 400);
        }

        Log::info('StripeWebhook: received event', $this->ctx([
            'type' => $event->type, 'event_id' => $event->id ?? null,
        ]));

        try {
            match ($event->type) {
                // Payment intents
                'payment_intent.succeeded' => $this->handleSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handleFailed($event->data->object),
                'payment_intent.processing',
                'payment_intent.canceled' => $this->handleBulkChargeStatusUpdate($event->data->object),

                // Customer sync
                'customer.created',
                'customer.updated' => $this->handleCustomerUpsert($event->data->object),
                'customer.deleted' => $this->handleCustomerDeleted($event->data->object),

                // Payment method sync
                'payment_method.attached' => $this->handlePaymentMethodAttached($event->data->object),
                'payment_method.updated' => $this->handlePaymentMethodUpdated($event->data->object),
                'payment_method.detached' => $this->handlePaymentMethodDetached($event->data->object),

                // Mandate
                'mandate.updated' => $this->handleMandateUpdated($event->data->object),

                'charge.dispute.created' => $this->handleDisputeCreated($event->data->object),

                'charge.dispute.updated' => $this->handleDisputeUpdated($event->data->object),
                'charge.dispute.closed' => $this->handleDisputeClosed($event->data->object),

                'payout.paid' => $this->handlePayoutPaid($event->data->object),
                'payout.created',
                'payout.updated' => $this->handlePayoutUpsert($event->data->object),
                'payout.failed',
                'payout.canceled' => $this->handlePayoutUpsert($event->data->object),

                // Balance-transaction feed: charge carries the BT we reconcile against.
                'charge.succeeded' => $this->handleChargeSucceeded($event->data->object),

                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('StripeWebhook: unhandled exception in event handler', $this->ctx([
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]));
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

        $existing = StripeCustomer::where('stripe_customer_id', $customer->id)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->first();

        if (! $existing && ! $defaultPmId) {
            return;
        }

        if ($existing) {
            $data = [
                'name' => $customer->name,
                'email' => $customer->email,
                'default_payment_method_id' => $defaultPmId,
                'stripe_data' => $customer->toArray(),
                'last_synced_at' => now(),
            ];

            if ($this->webhookAccount) {
                $data['stripe_account_id'] = $this->webhookAccount->id;
            }

            $existing->update($data);

            Log::info('StripeWebhook: customer updated in local DB', $this->ctx(['stripe_id' => $customer->id]));
        }
    }

    /** Removes the local StripeCustomer record when deleted in Stripe. */
    private function handleCustomerDeleted(object $customer): void
    {
        $deleted = StripeCustomer::where('stripe_customer_id', $customer->id)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->delete();

        if ($deleted) {
            Log::info('StripeWebhook: customer deleted from local DB', $this->ctx(['stripe_id' => $customer->id]));
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
            Log::warning('StripeWebhook: payment_method.attached has no customer', $this->ctx(['pm_id' => $pm->id]));

            return;
        }

        $localCustomer = $this->upsertCustomerFromStripe($stripeCustomerId);

        if (! $localCustomer) {
            return;
        }

        $this->upsertPaymentMethod($pm, $localCustomer);

        Log::info('StripeWebhook: BECS payment method attached and synced', $this->ctx([
            'pm_id' => $pm->id,
            'customer_id' => $stripeCustomerId,
        ]));
    }

    /** Updates an existing local BECS payment method when changed in Stripe. */
    private function handlePaymentMethodUpdated(object $pm): void
    {
        if ($pm->type !== 'au_becs_debit') {
            return;
        }

        $local = StripePaymentMethod::where('stripe_payment_method_id', $pm->id)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->first();

        if (! $local) {
            $this->handlePaymentMethodAttached($pm);

            return;
        }

        $local->update([
            'last4' => $pm->au_becs_debit->last4 ?? null,
            'account_holder_name' => $pm->billing_details->name ?? null,
            'stripe_data' => $pm->toArray(),
            'last_synced_at' => now(),
        ]);

        Log::info('StripeWebhook: BECS payment method updated', $this->ctx(['pm_id' => $pm->id]));
    }

    /** Marks a local payment method inactive when detached from a customer. */
    private function handlePaymentMethodDetached(object $pm): void
    {
        $updated = StripePaymentMethod::where('stripe_payment_method_id', $pm->id)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->update([
                'status' => 'inactive',
                'is_default' => false,
                'stripe_data' => $pm->toArray(),
                'last_synced_at' => now(),
            ]);

        if ($updated) {
            Log::info('StripeWebhook: payment method detached, marked inactive', $this->ctx(['pm_id' => $pm->id]));
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
                Log::info('StripeWebhook: payment_intent.succeeded — already settled, skipping', $this->ctx([
                    'id' => $ddPayment->id,
                ]));
            } else {
                $reconciled = $this->reconcileQuietly($intent->id ?? '');
                $balanceTx = $this->balanceTxArray($reconciled);

                if (empty($balanceTx)) {
                    $balanceTx = $this->becs()->getBalanceTransaction($intent['id']);
                }

                $ddPayment->markSettled($balanceTx);
                $ddPayment->invoice?->markPaymentSettled();

                Log::info('StripeWebhook: payment_intent.succeeded — DD payment marked settled', $this->ctx([
                    'id' => $ddPayment->id,
                    'payment_intent_id' => $intent->id,
                ]));

                WriteXeroPayment::dispatch($ddPayment->id);
            }
        }

        $batchItem = $this->findBatchItem($intent);

        if ($batchItem) {
            if ($batchItem->status === 'succeeded') {
                Log::info('StripeWebhook: payment_intent.succeeded — batch item already succeeded, skipping', $this->ctx([
                    'id' => $batchItem->id,
                ]));
            } else {
                $batchItem->update([
                    'status' => 'succeeded',
                    'stripe_data' => $intent->toArray(),
                ]);

                $batch = $batchItem->batch;

                if ($batch) {
                    $batch->recalculateStatus();
                    $batch->createdBy?->notify(
                        new StripePaymentSuccessNotification($batchItem->id)
                    );
                }

                Log::info('StripeWebhook: payment_intent.succeeded — batch item marked succeeded', $this->ctx([
                    'id' => $batchItem->id,
                    'payment_intent_id' => $intent->id,
                ]));
            }

            $this->reconcileQuietly($intent->id ?? '');
        }
    }

    private function handleFailed(object $intent): void
    {
        $lastError = $intent->last_payment_error;
        $reason = $lastError?->message ?? 'Payment failed';
        $code = $lastError?->code ?? null;

        $ddPayment = $this->findDirectDebitPayment($intent);

        if ($ddPayment) {
            if ($ddPayment->status === 'failed') {
                Log::info('StripeWebhook: payment_intent.payment_failed — DD payment already failed, skipping', $this->ctx([
                    'id' => $ddPayment->id,
                ]));
            } else {
                $ddPayment->markFailed($reason, $code);
                $ddPayment->invoice?->markPaymentFailed($reason);

                Log::warning('StripeWebhook: payment_intent.payment_failed — DD payment marked failed', $this->ctx([
                    'id' => $ddPayment->id,
                    'payment_intent_id' => $intent->id,
                    'code' => $code,
                    'reason' => $reason,
                ]));

                HandleFailedDirectDebitPayment::dispatch($ddPayment->id);
            }
        }

        $batchItem = $this->findBatchItem($intent);

        if ($batchItem) {
            if ($batchItem->status === 'failed') {
                Log::info('StripeWebhook: payment_intent.payment_failed — batch item already failed, skipping', $this->ctx([
                    'id' => $batchItem->id,
                ]));
            } else {
                $batchItem->update([
                    'status' => 'failed',
                    'stripe_data' => $intent->toArray(),
                    'error_message' => $reason,
                ]);

                $batch = $batchItem->batch;

                $batch->recalculateStatus();

                $batch->createdBy?->notify(
                    new StripePaymentFailureNotification($batchItem->id)
                );

                Log::warning('StripeWebhook: payment_intent.payment_failed — batch item marked failed', $this->ctx([
                    'id' => $batchItem->id,
                    'payment_intent_id' => $intent->id,
                    'code' => $code,
                    'reason' => $reason,
                ]));
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

        // Normalise Stripe's 'canceled' to our 'cancelled' spelling so status
        // stays consistent with manual cancellations.
        $status = match ($intent->status) {
            'processing' => 'processing',
            'canceled' => 'cancelled',
            default => $intent->status,
        };

        $batchItem->update([
            'status' => $status,
            'stripe_data' => $intent->toArray(),
        ]);

        $batchItem->batch->recalculateStatus();

        Log::info('StripeWebhook: batch item status updated', $this->ctx([
            'id' => $batchItem->id,
            'payment_intent_id' => $intent->id,
            'status' => $status,
        ]));
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
                'mandate_status' => 'inactive',
            ]);

        StripePaymentMethod::where('stripe_payment_method_id', $paymentMethodId)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->update(['status' => 'inactive', 'is_default' => false]);

        Log::warning('StripeWebhook: mandate.updated — inactive, cleared from client and local PM', $this->ctx([
            'payment_method_id' => $paymentMethodId,
        ]));
    }

    // -------------------------------------------------------------------------
    // Sync helpers
    // -------------------------------------------------------------------------

    /** Fetches the Stripe customer and upserts the local record. */
    private function upsertCustomerFromStripe(string $stripeCustomerId): ?StripeCustomer
    {
        try {
            $stripeCustomer = $this->becs()->client()->customers->retrieve($stripeCustomerId);

            if ($stripeCustomer->deleted ?? false) {
                return null;
            }

            $defaultPmId = $this->resolveDefaultPmId($stripeCustomer);

            $values = [
                'name' => $stripeCustomer->name,
                'email' => $stripeCustomer->email,
                'default_payment_method_id' => $defaultPmId,
                'stripe_data' => $stripeCustomer->toArray(),
                'last_synced_at' => now(),
            ];

            if ($this->webhookAccount) {
                $values['stripe_account_id'] = $this->webhookAccount->id;
            }

            return StripeCustomer::updateOrCreate(
                ['stripe_customer_id' => $stripeCustomerId],
                $values
            );
        } catch (\Throwable $e) {
            Log::error('StripeWebhook: failed to retrieve customer from Stripe', $this->ctx([
                'stripe_customer_id' => $stripeCustomerId,
                'error' => $e->getMessage(),
            ]));

            return null;
        }
    }

    /** Upserts a local StripePaymentMethod from a Stripe PaymentMethod object. */
    private function upsertPaymentMethod(object $pm, StripeCustomer $localCustomer): void
    {
        $defaultPmId = $localCustomer->default_payment_method_id;

        $values = [
            'stripe_customer_id' => $localCustomer->id,
            'type' => $pm->type,
            'last4' => $pm->au_becs_debit->last4 ?? null,
            'account_holder_name' => $pm->billing_details->name ?? null,
            'is_default' => $pm->id === $defaultPmId,
            'status' => 'active',
            'stripe_data' => $pm->toArray(),
            'last_synced_at' => now(),
        ];

        if ($this->webhookAccount) {
            $values['stripe_account_id'] = $this->webhookAccount->id;
        }

        StripePaymentMethod::updateOrCreate(
            ['stripe_payment_method_id' => $pm->id],
            $values
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

            if ($ddPayment && $this->belongsToWebhookAccount($ddPayment->stripe_account_id)) {
                return $ddPayment;
            }

            if ($ddPayment) {
                return null;
            }
        }

        return DirectDebitPayment::with([
            'invoice',
            'invoice.client',
            'invoice.tenant',
            'invoice.tenant.connection',
        ])
            ->where('gateway_payment_id', $intent->id)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->first();
    }

    /** Finds a StripeChargeBatchItem by stripe_payment_intent_id. */
    private function findBatchItem(object $intent): ?StripeChargeBatchItem
    {
        return StripeChargeBatchItem::where('stripe_payment_intent_id', $intent->id)
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->first();
    }

    /**
     * In per-account mode only records already stamped to that account may
     * be touched; unscoped (legacy) rows belong to the global endpoint.
     */
    private function belongsToWebhookAccount(?int $rowAccountId): bool
    {
        if (! $this->webhookAccount) {
            return true;
        }

        return $rowAccountId === $this->webhookAccount->id;
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

    /**
     * Handles a newly created Stripe dispute.
     */
    private function handleDisputeCreated(object $dispute): void
    {
        $batchItem = $this->findBatchItemFromDispute($dispute);

        if (! $batchItem) {
            return;
        }

        /*
         * Stripe may retry the same webhook.
         * Do not send another notification if we have already processed
         * this exact dispute.
         */
        $existingDisputeId = data_get(
            $batchItem->stripe_data,
            'dispute.id'
        );

        if ($existingDisputeId === ($dispute->id ?? null)) {
            Log::info('StripeWebhook: dispute.created already processed, skipping', $this->ctx([
                'batch_item_id' => $batchItem->id,
                'dispute_id' => $dispute->id,
            ]));

            return;
        }

        $this->saveDisputeData($batchItem, $dispute);

        $batchItem->update([
            'status' => 'disputed',
        ]);

        $batch = $batchItem->batch;

        if ($batch) {
            $batch->recalculateStatus();
        }

        /*
         * Notification implements ShouldQueue, so this does not send
         * the email synchronously from the webhook request.
         */
        $batch?->createdBy?->notify(
            new StripePaymentDisputeNotification($batchItem->id)
        );

        Log::warning('StripeWebhook: dispute created for batch item', $this->ctx([
            'batch_item_id' => $batchItem->id,
            'payment_intent_id' => $this->resolveId($dispute->payment_intent ?? null),
            'dispute_id' => $dispute->id ?? null,
            'reason' => $dispute->reason ?? null,
            'status' => $dispute->status ?? null,
            'amount' => $dispute->amount ?? null,
            'currency' => $dispute->currency ?? null,
        ]));
    }

    /**
     * Handles updates to an existing Stripe dispute.
     *
     * We keep the local batch item as "disputed" because the dispute
     * is still an active issue for the payment.
     */
    private function handleDisputeUpdated(object $dispute): void
    {
        $batchItem = $this->findBatchItemFromDispute($dispute);

        if (! $batchItem) {
            return;
        }

        $this->saveDisputeData($batchItem, $dispute);

        /*
         * Do not change the payment item back to succeeded just because
         * Stripe sent a dispute.updated event.
         */
        if ($batchItem->status !== 'disputed') {
            $batchItem->update([
                'status' => 'disputed',
            ]);

            $batchItem->batch?->recalculateStatus();
        }

        Log::warning('StripeWebhook: dispute updated for batch item', $this->ctx([
            'batch_item_id' => $batchItem->id,
            'dispute_id' => $dispute->id ?? null,
            'status' => $dispute->status ?? null,
            'reason' => $dispute->reason ?? null,
        ]));
    }

    /**
     * Handles a closed Stripe dispute.
     *
     * The final dispute result is stored in stripe_data.dispute.
     */
    private function handleDisputeClosed(object $dispute): void
    {
        $batchItem = $this->findBatchItemFromDispute($dispute);

        if (! $batchItem) {
            return;
        }

        $this->saveDisputeData($batchItem, $dispute);

        $disputeStatus = $dispute->status ?? null;

        if ($batchItem->status !== 'disputed') {
            $batchItem->update([
                'status' => 'disputed',
            ]);

            $batchItem->batch?->recalculateStatus();
        }

        Log::warning('StripeWebhook: dispute closed for batch item', $this->ctx([
            'batch_item_id' => $batchItem->id,
            'dispute_id' => $dispute->id ?? null,
            'status' => $disputeStatus,
            'reason' => $dispute->reason ?? null,
            'amount' => $dispute->amount ?? null,
            'currency' => $dispute->currency ?? null,
        ]));
    }

    /**
     * Finds a batch item from the PaymentIntent associated with a dispute.
     */
    private function findBatchItemFromDispute(object $dispute): ?StripeChargeBatchItem
    {
        $paymentIntentId = $this->resolveId(
            $dispute->payment_intent ?? null
        );

        if (! $paymentIntentId) {
            Log::warning('StripeWebhook: dispute has no payment intent', $this->ctx([
                'dispute_id' => $dispute->id ?? null,
            ]));

            return null;
        }

        return StripeChargeBatchItem::where(
            'stripe_payment_intent_id',
            $paymentIntentId
        )
            ->when($this->webhookAccount, fn ($q) => $q->where('stripe_account_id', $this->webhookAccount->id))
            ->first();
    }

    /**
     * Stores the complete Stripe dispute object under stripe_data.dispute.
     */
    private function saveDisputeData(
        StripeChargeBatchItem $batchItem,
        object $dispute
    ): void {
        $stripeData = $batchItem->stripe_data ?? [];

        $stripeData['dispute'] = $dispute->toArray();

        $batchItem->update([
            'stripe_data' => $stripeData,
        ]);
    }

    private function handlePayoutPaid(object $payout): void
    {
        // Persist first so the reconciled view is correct even if mail fails.
        $this->handlePayoutUpsert($payout);

        try {
            Notification::route('mail', 'alit@allinit.com.au')
                ->notify(
                    new StripePayoutSuccessNotification(
                        payoutId: $payout->id,
                        amount: $payout->amount,
                        currency: $payout->currency,
                        arrivalDate: $payout->arrival_date ?? null,
                        stripeAccountId: $this->webhookAccount?->id,
                    )
                );

            Log::info('StripeWebhook: payout.paid — notification queued', $this->ctx([
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'currency' => $payout->currency,
                'arrival_date' => $payout->arrival_date ?? null,
            ]));
        } catch (\Throwable $e) {
            Log::error('PayoutPaidNotification: failed to send email', $this->ctx(['error' => $e->getMessage()]));
        }

    }

    // -------------------------------------------------------------------------
    // Payout / balance-transaction steady state (post backfill)
    // -------------------------------------------------------------------------

    /** Upserts the payout row; backfills its lines once reconciliation completes. */
    private function handlePayoutUpsert(object $payout): void
    {
        $sync = $this->payoutSync();
        $local = $sync->upsertPayout($payout);

        Log::info('StripeWebhook: payout upserted', $this->ctx([
            'payout_id' => $payout->id,
            'status' => $payout->status ?? null,
        ]));

        // Stripe's payout object has NO reconciliation_status field, so we
        // backfill on every actionable status instead. The upserts are
        // idempotent, and listing by payout is the only way to learn
        // which balance transactions Stripe attributed to this payout.
        // Skipped for failed/canceled (nothing to attribute).
        if (in_array($payout->status ?? null, ['pending', 'in_transit', 'paid'], true)) {
            try {
                $n = $sync->syncPayoutTransactions($local->stripe_payout_id);
                Log::info('StripeWebhook: payout transactions backfilled', $this->ctx([
                    'payout_id' => $payout->id, 'count' => $n,
                ]));
            } catch (\Throwable $e) {
                Log::warning('StripeWebhook: payout transaction backfill failed', $this->ctx([
                    'payout_id' => $payout->id, 'error' => $e->getMessage(),
                ]));
            }
        }
    }

    /**
     * charge.succeeded carries balance_transaction + payment_intent.
     * Persist the BT immediately so items reconcile even before
     * their payout exists (payout FK stays null until payout.* arrives).
     */
    private function handleChargeSucceeded(object $charge): void
    {
        try {
            $raw = $charge->balance_transaction ?? null;
            $btId = is_string($raw)
                ? $raw
                : (is_object($raw) && method_exists($raw, 'toArray')
                    ? (($raw->toArray()['id'] ?? null))
                    : ($raw->id ?? null));

            if (! is_string($btId) || $btId === '') {
                return;
            }

            $sync = $this->payoutSync();
            $bt = $sync->client()->balanceTransactions->retrieve($btId, [
                'expand' => ['source'],
            ]);

            $sync->upsertBalanceTransaction($bt);

            Log::info('StripeWebhook: charge.succeeded — balance transaction stored', $this->ctx([
                'charge_id' => $charge->id ?? null,
                'balance_transaction' => $btId,
            ]));
        } catch (\Throwable $e) {
            Log::warning('StripeWebhook: charge.succeeded BT sync failed', $this->ctx([
                'charge_id' => $charge->id ?? null, 'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Payout sync bound to the webhook account (or the legacy global keys).
     * The service stamps every payout/BT row it writes with that account.
     */
    protected function payoutSync(): StripePayoutSyncService
    {
        if ($this->webhookAccount) {
            return new StripePayoutSyncService(
                $this->accounts->clientFor($this->webhookAccount),
                $this->webhookAccount
            );
        }

        return app(StripePayoutSyncService::class);
    }

    /**
     * Reconcile a PaymentIntent without ever failing the webhook.
     * Returns null when the BT is not available yet or Stripe errors.
     */
    private function reconcileQuietly(mixed $paymentIntentId): ?StripeBalanceTransaction
    {
        if (! is_string($paymentIntentId) || $paymentIntentId === '') {
            return null;
        }

        try {
            return $this->payoutSync()->reconcilePaymentIntent($paymentIntentId);
        } catch (\Throwable $e) {
            Log::warning('StripeWebhook: PI reconcile skipped', $this->ctx([
                'payment_intent_id' => $paymentIntentId, 'error' => $e->getMessage(),
            ]));

            return null;
        }
    }

    /**
     * Build the dollars-based array markSettled() expects from a persisted
     * BT row (stored in cents). Returns [] when there is nothing to use.
     *
     * @return array{gross: float, fee: float, net: float, currency: string, stripe_bt_id: string}|array{}
     */
    private function balanceTxArray(?StripeBalanceTransaction $bt): array
    {
        if (! $bt || ! $bt->stripe_balance_transaction_id) {
            return [];
        }

        return [
            'gross' => ((float) ($bt->amount ?? 0)) / 100,
            'fee' => ((float) ($bt->fee ?? 0)) / 100,
            'net' => ((float) ($bt->net ?? 0)) / 100,
            'currency' => strtoupper((string) ($bt->currency ?? 'AUD')),
            'stripe_bt_id' => (string) $bt->stripe_balance_transaction_id,
        ];
    }

    /**
     * Shared context for every webhook log line, so you can see at a glance
     * whether the event arrived on a per-account endpoint or legacy.
     */
    private function ctx(array $extra = [], ?StripeAccount $account = null): array
    {
        $account ??= $this->webhookAccount;

        return array_merge([
            'endpoint' => $account ? 'account:'.$account->id.' ('.$account->display_name.')' : 'legacy',
            'stripe_account_id' => $account?->id,
        ], $extra);
    }
}
