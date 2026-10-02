<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('activity:prune')->daily();

// Safety net: anything the webhooks missed (missed charge.succeeded,
// transient Stripe errors) gets reconciled by PaymentIntent id.
Schedule::command('stripe:reconcile-items --days=7')->dailyAt('02:30')->withoutOverlapping();

// Keeps payout rows + their BT lines (and payout links on items) in sync
// for the legacy pool and every active Stripe account.
Schedule::command('stripe:sync-payouts --days=30')->dailyAt('03:00')->withoutOverlapping();
