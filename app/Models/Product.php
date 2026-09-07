<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'slug',
        'title',
        'description',
        'image_url',
        'cost_price_minor',
        'retail_price_minor',
        'compare_at_price_minor',
        'currency',
        'inventory',
        'low_stock_threshold',
        'material',
        'weight',
        'dimensions',
        'seo_title',
        'seo_description',
        'attributes_json',
        'status',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'cost_price_minor'        => 'integer',
            'retail_price_minor'      => 'integer',
            'compare_at_price_minor'  => 'integer',
            'inventory'               => 'integer',
            'low_stock_threshold'     => 'integer',
            'attributes_json'         => 'array',
        ];
    }

    public function getEffectiveLowStockThreshold(): int
    {
        return $this->low_stock_threshold 
            ?? (int) Setting::get('low_stock_threshold', 5);
    }

    public function isLowStock(): bool
    {
        return $this->inventory <= $this->getEffectiveLowStockThreshold();
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function mediaAssets(): MorphToMany
    {
        return $this->morphToMany(MediaAsset::class, 'mediable')->withPivot('group', 'sort_order')->orderByPivot('sort_order');
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

    public function scopeForSupplier(\Illuminate\Database\Eloquent\Builder $query, int $supplierId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('supplier_id', $supplierId);
    }
}
