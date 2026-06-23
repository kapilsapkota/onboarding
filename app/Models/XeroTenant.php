<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XeroTenant extends Model
{
    protected $fillable = [
        'xero_connection_id',
        'tenant_id',
        'tenant_name',
        'tenant_type',
        'is_active',
        'dd_bank_account_id',
        'dd_bank_account_name',
        'last_contact_synced_at',
        'last_invoice_synced_at',
        'last_payment_synced_at',
        'last_repeating_invoice_synced_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_contact_synced_at' => 'datetime',
        'last_invoice_synced_at' => 'datetime',
        'last_repeating_invoice_synced_at' => 'datetime',
        'last_payment_synced_at' => 'datetime',
    ];
    public function connection()
    {
        return $this->belongsTo(XeroConnection::class, 'xero_connection_id');
    }
    public function getRouteKeyName()
    {
        return 'id'; // or tenant_id if needed
    }
    public function hasDdBankAccount(): bool
    {
        return ! empty($this->dd_bank_account_id);
    }

    public function contacts()
    {
        return $this->hasMany(XeroContact::class, 'xero_tenant_id', 'xero_tenant_id');
    }
}
