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
        'last_contact_sync_at',
        'last_invoice_synced_at',
        'last_payment_synced_at',
        'last_repeating_invoice_synced_at'
    ];

    public function connection()
    {
        return $this->belongsTo(XeroConnection::class, 'xero_connection_id');
    }
    public function getRouteKeyName()
    {
        return 'id'; // or tenant_id if needed
    }
}
