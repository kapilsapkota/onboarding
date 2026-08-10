<?php

namespace App\Services\Quotes;

use App\Jobs\Quotes\ProcessQuoteDeliveryJob;
use App\Models\Quote;
use App\Models\QuoteDelivery;
use App\Models\QuoteDeliveryAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuoteDeliveryService
{
    /**
     * Create a new delivery record and dispatch it for processing.
     *
     * Duplicate guard: if the quote already has a delivery in pending or
     * processing state, that delivery is returned without creating a new one.
     *
     * @param  array{
     *     send_email:     bool,
     *     send_sms:       bool,
     *     email_message:  string|null,
     *     sms_message:    string|null,
     * }  $validated
     *
     * @return array{delivery: QuoteDelivery, already_pending: bool}
     */
    public function createAndDispatch(Quote $quote, array $validated, int $userId): array
    {
        [$delivery, $alreadyPending] = DB::transaction(function () use ($quote, $validated, $userId) {

            // Lock the quote row so concurrent requests queue behind this one.
            Quote::lockForUpdate()->find($quote->id);

            // ── Duplicate guard ───────────────────────────────────────────────
            $existing = QuoteDelivery::where('quote_id', $quote->id)
                ->whereIn('status', [
                    QuoteDelivery::STATUS_PENDING,
                    QuoteDelivery::STATUS_PROCESSING,
                ])
                ->latest()
                ->first();

            if ($existing) {
                Log::info('quote_delivery.duplicate_prevented', [
                    'quote_id'    => $quote->id,
                    'delivery_id' => $existing->id,
                ]);

                return [$existing, true];
            }

            // ── Snapshot recipient details from the Quote model ───────────────
            $emailAddress = ($validated['send_email'] && $quote->email)
                ? $quote->email
                : null;

            $phoneNumber = ($validated['send_sms'] && $quote->mobile)
                ? $quote->mobile
                : null;

            // ── Create delivery record ────────────────────────────────────────
            $delivery = QuoteDelivery::create([
                'quote_id'      => $quote->id,
                'requested_by'  => $userId,
                'status'        => QuoteDelivery::STATUS_PENDING,
                'send_email'    => $validated['send_email'],
                'send_sms'      => $validated['send_sms'],
                'email_address' => $emailAddress,
                'phone_number'  => $phoneNumber,
                'email_subject' => 'Quotation ' . $quote->quote_number,
                'email_message' => $validated['email_message'] ?? null,
                'sms_message'   => $validated['sms_message'] ?? null,
            ]);

            $this->createInitialAttempts($delivery);

            Log::info('quote_delivery.created', [
                'delivery_id'  => $delivery->id,
                'quote_id'     => $quote->id,
                'requested_by' => $userId,
                'send_email'   => $validated['send_email'],
                'send_sms'     => $validated['send_sms'],
            ]);

            return [$delivery, false];
        });

        if (! $alreadyPending) {
            dispatch(new ProcessQuoteDeliveryJob($delivery->id));
        }

        return [
            'delivery'        => $delivery,
            'already_pending' => $alreadyPending,
        ];
    }

    /**
     * Retry a specific failed attempt.
     */
    public function retryAttempt(QuoteDeliveryAttempt $attempt): QuoteDeliveryAttempt
    {
        $delivery = $attempt->delivery;

        if ($attempt->status !== QuoteDeliveryAttempt::STATUS_FAILED) {
            throw new \InvalidArgumentException(
                "Attempt #{$attempt->id} cannot be retried — status is '{$attempt->status}'."
            );
        }

        $newAttempt = DB::transaction(function () use ($delivery, $attempt) {
            return $delivery->attempts()->create([
                'type'           => $attempt->type,
                'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
                'attempt_number' => $delivery->nextAttemptNumber($attempt->type),
            ]);
        });

        Log::info('quote_delivery.retry_requested', [
            'delivery_id'    => $delivery->id,
            'quote_id'       => $delivery->quote_id,
            'type'           => $attempt->type,
            'new_attempt_id' => $newAttempt->id,
            'attempt_number' => $newAttempt->attempt_number,
        ]);

        dispatch(new ProcessQuoteDeliveryJob($delivery->id, $attempt->type));

        return $newAttempt;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Create the initial set of pending attempt rows for a new delivery.
     *
     * Always created:
     *   - generate_pdf        (prerequisite for email + SharePoint)
     *   - generate_public_url (prerequisite for SMS)
     *   - sharepoint_upload   (always attempted — skipped gracefully if not configured)
     *
     * Created when channel is requested:
     *   - email (if send_email = true)
     *   - sms   (if send_sms = true)
     *
     * Creating the attempt row even when the recipient is missing allows the
     * job to mark it skipped with a clear human-readable reason, rather than
     * silently not running.
     */
    private function createInitialAttempts(QuoteDelivery $delivery): void
    {
        // PDF — always required.
        $delivery->attempts()->create([
            'type'           => QuoteDeliveryAttempt::TYPE_GENERATE_PDF,
            'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
            'attempt_number' => 1,
        ]);

        // Public URL — always required.
        $delivery->attempts()->create([
            'type'           => QuoteDeliveryAttempt::TYPE_GENERATE_PUBLIC_URL,
            'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
            'attempt_number' => 1,
        ]);

        // SharePoint — always attempted.
        // The job skips gracefully if SharePoint is not configured.
        $delivery->attempts()->create([
            'type'           => QuoteDeliveryAttempt::TYPE_SHAREPOINT_UPLOAD,
            'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
            'attempt_number' => 1,
        ]);

        // Email — only when requested.
        if ($delivery->send_email) {
            $delivery->attempts()->create([
                'type'           => QuoteDeliveryAttempt::TYPE_EMAIL,
                'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
                'attempt_number' => 1,
            ]);
        }

        // SMS — only when requested.
        if ($delivery->send_sms) {
            $delivery->attempts()->create([
                'type'           => QuoteDeliveryAttempt::TYPE_SMS,
                'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
                'attempt_number' => 1,
            ]);
        }
    }
}
