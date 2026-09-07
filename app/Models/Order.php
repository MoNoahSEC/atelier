<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_email',
        'customer_name',
        'customer_phone',
        'currency',
        'subtotal_minor',
        'shipping_minor',
        'tax_minor',
        'discount_minor',
        'cod_surcharge_minor',
        'total_amount_minor',
        'total_price_minor',
        'status',
        'payment_status',
        'payment_method',
        'provider_ref',
        'shipping_status',
        'fulfillment_status',
        'tracking_number',
        'carrier',
        'external_order_id',
        'notes',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_minor'    => 'integer',
            'shipping_minor'    => 'integer',
            'tax_minor'         => 'integer',
            'discount_minor'     => 'integer',
            'cod_surcharge_minor' => 'integer',
            'total_amount_minor'  => 'integer',
            'metadata_json'     => 'array',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePendingFulfillment(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('fulfillment_status', 'unfulfilled')
            ->where('payment_status', 'paid');
    }

    public function scopeForCustomerEmail(\Illuminate\Database\Eloquent\Builder $query, string $email): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('customer_email', mb_strtolower(trim($email)));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isFullyFulfilled(): bool
    {
        return $this->fulfillment_status === 'fulfilled';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeFulfilled(): bool
    {
        return $this->isPaid()
            && in_array($this->fulfillment_status, ['unfulfilled', 'partially_fulfilled'], true);
    }

    // ─── Dynamic Accessors & Aliases ──────────────────────────────────────────

    public function getTotalPriceMinorAttribute(): int
    {
        return (int) ($this->total_amount_minor ?? 0);
    }

    public function setTotalPriceMinorAttribute(mixed $value): void
    {
        $this->attributes['total_amount_minor'] = (int) $value;
    }

    public function getStatusAttribute(): string
    {
        return $this->shipping_status ?? $this->fulfillment_status ?? 'pending';
    }

    public function setStatusAttribute(string $value): void
    {
        $this->attributes['shipping_status'] = $value;
        $this->attributes['fulfillment_status'] = match ($value) {
            'delivered', 'completed' => 'fulfilled',
            'cancelled'              => 'cancelled',
            default                  => 'unfulfilled',
        };
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->metadata_json['name'] 
            ?? $this->metadata_json['customer_name'] 
            ?? ($this->customer?->name ?? null);
    }

    public function setCustomerNameAttribute(?string $value): void
    {
        $meta = $this->metadata_json ?? [];
        $meta['customer_name'] = $value;
        $this->metadata_json = $meta;
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->metadata_json['phone'] 
            ?? $this->metadata_json['customer_phone'] 
            ?? ($this->customer?->phone ?? null);
    }

    public function setCustomerPhoneAttribute(?string $value): void
    {
        $meta = $this->metadata_json ?? [];
        $meta['customer_phone'] = $value;
        $this->metadata_json = $meta;
    }

    public function getShippingAddressJsonAttribute(): array
    {
        return $this->metadata_json['shipping_address'] 
            ?? $this->metadata_json['address'] 
            ?? [];
    }
}
