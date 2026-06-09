<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class XeroContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'xero_tenant_id',
        'xero_contact_id',
        'xero_contact_number',
        'xero_account_number',
        'xero_contact_status',

        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'bank_account_details',
        'company_number',
        'tax_number',
        'tax_number_type',
        'accounts_receivable_tax_type',
        'accounts_payable_tax_type',

        'addresses',
        'phones',

        'is_supplier',
        'is_customer',
        'default_currency',

        'xero_updated_at',
        'synced_at',

        'client_id',

        'match_score',
        'match_method',
        'is_matched',
    ];

    protected $casts = [
        'is_supplier' => 'boolean',
        'is_customer'  => 'boolean',
        'is_matched'   => 'boolean',

        'match_score'  => 'float',

        'addresses'    => 'array',
        'phones'       => 'array',

        'xero_updated_at' => 'datetime',
        'synced_at'       => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tenant()
    {
        return $this->belongsTo(XeroTenant::class, 'xero_tenant_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers (Reconciliation)
    |--------------------------------------------------------------------------
    */

    public function isMatched(): bool
    {
        return $this->is_matched && $this->client_id !== null;
    }

    public function markMatched(Client $client, float $score, string $method): void
    {
        $this->update([
            'client_id'    => $client->id,
            'match_score'  => $score,
            'match_method' => $method,
            'is_matched'   => true,
        ]);
    }

    public function clearMatch(): void
    {
        $this->update([
            'client_id'    => null,
            'match_score'  => null,
            'match_method' => null,
            'is_matched'   => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (important for large orgs)
    |--------------------------------------------------------------------------
    */

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('xero_tenant_id', $tenantId);
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_customer', true);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('is_supplier', true);
    }

    public function scopeMatched($query)
    {
        return $query->where('is_matched', true);
    }

    public function scopeUnmatched($query)
    {
        return $query->where('is_matched', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (optional convenience)
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name
            ?? trim("{$this->first_name} {$this->last_name}")
            ?? $this->name;
    }
}
