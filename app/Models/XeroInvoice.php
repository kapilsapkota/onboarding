<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XeroInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'xero_tenant_id',
        'xero_invoice_id',        // InvoiceID (UUID from Xero)
        'xero_invoice_number',    // InvoiceNumber e.g. "INV-0001"
        'xero_branding_theme_id', // BrandingThemeID
        'type',                   // ACCREC (AR invoice) | ACCPAY (AP bill)
        'status',                 // DRAFT | SUBMITTED | AUTHORISED | PAID | VOIDED | DELETED

        'xero_contact_id',        // FK to xero_contacts.id (local)
        'xero_contact_xero_id',   // raw ContactID from Xero payload (for joins before contact is synced)

        // Dates
        'invoice_date',
        'due_date',
        'fully_paid_on_date',

        // Reference / narrative
        'reference',              // free-text PO / reference number
        'url',                    // external URL attached to invoice in Xero
        'sent_to_contact',        // bool – has Xero emailed this to the contact?

        // Monetary amounts (all stored in invoice currency)
        'currency_code',
        'currency_rate',          // exchange rate to org base currency
        'sub_total',              // tax-exclusive total of line items
        'total_tax',
        'total',                  // sub_total + total_tax
        'total_discount',         // sum of line-level discounts
        'amount_due',             // remaining balance
        'amount_paid',            // total payments applied
        'amount_credited',        // total credit notes applied

        // Line items (stored as JSON – mirrors Xero's LineItems array)
        'line_items',

        // Metadata
        'has_attachments',
        'xero_updated_at',        // UpdatedDateUTC from Xero
        'last_synced_at',         // when we last pulled this record from Xero

        // Payment / direct-debit tracking (application-level fields)
        'payment_initiated_at',   // when a payment run / DD was triggered in our app
        'payment_method',         // e.g. direct_debit | bank_transfer | card | bpay
        'payment_reference',      // our internal payment reference / batch ID
        'payment_status',         // pending | processing | settled | failed | cancelled
        'payment_failed_at',      // timestamp of failure (if applicable)
        'payment_failure_reason',  // human-readable failure message
        'payment_settled_at',     // timestamp when funds actually cleared

        // Reconciliation
        'client_id',              // FK to local clients table (if matched)
        'is_reconciled',          // has this invoice been reconciled against a local record?
        'xero_repeating_invoice_id'
    ];

    protected $casts = [
        // Booleans
        'sent_to_contact'  => 'boolean',
        'has_attachments'  => 'boolean',
        'is_reconciled'    => 'boolean',

        // Decimals
        'currency_rate'    => 'decimal:6',
        'sub_total'        => 'decimal:2',
        'total_tax'        => 'decimal:2',
        'total'            => 'decimal:2',
        'total_discount'   => 'decimal:2',
        'amount_due'       => 'decimal:2',
        'amount_paid'      => 'decimal:2',
        'amount_credited'  => 'decimal:2',

        // JSON
        'line_items' => 'array',

        // Dates
        'invoice_date'          => 'date',
        'due_date'              => 'date',
        'fully_paid_on_date'    => 'date',
        'xero_updated_at'       => 'datetime',
        'last_synced_at'        => 'datetime',
        'payment_initiated_at'  => 'datetime',
        'payment_failed_at'     => 'datetime',
        'payment_settled_at'    => 'datetime',
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

    /**
     * The local XeroContact record (may be null until contacts are synced).
     */
    public function xeroContact()
    {
        return $this->belongsTo(XeroContact::class, 'xero_contact_xero_id', 'xero_contact_id');
    }

    /**
     * The matched local client (if reconciliation has been run).
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isOverdue(): bool
    {
        return $this->amount_due > 0
            && $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->status, ['PAID', 'VOIDED', 'DELETED']);
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID' || $this->amount_due <= 0;
    }

    /**
     * Mark that a payment run / direct debit has been initiated in our system.
     */
    public function markPaymentInitiated(string $method, ?string $reference = null): void
    {
        $this->update([
            'payment_method'       => $method,
            'payment_reference'    => $reference,
            'payment_status'       => 'pending',
            'payment_initiated_at' => now(),
        ]);
    }

    public function markPaymentSettled(): void
    {
        $this->update([
            'payment_status'     => 'settled',
            'payment_settled_at' => now(),
        ]);
    }

    public function markPaymentFailed(string $reason): void
    {
        $this->update([
            'payment_status'         => 'failed',
            'payment_failed_at'      => now(),
            'payment_failure_reason' => $reason,
        ]);
    }

    public function markSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('xero_tenant_id', $tenantId);
    }

    /** AR invoices (money owed TO us). */
    public function scopeReceivable($query)
    {
        return $query->where('type', 'ACCREC');
    }

    /** AP bills (money we OWE). */
    public function scopePayable($query)
    {
        return $query->where('type', 'ACCPAY');
    }

    public function scopeAuthorised($query)
    {
        return $query->where('status', 'AUTHORISED');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('amount_due', '>', 0)
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['PAID', 'VOIDED', 'DELETED']);
    }

    public function scopeWithPendingPayment($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeWithFailedPayment($query)
    {
        return $query->where('payment_status', 'failed');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Human-readable label combining type + number, e.g. "INV-0042" or "BILL-0007".
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->xero_invoice_number ?? $this->xero_invoice_id ?? '—';
    }

    /**
     * Days overdue (negative = not yet due).
     */
    public function getDaysOverdueAttribute(): int
    {
        if ($this->due_date === null) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false) * -1;
    }

    public function directDebitPayments() : HasMany
    {
        return $this->hasMany(DirectDebitPayment::class);
    }

    public function repeatingInvoice() : BelongsTo
    {
        return $this->belongsTo(XeroRepeatingInvoice::class, 'xero_repeating_invoice_id');
    }

    // In XeroInvoice model — add alongside directDebitPayments()
    public function latestDirectDebitPayment()
    {
        return $this->hasOne(DirectDebitPayment::class, 'xero_invoice_id')->latestOfMany();
    }
}
