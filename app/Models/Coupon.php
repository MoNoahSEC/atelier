<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_order_amount_minor' => 'integer',
            'max_discount_minor' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotalMinor): int
    {
        if ($subtotalMinor < $this->min_order_amount_minor) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = (int) round(($subtotalMinor * $this->value) / 100);
            
            if ($this->max_discount_minor !== null && $discount > $this->max_discount_minor) {
                return $this->max_discount_minor;
            }
            
            return min($discount, $subtotalMinor);
        }

        if ($this->type === 'fixed') {
            return min($this->value, $subtotalMinor);
        }

        return 0;
    }
}
