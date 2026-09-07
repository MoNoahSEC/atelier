<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'page_type',
        'page_id',
        'visitor_id',
        'url',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'page_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'page_id');
    }

    /**
     * Record a page view quickly and non-blockingly.
     */
    public static function track(string $pageType, ?int $pageId = null, ?string $url = null): void
    {
        try {
            $visitorId = request()->cookie('atelier_visitor_id');
            if (!$visitorId) {
                $visitorId = (string) \Illuminate\Support\Str::uuid();
                \Illuminate\Support\Facades\Cookie::queue('atelier_visitor_id', $visitorId, 60 * 24 * 365);
            }

            static::create([
                'page_type'  => $pageType,
                'page_id'    => $pageId,
                'visitor_id' => $visitorId,
                'url'        => $url ?? request()->path(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently ignore analytics logging failures to avoid impacting visitor UX
        }
    }
}
