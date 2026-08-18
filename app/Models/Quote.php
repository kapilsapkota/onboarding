<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

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
        'subtotal'    => 'decimal:2',
        'gst_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
        'sent_at'     => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    protected $appends = ['email_subject'];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(QuoteSignature::class);
    }

    /**
     * All delivery records for this quote, newest first.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(QuoteDelivery::class)->latest();
    }

    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'parent_quote_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Quote::class, 'parent_quote_id')
            ->orderBy('revision_number');
    }

    public function rootQuote(): Quote
    {
        return $this->parent_quote_id
            ? $this->parentQuote->rootQuote()
            : $this;
    }
    /**
     * The most recent delivery — used by the show page to display
     * current delivery status without loading all history.
     */
    public function latestDelivery(): HasOne
    {
        return $this->hasOne(QuoteDelivery::class)->latestOfMany();
    }

    // -------------------------------------------------------------------------
    // Boot / auto-number
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = static::generateQuoteNumber();
            }
        });

//        static::updating(function (Quote $quote) {
//            /*
//             * Once a quote has been sent or accepted, it becomes immutable.
//             *
//             * We still allow the system to transition:
//             *
//             * sent -> accepted
//             *
//             * and to update the relevant timestamp fields.
//             */
//            if ($quote->getOriginal('status') === 'sent') {
//                $allowed = [
//                    'status',
//                    'accepted_at',
//                ];
//
//                $changed = array_keys($quote->getDirty());
//
//                $unauthorisedChanges = array_diff($changed, $allowed);
//
//                if (! empty($unauthorisedChanges)) {
//                    throw new LogicException(
//                        'This quote has already been sent and can no longer be edited.'
//                    );
//                }
//            }
//
//            /*
//             * Accepted quotes are completely locked.
//             */
//            if ($quote->getOriginal('status') === 'accepted') {
//                throw new LogicException(
//                    'This quote has been accepted and can no longer be edited.'
//                );
//            }
//        });


        static::saved(function (Quote $quote) {
            $quote->recalculateTotals();
        });


    }

    // -------------------------------------------------------------------------
    // Locking
    // -------------------------------------------------------------------------

    public function isLocked(): bool
    {
        return in_array($this->status, [
            'sent',
            'accepted',
        ], true);
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked();
    }
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    public function canChangeStatus(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return match ($this->status) {
            'draft' => in_array($newStatus, ['draft', 'sent', 'rejected'], true),

            // Sent can be manually accepted/rejected by admin.
            'sent' => in_array($newStatus, ['sent', 'accepted', 'rejected'], true),

            // Final states cannot be changed.
            'accepted',
            'rejected' => $newStatus === $this->status,

            default => false,
        };
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()
            ->get()
            ->sum(function ($item) {
                return ($item->unit_price * $item->quantity) + $item->setup_fee;
            });

        $gst = round(
            $subtotal * config('quote.gst_rate', 0.10),
            2
        );

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

    /**
     * Mark the quote as sent.
     *
     * Business rule: called only when at least one delivery channel
     * (email or SMS) succeeds. A delivery that only skips channels
     * does not mark the quote as sent.
     */
    public function markAsSent(): void
    {
        if (is_null($this->sent_at)) {
            $this->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        }
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
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
            ->map(fn ($category) => $this->sanitizeFilenamePart($category))
            ->implode(' - ');

        $client = $this->sanitizeFilenamePart($this->client_name ?: '');
        $date   = now()->format('d-m-y');

        return "AIIT - Proposal To {$client} - {$categories} - By AT-{$date}.pdf";
    }

    public function getEmailSubjectAttribute(): string
    {
        $categories = $this->items
            ->map(function ($item) {
                return $item->product?->category?->name
                    ?? $item->category_name;
            })
            ->filter()
            ->unique()
            ->implode(' | ');

        $client = $this->client_name ?: '-';
        $quote_number = $this->quote_number;

        return $categories
            ? "{$client} - {$quote_number} - {$categories}"
            : $client;
    }
    private function sanitizeFilenamePart(?string $value): string
    {
        if (!$value) {
            return '';
        }

        // Remove characters that are invalid/problematic in filenames.
        $value = preg_replace('/[\/\\\\:*?"<>|]/', '', $value);

        // Replace multiple spaces with a single space.
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value, " .-");
    }

    public function sendModalData(): array
    {
        return [
            'id'           => $this->id,
            'quote_number' => $this->quote_number,
            'email_subject' => $this->email_subject,
            'client_name'  => $this->client_name,
            'contact_name' => $this->contact_name,
            'email'        => $this->email,
            'mobile'       => $this->mobile,
            'total'        => $this->total,
            'created_at'   => $this->created_at?->toISOString(),
            'expires_at'   => $this->expires_at?->toISOString(),
            'items'        => $this->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price,
                    'total_price'  => $item->total_price,
                ];
            })->values()->all(),
        ];
    }
}
