<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StripeAccount extends Model
{
    protected $fillable = [
        'public_id',
        'company_id',
        'display_name',
        'publishable_key',
        'secret_key',
        'webhook_secret',
        'is_default',
        'is_legacy',
        'status',
    ];

    protected $casts = [
        'secret_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'is_default' => 'boolean',
        'is_legacy' => 'boolean',
    ];

    protected $hidden = [
        'secret_key',
        'webhook_secret',
    ];

    protected static function booted(): void
    {
        static::creating(function (StripeAccount $account): void {
            if (empty($account->public_id)) {
                $account->public_id = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stripeCustomers(): HasMany
    {
        return $this->hasMany(StripeCustomer::class);
    }

    public function stripePaymentMethods(): HasMany
    {
        return $this->hasMany(StripePaymentMethod::class);
    }

    public function chargeBatches(): HasMany
    {
        return $this->hasMany(StripeChargeBatch::class);
    }

    public function stripePayouts(): HasMany
    {
        return $this->hasMany(StripePayout::class);
    }

    public function stripeBalanceTransactions(): HasMany
    {
        return $this->hasMany(StripeBalanceTransaction::class);
    }

    public function directDebitPayments(): HasMany
    {
        return $this->hasMany(DirectDebitPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mark this account as the default for its company, unsetting the flag
     * on any sibling account. Works for company-less (legacy) accounts too.
     */
    public function setAsDefault(): void
    {
        static::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Public DDR URL for this account. Resolved server-side by public_id;
     * the id is random and reveals nothing about the company or keys.
     */
    public function ddrUrl(): string
    {
        return route('ddr.account', ['publicId' => $this->public_id]);
    }
}
