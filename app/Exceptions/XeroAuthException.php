<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Xero access token and its refresh token are both unusable,
 * meaning an admin must re-authorize the connection via OAuth.
 *
 * Catch this specifically in controllers to show the "reconnect" alert.
 * Do NOT catch it silently — it always requires human intervention.
 */
class XeroAuthException extends RuntimeException
{
    public function __construct(string $message = 'Xero connection requires re-authorization.')
    {
        parent::__construct($message);
    }
}
