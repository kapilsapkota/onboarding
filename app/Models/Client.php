<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'company_name',
        'industry',
        'website',
        'address',
        'city',
        'country',
        'post_code',
        'abn',
        'billing_email',
        'monthly_budget',
        'referred_by',
        'instagram',
        'facebook',
        'tiktok',
        'linkedin',
        'twitter',
        'whatsapp_group',
        'logo_path',
        'contacts_file_path',
        'pasted_employees',
        'notes',
        'status',
        'company_phone',
        'address_second',
        'state',
        'service_providers',
        'services',
        'bank_name',
        'bank_branch',
        'account_name',
        'account_number',
        'bsb',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'mandate_id',
        'mandate_status',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class)->orderByDesc('is_primary');
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->contacts->firstWhere('is_primary', true)?->email
            ?? $this->contacts->first()?->email;
    }

    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->contacts->firstWhere('is_primary', true)?->phone
            ?? $this->contacts->first()?->phone;
    }

    public function getPrimaryContactNameAttribute(): ?string
    {
        return $this->contacts->firstWhere('is_primary', true)?->full_name
            ?? $this->contacts->first()?->full_name;
    }
    public function charges()
    {
        return $this->hasMany(ClientCharge::class)->latest();
    }
}
