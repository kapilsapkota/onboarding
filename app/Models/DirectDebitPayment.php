<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectDebitPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'xero_invoice_id',
        'xero_tenant_id',
        'client_id',

        'xero_invoice_xero_id',
        'xero_invoice_number',
        'amount',
        'currency_code',

        'payment_method',
        'gateway',
        'gateway_payment_id',
        'gateway_batch_id',
        'our_reference',

        'status',
        'initiated_at',
        'submitted_to_gateway_at',
        'settled_at',
        'failed_at',
        'cancelled_at',

        'failure_code',
        'failure_reason',

        'attempt_number',
        'retry_of_id',

        'xero_payment_id',
        'xero_bank_account_id',
        'xero_payment_posted_at',
        'xero_post_error',
        'xero_post_attempted',

        'initiated_by_type',
        'initiated_by_user_id',
        'stripe_fee',
        'stripe_net',
        'stripe_balance_transaction_id',
    ];

    protected $casts = [
        'amount'                    => 'decimal:2',
        'xero_post_attempted'       => 'boolean',
        'initiated_at'              => 'datetime',
        'submitted_to_gateway_at'   => 'datetime',
        'settled_at'                => 'datetime',
        'failed_at'                 => 'datetime',
        'cancelled_at'              => 'datetime',
        'xero_payment_posted_at'    => 'datetime',
        'stripe_fee' => 'decimal:2',
        'stripe_net' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoice() : BelongsTo
    {
        return $this->belongsTo(XeroInvoice::class, 'xero_invoice_id');
    }

    public function tenant() : BelongsTo
    {
        return $this->belongsTo(XeroTenant::class, 'xero_tenant_id');
    }

    public function client() : BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function retryOf()
    {
        return $this->belongsTo(DirectDebitPayment::class, 'retry_of_id');
    }

    public function retries()
    {
        return $this->hasMany(DirectDebitPayment::class, 'retry_of_id');
    }

    public function initiatedByUser() : BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'initiated_by_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */

    /**
     * Build the standard array used when creating a DirectDebitPayment from
     * an invoice. Centralises the field mapping so both the scheduled job
     * and the manual controller action stay in sync.
     */
    public static function dataFromInvoice(
        XeroInvoice $invoice,
        string      $initiatedByType = 'scheduled',
        ?int        $initiatedByUserId = null,
        ?float      $overrideAmount = null,
        ?Client     $client = null,
    ): array {
        return [
            'xero_invoice_id'      => $invoice->id,
            'xero_tenant_id'       => $invoice->xero_tenant_id,
            'client_id'            => $client->id,
            'xero_invoice_xero_id' => $invoice->xero_invoice_id,
            'xero_invoice_number'  => $invoice->xero_invoice_number,
            'amount'               => $overrideAmount ?? $invoice->amount_due,
            'currency_code'        => $invoice->currency_code ?? 'AUD',
            'payment_method'       => 'direct_debit',
            'our_reference'        => 'DD-' . $invoice->xero_invoice_number . '-' . now()->format('Ymd'),
            'status'               => 'pending',
            'initiated_at'         => now(),
            'initiated_by_type'    => $initiatedByType,
            'initiated_by_user_id' => $initiatedByUserId,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers
    |--------------------------------------------------------------------------
    */

    public function markSubmitted(string $gatewayPaymentId, ?string $batchId = null): void
    {
        $this->update([
            'status'                    => 'processing',
            'gateway_payment_id'        => $gatewayPaymentId,
            'gateway_batch_id'          => $batchId,
            'submitted_to_gateway_at'   => now(),
        ]);
    }

    public function markSettled(array $balanceTx = []): void
    {
        $this->update([
            'status'     => 'settled',
            'settled_at' => now(),
            'stripe_fee'                     => $balanceTx['fee'] ?? null,
            'stripe_net'                     => $balanceTx['net'] ?? null,
            'stripe_balance_transaction_id'  => $balanceTx['stripe_bt_id'] ?? null,
        ]);
    }

    public function markFailed(string $reason, ?string $code = null): void
    {
        $this->update([
            'status'         => 'failed',
            'failed_at'      => now(),
            'failure_reason' => $reason,
            'failure_code'   => $code,
        ]);
    }

    public function markXeroPosted(string $xeroPaymentId): void
    {
        $this->update([
            'xero_payment_id'        => $xeroPaymentId,
            'xero_payment_posted_at' => now(),
            'xero_post_attempted'    => true,
            'xero_post_error'        => null,
        ]);
    }

    public function markXeroPostFailed(string $error): void
    {
        $this->update([
            'xero_post_attempted' => true,
            'xero_post_error'     => $error,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSettled($query)
    {
        return $query->where('status', 'settled');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeAwaitingXeroPostback($query)
    {
        return $query
            ->where('status', 'settled')
            ->where('xero_post_attempted', false);
    }
}
