<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'carrier',
        'tracking_number',
        'status',
        'shipped_at',
        'delivered_at',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at'    => 'datetime',
            'delivered_at'  => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeShipped(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'delivered');
    }
}
