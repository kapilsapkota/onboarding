<?php

namespace Tests\Support\Stripe;

use App\Http\Controllers\StripeWebhookController;
use App\Services\StripeBecsService;

class StubWebhookController extends StripeWebhookController
{
    public static ?StripeBecsService $stub = null;

    protected function becs(): StripeBecsService
    {
        return static::$stub ?? parent::becs();
    }
}
