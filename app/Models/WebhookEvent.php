<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'event_type',
        'idempotency_key',
        'payload_json',
        'processed_at',
        'failed_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'payload_json'  => 'array',
            'processed_at'  => 'datetime',
            'failed_at'     => 'datetime',
            'attempts'      => 'integer',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeUnprocessed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('processed_at')->whereNull('failed_at');
    }

    public function scopeProcessed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('failed_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }
}
