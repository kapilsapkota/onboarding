<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteDelivery extends Model
{
    // -------------------------------------------------------------------------
    // Overall delivery statuses
    // -------------------------------------------------------------------------
    const STATUS_PENDING          = 'pending';
    const STATUS_PROCESSING       = 'processing';
    const STATUS_COMPLETED        = 'completed';
    const STATUS_PARTIALLY_FAILED = 'partially_failed';
    const STATUS_FAILED           = 'failed';
    const STATUS_CANCELLED        = 'cancelled';

    protected $fillable = [
        'quote_id',
        'requested_by',
        'status',
        'send_email',
        'send_sms',
        'email_address',
        'phone_number',
        'email_subject',
        'email_message',
        'sms_message',
        'public_token',
        'public_url',
        'pdf_disk',
        'pdf_path',
        'pdf_filename',
        'pdf_size',
        'sharepoint_file_id',
        'sharepoint_url',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'send_email'   => 'boolean',
        'send_sms'     => 'boolean',
        'pdf_size'     => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'failed_at'    => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuoteDeliveryAttempt::class);
    }

    // -------------------------------------------------------------------------
    // Attempt helpers
    //
    // These are used by jobs to make idempotency decisions:
    //   "Has PDF generation already succeeded? Skip it."
    //   "What attempt number should this retry use?"
    // -------------------------------------------------------------------------

    /**
     * Returns the most recent attempt record for a given operation type,
     * or null if no attempt has ever been made.
     */
    public function latestAttemptOfType(string $type): ?QuoteDeliveryAttempt
    {
        return $this->attempts()
            ->where('type', $type)
            ->orderByDesc('attempt_number')
            ->first();
    }

    /**
     * Returns true if any attempt for this type has status = succeeded.
     * This is the primary idempotency check in all jobs.
     */
    public function hasSucceeded(string $type): bool
    {
        return $this->attempts()
            ->where('type', $type)
            ->where('status', QuoteDeliveryAttempt::STATUS_SUCCEEDED)
            ->exists();
    }

    /**
     * Returns true if the latest attempt for this type is in a failed state.
     */
    public function latestAttemptFailed(string $type): bool
    {
        $latest = $this->latestAttemptOfType($type);

        return $latest?->status === QuoteDeliveryAttempt::STATUS_FAILED;
    }

    /**
     * Returns the next sequential attempt number for a given type.
     * First attempt = 1, first retry = 2, etc.
     */
    public function nextAttemptNumber(string $type): int
    {
        $max = $this->attempts()
            ->where('type', $type)
            ->max('attempt_number');

        return ($max ?? 0) + 1;
    }

    // -------------------------------------------------------------------------
    // Status calculation
    //
    // The overall delivery status is derived from the individual attempt
    // statuses rather than being set manually. This prevents the overall status
    // from diverging from reality.
    //
    // Rules (applied in order):
    //
    // 1. If PDF generation failed → failed (nothing can proceed without a PDF)
    // 2. If all requested channels succeeded → completed
    // 3. If all requested channels failed or skipped → failed
    // 4. If some channels succeeded and some failed → partially_failed
    // 5. Otherwise → processing (still in progress)
    // -------------------------------------------------------------------------

    public function recalculateStatus(): string
    {
        // Rule 1: PDF failure is a hard failure for the entire delivery.
        if ($this->latestAttemptFailed(QuoteDeliveryAttempt::TYPE_GENERATE_PDF)) {
            return self::STATUS_FAILED;
        }

        $requestedTypes = [];

        if ($this->send_email) {
            $requestedTypes[] = QuoteDeliveryAttempt::TYPE_EMAIL;
        }

        if ($this->send_sms) {
            $requestedTypes[] = QuoteDeliveryAttempt::TYPE_SMS;
        }

        if (empty($requestedTypes)) {
            return self::STATUS_FAILED;
        }

        $succeeded = 0;
        $failedOrSkipped = 0;

        foreach ($requestedTypes as $type) {
            $latest = $this->latestAttemptOfType($type);

            if (! $latest) {
                // Still pending — delivery is still processing.
                continue;
            }

            if ($latest->status === QuoteDeliveryAttempt::STATUS_SUCCEEDED) {
                $succeeded++;
            } elseif (in_array($latest->status, [
                QuoteDeliveryAttempt::STATUS_FAILED,
                QuoteDeliveryAttempt::STATUS_SKIPPED,
            ])) {
                $failedOrSkipped++;
            }
        }

        $total = count($requestedTypes);

        // Rule 2
        if ($succeeded === $total) {
            return self::STATUS_COMPLETED;
        }

        // Rule 3
        if ($failedOrSkipped === $total) {
            return self::STATUS_FAILED;
        }

        // Rule 4
        if ($succeeded > 0) {
            return self::STATUS_PARTIALLY_FAILED;
        }

        // Rule 5
        return self::STATUS_PROCESSING;
    }

    /**
     * Recalculates and persists the overall status.
     * Called at the end of every job.
     */
    public function updateStatus(): void
    {
        $status = $this->recalculateStatus();

        $updates = ['status' => $status];

        if ($status === self::STATUS_COMPLETED) {
            $updates['completed_at'] = $this->completed_at ?? now();
        }

        if (in_array($status, [self::STATUS_FAILED, self::STATUS_PARTIALLY_FAILED])) {
            $updates['failed_at'] = $this->failed_at ?? now();
        }

        $this->update($updates);
    }

    // -------------------------------------------------------------------------
    // Display helpers
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING          => 'Pending',
            self::STATUS_PROCESSING       => 'Processing',
            self::STATUS_COMPLETED        => 'Completed',
            self::STATUS_PARTIALLY_FAILED => 'Partially Failed',
            self::STATUS_FAILED           => 'Failed',
            self::STATUS_CANCELLED        => 'Cancelled',
            default                       => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED        => 'green',
            self::STATUS_PARTIALLY_FAILED => 'yellow',
            self::STATUS_FAILED           => 'red',
            self::STATUS_CANCELLED        => 'gray',
            self::STATUS_PROCESSING       => 'blue',
            default                       => 'gray',
        };
    }

    public function hasPdf(): bool
    {
        return ! empty($this->pdf_path);
    }

    public function hasPublicUrl(): bool
    {
        return ! empty($this->public_url);
    }
}
