<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_transaction_id',
        'amount_minor',
        'currency',
        'status',
        'idempotency_key',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor'  => 'integer',
            'metadata_json' => 'array',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'completed');
    }
}
