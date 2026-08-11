<?php

namespace App\Services;

use App\Data\SmsResult;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Exceptions\RestException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;

/**
 * Generic SMS service backed by Twilio.
 *
 */
class SmsService
{
    private TwilioClient $client;
    private string       $from;

    public function __construct()
    {
        $this->client = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token'),
        );

        $this->from = config('services.twilio.from');
    }

    /**
     * Send an SMS to the given number.
     *
     * The number is normalised to E.164 format automatically.
     * Always returns an SmsResult — never throws.
     *
     * Does NOT log message body — it may contain personal information.
     */
    public function send(string $to, string $message): SmsResult
    {
        $normalisedTo = $this->normaliseNumber($to);

        try {
            $response = $this->client->messages->create($normalisedTo, [
                'from' => $this->from,
                'body' => $message,
            ]);

            Log::info('sms.sent', [
                'to'     => $normalisedTo,
                'sid'    => $response->sid,
                'status' => (string) $response->status,
            ]);

            return SmsResult::success(
                providerSid:    $response->sid,
                providerStatus: (string) $response->status,
            );

        } catch (RestException $e) {
            Log::error('sms.provider_failed', [
                'to'          => $normalisedTo,
                'twilio_code' => $e->getCode(),
                'http_status' => $e->getStatusCode(),
                'error'       => $e->getMessage(),
            ]);

            return SmsResult::failure(
                errorMessage: $this->humaniseTwilioError($e),
                errorCode:    'twilio_' . $e->getCode(),
                exception:    $e,
            );

        } catch (TwilioException $e) {
            Log::error('sms.twilio_exception', [
                'to'    => $normalisedTo,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure(
                errorMessage: 'The SMS provider returned an unexpected error. Please try again.',
                errorCode:    'twilio_exception',
                exception:    $e,
            );

        } catch (Throwable $e) {
            Log::error('sms.failed', [
                'to'    => $normalisedTo,
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return SmsResult::failure(
                errorMessage: 'The SMS could not be sent. Please try again.',
                exception:    $e,
            );
        }
    }

    /**
     * Normalise a phone number to E.164 format.
     *
     * Handles the most common Australian formats:
     *   04XXXXXXXX  → +614XXXXXXXX
     *   4XXXXXXXX   → +614XXXXXXXX
     *   614XXXXXXXX → +614XXXXXXXX
     *   +XXXXXXXXXX → unchanged (already E.164)
     *
     * For other countries: if the number already starts with + it is
     * returned as-is. Otherwise it is returned cleaned but not modified —
     * Twilio will reject it with a clear error (21211) if invalid.
     */
    public function normaliseNumber(string $number): string
    {
        // Strip common formatting characters.
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $number);

        // Australian mobile: 04XXXXXXXX
        if (preg_match('/^04\d{8}$/', $cleaned)) {
            return '+61' . substr($cleaned, 1);
        }

        // Australian mobile without leading zero: 4XXXXXXXX
        if (preg_match('/^4\d{8}$/', $cleaned)) {
            return '+61' . $cleaned;
        }

        // Australian with country code but no +: 614XXXXXXXX
        if (preg_match('/^614\d{8}$/', $cleaned)) {
            return '+' . $cleaned;
        }

        // Already E.164.
        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        // Return cleaned — Twilio will validate.
        return $cleaned;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Translate a Twilio RestException into a safe, human-readable message.
     * Error codes: https://www.twilio.com/docs/api/errors
     */
    private function humaniseTwilioError(RestException $e): string
    {
        return match ($e->getCode()) {
            21211 => 'The mobile number is not a valid phone number.',
            21214 => 'The mobile number must include a country code.',
            21217 => 'The mobile number does not appear to be a valid phone number.',
            21401 => 'The mobile number is invalid.',
            21408 => 'SMS delivery to this country or region is not enabled.',
            21610 => 'This number has unsubscribed from SMS messages.',
            21612 => 'The destination number is not reachable via SMS.',
            21614 => 'The mobile number is not SMS-capable.',
            30003 => 'The mobile number is unreachable — the handset may be off.',
            30004 => 'SMS delivery was blocked by the carrier for this number.',
            30005 => 'The mobile number is unknown or no longer in service.',
            30006 => 'The destination appears to be a landline and cannot receive SMS.',
            30007 => 'The SMS was filtered by the carrier.',
            30008 => 'The SMS was rejected by the carrier.',
            default => str_contains(strtolower($e->getMessage()), 'authenticate')
                ? 'SMS provider authentication failed. Please contact support.'
                : 'The SMS could not be delivered. Please check the mobile number and try again.',
        };
    }
}
