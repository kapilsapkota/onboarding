<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XeroTenant extends Model
{
    use LogsActivity;

    protected $fillable = [
        'xero_connection_id',
        'company_id',
        'default_stripe_account_id',
        'tenant_id',
        'tenant_name',
        'tenant_type',
        'is_active',
        'dd_bank_account_id',
        'dd_bank_account_name',
        'last_contact_synced_at',
        'last_invoice_synced_at',
        'last_payment_synced_at',
        'last_repeating_invoice_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_contact_synced_at' => 'datetime',
        'last_invoice_synced_at' => 'datetime',
        'last_repeating_invoice_synced_at' => 'datetime',
        'last_payment_synced_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(XeroConnection::class, 'xero_connection_id');
    }

    /**
     * Owning business. The tenant is the source of truth; the company
     * groups Stripe accounts, clients and direct-debit runs under it.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Preferred Stripe account for collections against this organisation.
     */
    public function defaultStripeAccount(): BelongsTo
    {
        return $this->belongsTo(StripeAccount::class, 'default_stripe_account_id');
    }

    public function getRouteKeyName()
    {
        return 'id'; // or tenant_id if needed
    }

    public function hasDdBankAccount(): bool
    {
        return ! empty($this->dd_bank_account_id);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(XeroContact::class, 'xero_tenant_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(XeroInvoice::class, 'xero_tenant_id');
    }

    public function repeatingInvoices(): HasMany
    {
        return $this->hasMany(XeroRepeatingInvoice::class, 'xero_tenant_id');
    }
}
