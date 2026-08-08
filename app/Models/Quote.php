<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quote_number',
        'client_name',
        'contact_name',
        'email',
        'mobile',
        'website',
        'logo_url',
        'sharepoint_file_url',
        'sharepoint_source_url',
        'status',
        'subtotal',
        'gst_amount',
        'total',
        'notes',
        'terms',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'expires_at',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'gst_amount'    => 'decimal:2',
        'total'         => 'decimal:2',
        'sent_at'       => 'datetime',
        'accepted_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'expires_at'   => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    // -----------------------------------------------------------------------
    // Boot / auto-number
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = static::generateQuoteNumber();
            }
        });

        static::saved(function (Quote $quote) {
            $quote->recalculateTotals();
        });
    }

    public static function generateQuoteNumber(): string
    {
        $year = now()->year;

        $last = static::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('quote_number');

        $next = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'Q-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('unit_price') + $this->items()->sum('setup_fee');
        $gst      = round($subtotal * config('quote.gst_rate',0.10), 2);

        $this->withoutEvents(function () use ($subtotal, $gst) {
            $this->update([
                'subtotal'   => $subtotal,
                'gst_amount' => $gst,
                'total'      => $subtotal + $gst,
            ]);
        });
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'sent'     => ['label' => 'Sent',     'class' => 'bg-blue-100 text-blue-800'],
            'accepted' => ['label' => 'Accepted', 'class' => 'bg-green-100 text-green-800'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-red-100 text-red-800'],
            default    => ['label' => 'Draft',    'class' => 'bg-gray-100 text-gray-600'],
        };
    }

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    public function signatures() : HasMany
    {
        return $this->hasMany(QuoteSignature::class);
    }
    public function getPdfFilenameAttribute(): string
    {
        $categories = $this->items
            ->map(function ($item) {
                return $item->product?->category?->name
                    ?? $item->category_name;
            })
            ->filter()
            ->unique()
            ->implode(' - ');

        $client = $this->client_name ?: 'Client';

        $date = now()->format('d-M-Y');

        return "AIIT - Proposal To {$client} - {$categories} - by AT-{$date}.pdf";
    }

}
