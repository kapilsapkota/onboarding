<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeCustomer extends Model
{
    protected $fillable = [
        'stripe_customer_id',
        'name',
        'email',
        'default_payment_method_id',
        'stripe_data',
        'last_synced_at',
    ];

    protected $casts = [
        'stripe_data'    => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(StripePaymentMethod::class, 'stripe_customer_id');
    }

    public function batchItems(): HasMany
    {
        return $this->hasMany(StripeChargeBatchItem::class, 'stripe_customer_id');
    }

    /** Returns the first active BECS payment method. */
    public function defaultBecsMethod(): ?StripePaymentMethod
    {
        return $this->paymentMethods()
            ->where('type', 'au_becs_debit')
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->first();
    }
}
