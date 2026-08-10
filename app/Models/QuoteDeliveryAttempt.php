<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteDeliveryAttempt extends Model
{
    // -------------------------------------------------------------------------
    // Types — one row per operation per attempt
    // -------------------------------------------------------------------------
    const TYPE_GENERATE_PDF        = 'generate_pdf';
    const TYPE_GENERATE_PUBLIC_URL = 'generate_public_url';
    const TYPE_SHAREPOINT_UPLOAD   = 'sharepoint_upload';
    const TYPE_EMAIL               = 'email';
    const TYPE_SMS                 = 'sms';

    // -------------------------------------------------------------------------
    // Statuses
    // -------------------------------------------------------------------------
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCEEDED  = 'succeeded';
    const STATUS_FAILED     = 'failed';
    const STATUS_SKIPPED    = 'skipped';

    protected $fillable = [
        'quote_delivery_id',
        'type',
        'status',
        'attempt_number',
        'started_at',
        'completed_at',
        'failed_at',
        'error_code',
        'error_message',
        'error_details',
        'metadata',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'failed_at'    => 'datetime',
        'error_details' => 'array',
        'metadata'      => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(QuoteDelivery::class, 'quote_delivery_id');
    }

    // -------------------------------------------------------------------------
    // State transitions
    //
    // All state changes go through these methods so timestamps are always set
    // consistently and we never forget to record when something happened.
    // -------------------------------------------------------------------------

    public function markProcessing(): void
    {
        $this->update([
            'status'     => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markSucceeded(array $metadata = []): void
    {
        $this->update([
            'status'       => self::STATUS_SUCCEEDED,
            'completed_at' => now(),
            'metadata'     => empty($metadata) ? null : $metadata,
        ]);
    }

    /**
     * @param  string  $errorCode     Short machine-readable code, e.g. "twilio_invalid_number"
     * @param  string  $errorMessage  Safe human-readable message shown in the UI
     * @param  array   $errorDetails  Full technical detail — exception class, trace, provider body
     */
    public function markFailed(
        string $errorCode,
        string $errorMessage,
        array  $errorDetails = []
    ): void {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'failed_at'     => now(),
            'error_code'    => $errorCode,
            'error_message' => $errorMessage,
            'error_details' => empty($errorDetails) ? null : $errorDetails,
        ]);
    }

    /**
     * Used when an operation is deliberately not attempted.
     * e.g. "Email skipped: no email address on quote."
     */
    public function markSkipped(string $reason): void
    {
        $this->update([
            'status'        => self::STATUS_SKIPPED,
            'completed_at'  => now(),
            'error_message' => $reason,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isRetryable(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_GENERATE_PDF        => 'Generate PDF',
            self::TYPE_GENERATE_PUBLIC_URL => 'Generate Public Link',
            self::TYPE_SHAREPOINT_UPLOAD   => 'SharePoint Upload',
            self::TYPE_EMAIL               => 'Email',
            self::TYPE_SMS                 => 'SMS',
            default                        => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING    => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SUCCEEDED  => 'Succeeded',
            self::STATUS_FAILED     => 'Failed',
            self::STATUS_SKIPPED    => 'Skipped',
            default                 => ucfirst($this->status),
        };
    }
}
