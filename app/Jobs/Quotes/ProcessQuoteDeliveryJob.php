<?php

namespace App\Jobs\Quotes;

use App\Mail\Quotes\QuoteDeliveryMail;
use App\Models\QuoteDelivery;
use App\Models\QuoteDeliveryAttempt;
use App\Services\EmailService;
use App\Services\Quotes\QuotePdfService;
use App\Services\Quotes\QuotePublicLinkService;
use App\Services\Quotes\QuoteSharePointService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates the full quote delivery pipeline.
 *
 */
class ProcessQuoteDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly int     $deliveryId,
        private readonly ?string $retryType = null,
    ) {}

    // =========================================================================
    // Entry point
    // =========================================================================

    public function handle(
        QuotePdfService        $pdfService,
        QuotePublicLinkService $publicLinkService,
        QuoteSharePointService $sharePointService,
        EmailService           $emailService,
        SmsService             $smsService,
    ): void {
        $delivery = QuoteDelivery::with([
            'quote.items.product.category',
            'attempts',
        ])->findOrFail($this->deliveryId);

        Log::info('quote_delivery.job_started', [
            'delivery_id' => $delivery->id,
            'quote_id'    => $delivery->quote_id,
            'retry_type'  => $this->retryType,
        ]);

        if (in_array($delivery->status, [
            QuoteDelivery::STATUS_PENDING,
            QuoteDelivery::STATUS_FAILED,
            QuoteDelivery::STATUS_PARTIALLY_FAILED,
        ])) {
            $delivery->update([
                'status'     => QuoteDelivery::STATUS_PROCESSING,
                'started_at' => $delivery->started_at ?? now(),
            ]);
        }

        // ── Step 1: PDF ───────────────────────────────────────────────────────
        // Prerequisite for email and SharePoint.
        $pdfSucceeded = $this->stepGeneratePdf($delivery, $pdfService);

        // ── Step 2: Public URL ────────────────────────────────────────────────
        // Prerequisite for SMS. Runs in parallel with PDF conceptually —
        // neither depends on the other.
        $publicUrlSucceeded = $this->stepGeneratePublicUrl($delivery, $publicLinkService);

        // ── Step 3: SharePoint ────────────────────────────────────────────────
        // Requires PDF. Does NOT block email or SMS on failure.
        if ($pdfSucceeded) {
            $this->stepSharePoint($delivery, $sharePointService);
        } else {
            // PDF failed — mark SharePoint as blocked by it.
            $this->blockDependentStep(
                delivery:  $delivery,
                type:      QuoteDeliveryAttempt::TYPE_SHAREPOINT_UPLOAD,
                errorCode: 'sharepoint_blocked_no_pdf',
                message:   'SharePoint upload could not proceed because the PDF was not generated successfully. Retry PDF generation first.',
            );
        }

        // ── Step 4: Email ─────────────────────────────────────────────────────
        // Requires PDF. SharePoint result is irrelevant.
        if ($delivery->send_email) {
            $this->stepEmail(
                delivery:           $delivery,
                emailService:       $emailService,
                pdfService:         $pdfService,
                pdfSucceeded:       $pdfSucceeded,
                publicUrlSucceeded: $publicUrlSucceeded,
            );
        }

        // ── Step 5: SMS ───────────────────────────────────────────────────────
        // Requires public URL only. Fully independent of SharePoint.
        if ($delivery->send_sms) {
            $this->stepSms($delivery, $smsService, $publicUrlSucceeded);
        }

        // ── Final status ──────────────────────────────────────────────────────
        $delivery->refresh();
        $delivery->updateStatus();
        $this->maybeMarkQuoteAsSent($delivery);

        Log::info('quote_delivery.job_finished', [
            'delivery_id' => $delivery->id,
            'status'      => $delivery->status,
            'retry_type'  => $this->retryType,
        ]);
    }

    // =========================================================================
    // Laravel failed() hook
    // =========================================================================

    public function failed(Throwable $exception): void
    {
        Log::error('quote_delivery.job_crashed', [
            'delivery_id' => $this->deliveryId,
            'retry_type'  => $this->retryType,
            'error'       => $exception->getMessage(),
            'class'       => $exception::class,
        ]);

        try {
            $delivery = QuoteDelivery::find($this->deliveryId);

            if ($delivery && ! in_array($delivery->status, [
                    QuoteDelivery::STATUS_COMPLETED,
                    QuoteDelivery::STATUS_CANCELLED,
                ])) {
                $delivery->update([
                    'status'    => QuoteDelivery::STATUS_FAILED,
                    'failed_at' => $delivery->failed_at ?? now(),
                ]);
            }
        } catch (Throwable) {
            // Never throw from failed().
        }
    }

    // =========================================================================
    // Pipeline steps
    // =========================================================================

    private function stepGeneratePdf(
        QuoteDelivery   $delivery,
        QuotePdfService $pdfService,
    ): bool {
        if ($delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_GENERATE_PDF)) {
            return true;
        }

        $attempt = $this->findPendingAttempt($delivery, QuoteDeliveryAttempt::TYPE_GENERATE_PDF);

        if (! $attempt) {
            return false;
        }

        $attempt->markProcessing();

        try {
            $path = $pdfService->generate($delivery);

            $delivery->refresh();

            $attempt->markSucceeded([
                'path'     => $path,
                'filename' => $delivery->pdf_filename,
                'size'     => $delivery->pdf_size,
            ]);

            Log::info('quote_delivery.pdf_generated', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
            ]);

            // Unblock anything that was waiting on the PDF.
            $this->unblockDownstream($delivery, [
                'email_blocked_no_pdf',
                'sharepoint_blocked_no_pdf',
            ]);

            return true;

        } catch (Throwable $e) {
            $attempt->markFailed(
                errorCode:    'pdf_generation_failed',
                errorMessage: 'The PDF could not be generated. Please try again.',
                errorDetails: $this->exceptionDetail($e),
            );

            Log::error('quote_delivery.pdf_failed', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function stepGeneratePublicUrl(
        QuoteDelivery          $delivery,
        QuotePublicLinkService $publicLinkService,
    ): bool {
        if ($delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_GENERATE_PUBLIC_URL)) {
            return true;
        }

        $attempt = $this->findPendingAttempt($delivery, QuoteDeliveryAttempt::TYPE_GENERATE_PUBLIC_URL);

        if (! $attempt) {
            return false;
        }

        $attempt->markProcessing();

        try {
            $url = $publicLinkService->generateForDelivery($delivery);

            $delivery->refresh();

            $attempt->markSucceeded(['url' => $url]);

            Log::info('quote_delivery.public_url_generated', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
            ]);

            $this->unblockDownstream($delivery, ['sms_blocked_no_url']);

            return true;

        } catch (Throwable $e) {
            $attempt->markFailed(
                errorCode:    'public_url_failed',
                errorMessage: 'The public quote link could not be generated. Please try again.',
                errorDetails: $this->exceptionDetail($e),
            );

            Log::error('quote_delivery.public_url_failed', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function stepSharePoint(
        QuoteDelivery          $delivery,
        QuoteSharePointService $sharePointService,
    ): void {
        // ── Idempotency ───────────────────────────────────────────────────────
        if ($delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_SHAREPOINT_UPLOAD)) {
            return;
        }

        $attempt = $this->findPendingAttempt($delivery, QuoteDeliveryAttempt::TYPE_SHAREPOINT_UPLOAD);

        if (! $attempt) {
            return;
        }

        // ── Guard: SharePoint not configured ──────────────────────────────────
        // If credentials are not set, skip gracefully rather than failing.
        // This allows the app to function without SharePoint configured.
        if (! $this->sharePointConfigured()) {
            $attempt->markSkipped(
                'SharePoint upload skipped: SharePoint is not configured on this environment.'
            );
            return;
        }

        $attempt->markProcessing();

        try {
            $delivery->refresh();

            $webUrl = $sharePointService->upload($delivery);

            $delivery->refresh();

            $attempt->markSucceeded([
                'sharepoint_url'     => $webUrl,
                'sharepoint_file_id' => $delivery->sharepoint_file_id,
            ]);
            // Save the SharePoint PDF URL against the quote.
            $quote = $delivery->quote;

            if ($quote) {
                $quote->update([
                    'sharepoint_file_url' => $webUrl,
                ]);
            }

            Log::info('quote_delivery.sharepoint_uploaded', [
                'delivery_id'    => $delivery->id,
                'quote_id'       => $delivery->quote_id,
                'attempt_id'     => $attempt->id,
                'sharepoint_url' => $webUrl,
            ]);

        } catch (Throwable $e) {
            $attempt->markFailed(
                errorCode:    'sharepoint_upload_failed',
                errorMessage: $sharePointService->humaniseError($e),
                errorDetails: $this->exceptionDetail($e),
            );

            Log::error('quote_delivery.sharepoint_failed', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
                'error'       => $e->getMessage(),
                'class'       => $e::class,
            ]);

            // SharePoint failure does NOT throw — email and SMS proceed normally.
        }
    }

    private function stepEmail(
        QuoteDelivery   $delivery,
        EmailService    $emailService,
        QuotePdfService $pdfService,
        bool            $pdfSucceeded,
        bool            $publicUrlSucceeded,
    ): void {
        if ($delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_EMAIL)) {
            return;
        }

        $attempt = $this->findPendingAttempt($delivery, QuoteDeliveryAttempt::TYPE_EMAIL);

        if (! $attempt) {
            return;
        }

        // ── Guard: recipient ──────────────────────────────────────────────────
        if (empty($delivery->email_address)) {
            $attempt->markSkipped(
                'Email skipped: the quote does not have an email address.'
            );
            return;
        }

        // ── Guard: PDF required ───────────────────────────────────────────────
        if (! $pdfSucceeded || ! $pdfService->pdfExists($delivery)) {
            $attempt->markFailed(
                errorCode:    'email_blocked_no_pdf',
                errorMessage: 'Email could not be sent because the PDF was not generated successfully. Retry PDF generation first.',
                errorDetails: ['pdf_path' => $delivery->pdf_path],
            );
            return;
        }

        $attempt->markProcessing();

        try {
            $delivery->refresh();

            $mailable = new QuoteDeliveryMail($delivery, $pdfService);
            $result   = $emailService->send($delivery->email_address, $mailable);

            if ($result->success) {
                $attempt->markSucceeded([
                    'message_id' => $result->messageId,
                    'to'         => $delivery->email_address,
                ]);

                Log::info('quote_delivery.email_sent', [
                    'delivery_id' => $delivery->id,
                    'attempt_id'  => $attempt->id,
                ]);
            } else {
                $attempt->markFailed(
                    errorCode:    'email_delivery_failed',
                    errorMessage: $result->errorMessage ?? 'The email could not be delivered. Please try again.',
                    errorDetails: $this->exceptionDetail($result->exception),
                );

                Log::error('quote_delivery.email_failed', [
                    'delivery_id' => $delivery->id,
                    'attempt_id'  => $attempt->id,
                    'error'       => $result->errorMessage,
                ]);
            }

        } catch (Throwable $e) {
            $attempt->markFailed(
                errorCode:    'email_exception',
                errorMessage: 'An unexpected error occurred while sending the email. Please try again.',
                errorDetails: $this->exceptionDetail($e),
            );

            Log::error('quote_delivery.email_exception', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function stepSms(
        QuoteDelivery $delivery,
        SmsService    $smsService,
        bool          $publicUrlSucceeded,
    ): void {
        if ($delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_SMS)) {
            return;
        }

        $attempt = $this->findPendingAttempt($delivery, QuoteDeliveryAttempt::TYPE_SMS);

        if (! $attempt) {
            return;
        }

        // ── Guard: recipient ──────────────────────────────────────────────────
        if (empty($delivery->phone_number)) {
            $attempt->markSkipped(
                'SMS skipped: the quote does not have a mobile number.'
            );
            return;
        }

        // ── Guard: public URL required ────────────────────────────────────────
        $delivery->refresh();

        if (! $publicUrlSucceeded || empty($delivery->public_url)) {
            $attempt->markFailed(
                errorCode:    'sms_blocked_no_url',
                errorMessage: 'SMS could not be sent because the public quote link was not generated. Retry link generation first.',
                errorDetails: [],
            );
            return;
        }

        $attempt->markProcessing();

        try {
            $message = $this->buildSmsMessage($delivery);
            $result  = $smsService->send($delivery->phone_number, $message);

            if ($result->success) {
                $attempt->markSucceeded([
                    'provider_sid'    => $result->providerSid,
                    'provider_status' => $result->providerStatus,
                    'to'              => $delivery->phone_number,
                ]);

                Log::info('quote_delivery.sms_sent', [
                    'delivery_id' => $delivery->id,
                    'attempt_id'  => $attempt->id,
                    'sid'         => $result->providerSid,
                ]);
            } else {
                $attempt->markFailed(
                    errorCode:    $result->errorCode ?? 'sms_delivery_failed',
                    errorMessage: $result->errorMessage ?? 'The SMS could not be delivered. Please try again.',
                    errorDetails: $this->exceptionDetail($result->exception),
                );

                Log::error('quote_delivery.sms_failed', [
                    'delivery_id' => $delivery->id,
                    'attempt_id'  => $attempt->id,
                    'error'       => $result->errorMessage,
                ]);
            }

        } catch (Throwable $e) {
            $attempt->markFailed(
                errorCode:    'sms_exception',
                errorMessage: 'An unexpected error occurred while sending the SMS. Please try again.',
                errorDetails: $this->exceptionDetail($e),
            );

            Log::error('quote_delivery.sms_exception', [
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attempt->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Block a dependent step that cannot run yet because a prerequisite failed.
     * Only creates the failed attempt if a pending row exists and the step
     * hasn't already succeeded.
     */
    private function blockDependentStep(
        QuoteDelivery $delivery,
        string        $type,
        string        $errorCode,
        string        $message,
    ): void {
        if ($delivery->hasSucceeded($type)) {
            return;
        }

        $attempt = $this->findPendingAttempt($delivery, $type);

        if (! $attempt) {
            return;
        }

        $attempt->markFailed(
            errorCode:    $errorCode,
            errorMessage: $message,
            errorDetails: [],
        );
    }

    /**
     * When a prerequisite step succeeds, unblock dependent operations that
     * were previously marked failed specifically because of the missing
     * prerequisite. Creates new pending attempt rows so they run in this
     * same job pass.
     */
    private function unblockDownstream(QuoteDelivery $delivery, array $errorCodesToUnblock): void
    {
        $blocked = $delivery->attempts()
            ->whereIn('error_code', $errorCodesToUnblock)
            ->where('status', QuoteDeliveryAttempt::STATUS_FAILED)
            ->get();

        foreach ($blocked as $blockedAttempt) {
            $alreadyPending = $delivery->attempts()
                ->where('type', $blockedAttempt->type)
                ->where('status', QuoteDeliveryAttempt::STATUS_PENDING)
                ->exists();

            if ($alreadyPending) {
                continue;
            }

            $delivery->attempts()->create([
                'type'           => $blockedAttempt->type,
                'status'         => QuoteDeliveryAttempt::STATUS_PENDING,
                'attempt_number' => $delivery->nextAttemptNumber($blockedAttempt->type),
            ]);

            Log::info('quote_delivery.attempt_unblocked', [
                'delivery_id'  => $delivery->id,
                'type'         => $blockedAttempt->type,
                'unblocked_by' => $blockedAttempt->error_code,
            ]);
        }
    }

    private function findPendingAttempt(
        QuoteDelivery $delivery,
        string        $type,
    ): ?QuoteDeliveryAttempt {
        return $delivery->attempts()
            ->where('type', $type)
            ->where('status', QuoteDeliveryAttempt::STATUS_PENDING)
            ->orderByDesc('attempt_number')
            ->first();
    }

    private function buildSmsMessage(QuoteDelivery $delivery): string
    {
        $quote = $delivery->quote;
        $name  = $quote->contact_name ?? $quote->client_name ?? 'there';

        $lines   = [];
        $lines[] = "Hi {$name},";

        $lines[] = "Thank you for choosing All in IT Solutions. See below link with your quote.";

        if (! empty($delivery->sms_message)) {
            $lines[] = trim($delivery->sms_message);
        }

        $lines[] = $delivery->public_url;

        $lines[] = "www.allinit.solutions";

        return implode("\n\n", $lines);
    }

    private function maybeMarkQuoteAsSent(QuoteDelivery $delivery): void
    {
        $emailDelivered = $delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_EMAIL);
        $smsDelivered   = $delivery->hasSucceeded(QuoteDeliveryAttempt::TYPE_SMS);

        if ($emailDelivered || $smsDelivered) {
            $delivery->quote->markAsSent();

            Log::info('quote_delivery.quote_marked_sent', [
                'delivery_id' => $delivery->id,
                'quote_id'    => $delivery->quote_id,
                'via_email'   => $emailDelivered,
                'via_sms'     => $smsDelivered,
            ]);
        }
    }

    /**
     * Returns true only when all four SharePoint config values are present.
     * Allows the app to run in environments without SharePoint configured
     * (e.g. local dev) without crashing.
     */
    private function sharePointConfigured(): bool
    {
        return ! empty(config('services.sharepoint.tenant_id'))
            && ! empty(config('services.sharepoint.client_id'))
            && ! empty(config('services.sharepoint.client_secret'))
            && ! empty(config('services.sharepoint.drive_id'));
    }

    private function exceptionDetail(?Throwable $e): array
    {
        if ($e === null) {
            return [];
        }

        return [
            'exception' => $e::class,
            'message'   => $e->getMessage(),
            'code'      => $e->getCode(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => collect(explode("\n", $e->getTraceAsString()))
                ->take(15)
                ->values()
                ->all(),
        ];
    }
}
