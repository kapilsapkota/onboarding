<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripePayout extends Model
{
    protected $fillable = [
        'stripe_payout_id',
        'status',
        'type',
        'method',
        'currency',
        'amount',
        'arrival_at',
        'paid_at',
        'destination',
        'description',
        'stripe_data',
        'last_synced_at',
    ];

    protected $casts = [
        'stripe_data' => 'array',
        'arrival_at' => 'datetime',
        'paid_at' => 'datetime',
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
