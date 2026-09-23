<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StripeBalanceTransaction extends Model
{
    protected $fillable = [
        'stripe_balance_transaction_id',
        'source_id',
        'source_type',
        'type',
        'reporting_category',
        'status',
        'balance_type',
        'exchange_rate',
        'description',
        'currency',
        'amount',
        'fee',
        'net',
        'fee_details',
        'available_at',
        'occurred_at',
        'stripe_payout_id',
        'payout_stripe_id',
        'charge_stripe_id',
        'payment_intent_stripe_id',
        'customer_stripe_id',
        'is_app_transaction',
        'stripe_data',
        'last_synced_at',
    ];

    protected $casts = [
        'stripe_data' => 'array',
        'fee_details' => 'array',
        'available_at' => 'datetime',
        'occurred_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_app_transaction' => 'boolean',
    ];

    public function scopeApp($query)
    {
        return $query->where('is_app_transaction', true);
    }

    public function scopeExternal($query)
    {
        return $query->where('is_app_transaction', false);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(
            StripePayout::class,
            'stripe_payout_id'
        );
    }

    public function batchItem(): HasOne
    {
        return $this->hasOne(
            StripeChargeBatchItem::class,
            'stripe_balance_transaction_id',
            'stripe_balance_transaction_id'
        );
    }
}
