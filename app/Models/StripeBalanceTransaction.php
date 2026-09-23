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
        'type',
        'reporting_category',
        'currency',
        'amount',
        'fee',
        'net',
        'available_at',
        'occurred_at',
        'stripe_payout_id',
        'stripe_data',
        'last_synced_at',
    ];

    protected $casts = [
        'stripe_data' => 'array',
        'available_at' => 'datetime',
        'occurred_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

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
