<?php

namespace App\Data;

use Throwable;

/**
 * Immutable result returned by EmailService::send().
 *
 * Never throws — the caller decides what to do with a failure.
 */
final class EmailResult
{
    private function __construct(
        public readonly bool       $success,
        public readonly ?string    $messageId,
        public readonly ?string    $errorMessage,
        public readonly ?Throwable $exception,
    ) {}

    public static function success(?string $messageId = null): self
    {
        return new self(
            success:      true,
            messageId:    $messageId,
            errorMessage: null,
            exception:    null,
        );
    }

    public static function failure(string $errorMessage, ?Throwable $exception = null): self
    {
        return new self(
            success:      false,
            messageId:    null,
            errorMessage: $errorMessage,
            exception:    $exception,
        );
    }
}
