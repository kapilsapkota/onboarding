<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripePaymentMethod extends Model
{
    protected $fillable = [
        'stripe_customer_id',
        'stripe_payment_method_id',
        'type',
        'last4',
        'account_holder_name',
        'is_default',
        'status',
        'stripe_data',
        'last_synced_at',
    ];

    protected $casts = [
        'stripe_data'    => 'array',
        'is_default'     => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function stripeCustomer(): BelongsTo
    {
        return $this->belongsTo(StripeCustomer::class, 'stripe_customer_id');
    }

    /** Returns masked display string e.g. BECS ••••1234. */
    public function maskedLabel(): string
    {
        return 'BECS ••••' . $this->last4;
    }
}
