<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'title',
        'attribute_name',
        'attribute_value',
        'price_override_minor',
        'attributes_json',
        'image_url',
        'cost_price_minor',
        'retail_price_minor',
        'inventory',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price_minor'     => 'integer',
            'retail_price_minor'   => 'integer',
            'price_override_minor' => 'integer',
            'inventory'            => 'integer',
            'attributes_json'      => 'array',
        ];
    }

    public function getEffectivePriceMinorAttribute(): int
    {
        if ($this->price_override_minor !== null && $this->price_override_minor > 0) {
            return $this->price_override_minor;
        }

        if ($this->retail_price_minor !== null && $this->retail_price_minor > 0) {
            return $this->retail_price_minor;
        }

        return $this->product?->retail_price_minor ?? 0;
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('inventory', '>', 0);
    }
}
