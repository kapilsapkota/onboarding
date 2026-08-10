<?php

namespace App\Data;

use Throwable;

/**
 * Immutable result returned by SmsService::send().
 *
 * Never throws — the caller decides what to do with a failure.
 */
final class SmsResult
{
    private function __construct(
        public readonly bool       $success,
        public readonly ?string    $providerSid,
        public readonly ?string    $providerStatus,
        public readonly ?string    $errorMessage,
        public readonly ?string    $errorCode,
        public readonly ?Throwable $exception,
    ) {}

    public static function success(string $providerSid, string $providerStatus): self
    {
        return new self(
            success:        true,
            providerSid:    $providerSid,
            providerStatus: $providerStatus,
            errorMessage:   null,
            errorCode:      null,
            exception:      null,
        );
    }

    public static function failure(
        string     $errorMessage,
        ?string    $errorCode = null,
        ?Throwable $exception = null,
    ): self {
        return new self(
            success:        false,
            providerSid:    null,
            providerStatus: null,
            errorMessage:   $errorMessage,
            errorCode:      $errorCode,
            exception:      $exception,
        );
    }
}
