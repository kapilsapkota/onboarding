<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class XeroRepeatingInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'xero_tenant_id',
        'xero_repeating_invoice_id',
        'type',
        'status',

        'xero_contact_id',
        'xero_contact_xero_id',

        'schedule_period',
        'schedule_period_type',
        'schedule_due_date',
        'schedule_due_date_type',
        'schedule_start_date',
        'schedule_next_scheduled_date',
        'schedule_end_date',

        'currency_code',
        'sub_total',
        'total_tax',
        'total',
        'line_items',
        'reference',
        'xero_branding_theme_id',
        'has_attachments',
        'last_synced_at',
    ];

    protected $casts = [
        'has_attachments'             => 'boolean',
        'sub_total'                   => 'decimal:2',
        'total_tax'                   => 'decimal:2',
        'total'                       => 'decimal:2',
        'line_items'                  => 'array',
        'schedule_start_date'         => 'date',
        'schedule_next_scheduled_date' => 'date',
        'schedule_end_date'           => 'date',
        'last_synced_at'              => 'datetime',
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

    public function xeroContact()
    {
        return $this->belongsTo(XeroContact::class, 'xero_contact_id');
    }

    /**
     * Invoices generated from this repeating template.
     */
    public function generatedInvoices()
    {
        return $this->hasMany(XeroInvoice::class, 'xero_repeating_invoice_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Human-readable schedule description, e.g. "Every 1 month".
     */
    public function getScheduleSummaryAttribute(): string
    {
        if (! $this->schedule_period || ! $this->schedule_period_type) {
            return 'Unknown schedule';
        }

        $period = $this->schedule_period === 1 ? '' : $this->schedule_period . ' ';
        $type   = match (strtoupper($this->schedule_period_type)) {
            'WEEKLY'  => $this->schedule_period === 1 ? 'weekly'  : 'weeks',
            'MONTHLY' => $this->schedule_period === 1 ? 'monthly' : 'months',
            'YEARLY'  => $this->schedule_period === 1 ? 'yearly'  : 'years',
            'DAILY'   => $this->schedule_period === 1 ? 'daily'   : 'days',
            default   => strtolower($this->schedule_period_type),
        };

        return $this->schedule_period === 1
            ? ucfirst($type)
            : "Every {$period}{$type}";
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'AUTHORISED')
            ->where(function ($q) {
                $q->whereNull('schedule_end_date')
                    ->orWhere('schedule_end_date', '>=', today());
            });
    }
}
