<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeChargeBatchItem extends Model
{
    protected $fillable = [
        'batch_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'amount',
        'description',
        'currency',
        'stripe_payment_intent_id',
        'status',
        'stripe_data',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'stripe_data'  => 'array',
        'processed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StripeChargeBatch::class, 'batch_id');
    }

    public function stripeCustomer(): BelongsTo
    {
        return $this->belongsTo(StripeCustomer::class, 'stripe_customer_id');
    }

    public function stripePaymentMethod(): BelongsTo
    {
        return $this->belongsTo(StripePaymentMethod::class, 'stripe_payment_method_id');
    }

    /** Returns the Stripe idempotency key for this item's PaymentIntent. */
    public function idempotencyKey(): string
    {
        return 'bulk-charge-item-' . $this->id;
    }

    /** Formats amount cents as a dollar string. */
    public function formattedAmount(): string
    {
        return '$' . number_format($this->amount / 100, 2);
    }
}
