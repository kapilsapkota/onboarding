<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'short_name',
        'description',
        'scope_items',
        'key_scope_keyword',
        'price_type',
        'fixed_price',
        'setup_fee',
        'price_min',
        'price_max',
        'price_increment',
        'hourly_rate',
        'frequency',
        'image_url',
        'notes',
        'is_active',
        'sort_order',
        'quote_default'
    ];

    protected $casts = [
        'scope_items'     => 'array',
        'fixed_price'     => 'decimal:2',
        'setup_fee'       => 'decimal:2',
        'price_min'       => 'decimal:2',
        'price_max'       => 'decimal:2',
        'price_increment' => 'decimal:2',
        'hourly_rate'     => 'decimal:2',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate the list of dropdown price options.
     */
    public function getPriceOptions(): array
    {
        if ($this->price_type !== 'dropdown') {
            return [];
        }

        $options = [];
        $current = (float) $this->price_min;
        $max     = (float) $this->price_max;
        $step    = (float) $this->price_increment;

        while ($current <= $max + 0.001) {
            $options[] = round($current, 2);
            $current += $step;
        }

        return $options;
    }

    /**
     * Calculate hours for a given price.
     */
    public function calculateHours(float $price): ?float
    {
        if (! $this->hourly_rate || $this->hourly_rate <= 0) {
            return null;
        }

        return round($price / (float) $this->hourly_rate, 1);
    }

    /**
     * Return the default price for the product.
     */
    public function getDefaultPrice(): float
    {
        return (float) ($this->price_type === 'fixed' ? $this->fixed_price : $this->price_min);
    }

    /**
     * Formatted frequency label.
     */
    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'monthly'     => 'Monthly',
            'quarterly'   => 'Quarterly',
            'annually'    => 'Annually',
            default       => 'Once Off',
        };
    }
}
