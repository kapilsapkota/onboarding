<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'product_id',
        'category_name',
        'product_name',
        'scope_of_works',
        'key_scope_keyword',
        'unit_price',
        'gst_amount',
        'total_price',
        'hours',
        'hourly_rate',
        'frequency',
        'image_url',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'gst_amount'   => 'decimal:2',
        'total_price'  => 'decimal:2',
        'hours'        => 'decimal:2',
        'hourly_rate'  => 'decimal:2',
        'sort_order'   => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // -----------------------------------------------------------------------
    // Boot: auto-calculate GST & total on save
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::saving(function (QuoteItem $item) {
            $item->gst_amount  = round((float) $item->unit_price * 0.10, 2);
            $item->total_price = round((float) $item->unit_price + $item->gst_amount, 2);

            if ($item->hourly_rate && $item->hourly_rate > 0) {
                $item->hours = round((float) $item->unit_price / (float) $item->hourly_rate, 1);
            }
        });
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'monthly'   => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly'    => 'Yearly',
            default     => 'Once Off',
        };
    }

    public function getScopeListAttribute(): array
    {
        if (! $this->scope_of_works) {
            return [];
        }

        return array_filter(array_map('trim', explode("\n", $this->scope_of_works)));
    }
}
