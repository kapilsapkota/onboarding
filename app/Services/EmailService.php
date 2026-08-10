<?php

namespace App\Services;

use App\Data\EmailResult;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

/**
 * Generic email service.
 *
 * Wraps Laravel Mail with consistent error handling and structured logging.
 * Reusable anywhere in the application — not specific to quotes.
 *
 * Usage:
 *   $result = app(EmailService::class)->send('client@example.com', new SomeMailable());
 *   if (!$result->success) { ... }
 */
class EmailService
{
    /**
     * Send a Mailable to the given address.
     *
     * Always returns an EmailResult — never throws.
     * Logs success and failure with structured context.
     * Does NOT log message body or personal content.
     */
    public function send(string $to, Mailable $mailable): EmailResult
    {
        try {
            $sent = Mail::to($to)->send($mailable);

            $messageId = $sent?->getMessageId();

            Log::info('email.sent', [
                'to'         => $to,
                'message_id' => $messageId,
            ]);

            return EmailResult::success($messageId);

        } catch (TransportException $e) {
            Log::error('email.transport_failed', [
                'to'    => $to,
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return EmailResult::failure(
                errorMessage: $this->humaniseTransportError($e),
                exception:    $e,
            );

        } catch (Throwable $e) {
            Log::error('email.failed', [
                'to'    => $to,
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return EmailResult::failure(
                errorMessage: 'The email could not be sent. Please try again.',
                exception:    $e,
            );
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Translate a transport exception into a safe, human-readable message.
     * Uses the actual exception message to pick the most accurate description.
     */
    private function humaniseTransportError(Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'connection refused')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'could not connect')
            || str_contains($message, 'network unreachable')) {
            return 'The mail server could not be reached. Please try again later.';
        }

        if (str_contains($message, '550')
            || str_contains($message, 'user unknown')
            || str_contains($message, 'no such user')
            || str_contains($message, 'mailbox not found')) {
            return 'The mail server rejected the recipient address.';
        }

        if (str_contains($message, '421')
            || str_contains($message, 'service not available')
            || str_contains($message, 'service temporarily unavailable')) {
            return 'The mail server is temporarily unavailable. Please try again.';
        }

        if (str_contains($message, 'authentication')
            || str_contains($message, '535')
            || str_contains($message, 'credentials')) {
            return 'Mail server authentication failed. Please contact support.';
        }

        if (str_contains($message, 'rate limit')
            || str_contains($message, 'too many')) {
            return 'Too many emails sent. Please wait a moment and try again.';
        }

        return 'The email could not be delivered. Please try again.';
    }
}
