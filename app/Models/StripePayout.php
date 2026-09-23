<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripePayout extends Model
{
    protected $fillable = [
        'stripe_payout_id',
        'stripe_created_at',
        'status',
        'reconciliation_status',
        'type',
        'method',
        'currency',
        'amount',
        'arrival_at',
        'paid_at',
        'destination',
        'trace_id',
        'description',
        'statement_descriptor',
        'automatic',
        'failure_code',
        'failure_message',
        'failure_balance_transaction_stripe_id',
        'balance_transaction_stripe_id',
        'stripe_data',
        'last_synced_at',
    ];

    protected $casts = [
        'stripe_data' => 'array',
        'arrival_at' => 'datetime',
        'paid_at' => 'datetime',
        'stripe_created_at' => 'datetime',
        'automatic' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(
            StripeBalanceTransaction::class,
            'stripe_payout_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            StripeChargeBatchItem::class,
            'stripe_payout_id'
        );
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2);
    }
}
