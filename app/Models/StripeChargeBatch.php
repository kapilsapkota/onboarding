<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeChargeBatch extends Model
{
    protected $fillable = [
        'reference',
        'customer_count',
        'total_amount',
        'currency',
        'status',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StripeChargeBatchItem::class, 'batch_id');
    }

    /** Recalculates and saves batch status based on item statuses. */
    public function recalculateStatus(): void
    {
        $items = $this->items()->get();

        $total     = $items->count();
        $succeeded = $items->where('status', 'succeeded')->count();
        $failed    = $items->where('status', 'failed')->count();
        $pending   = $items->whereIn('status', ['pending', 'processing'])->count();

        if ($pending > 0) {
            $status = 'processing';
        } elseif ($succeeded === $total) {
            $status = 'completed';
        } elseif ($failed === $total) {
            $status = 'failed';
        } else {
            $status = 'completed_with_errors';
        }

        $this->update([
            'status'       => $status,
            'completed_at' => $pending === 0 ? now() : null,
        ]);
    }

    /** Formats total_amount cents as a dollar string. */
    public function formattedTotal(): string
    {
        return '$' . number_format($this->total_amount / 100, 2);
    }

    public function createdBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
