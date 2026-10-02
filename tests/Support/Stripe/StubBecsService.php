<?php

namespace Tests\Support\Stripe;

use App\Models\StripeAccount;
use App\Services\StripeBecsService;

class StubBecsService extends StripeBecsService
{
    public function __construct(?StripeAccount $account = null) {}

    public function getBalanceTransaction(string $paymentIntentId): array
    {
        return [
            'gross' => 100.0, 'fee' => 1.75, 'net' => 98.25,
            'currency' => 'AUD', 'stripe_bt_id' => 'txn_test123',
        ];
    }
}
