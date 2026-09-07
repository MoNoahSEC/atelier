<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    // Audit logs are immutable records — no mass assignment
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'actor_id'    => 'integer',
            'entity_id'   => 'integer',
            'changes_json' => 'array',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForEntity(\Illuminate\Database\Eloquent\Builder $query, string $entityType, int $entityId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeByActor(\Illuminate\Database\Eloquent\Builder $query, string $actorType, int $actorId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('actor_type', $actorType)->where('actor_id', $actorId);
    }

    public function scopeOfAction(\Illuminate\Database\Eloquent\Builder $query, string $action): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('action', $action);
    }

    public static function log(string $action, string $entityType, int $entityId, ?string $summary = null, array $changes = []): self
    {
        $actorId = auth()->id() ?? 0;
        $actorType = auth()->check() ? 'admin' : 'system';
        $ip = request()->ip() ?? '127.0.0.1';

        return static::create([
            'actor_type'   => $actorType,
            'actor_id'     => $actorId,
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'ip_address'   => $ip,
            'user_agent'   => substr((string) request()->userAgent(), 0, 255),
            'changes_json' => array_merge(['summary' => $summary], $changes),
        ]);
    }
}

