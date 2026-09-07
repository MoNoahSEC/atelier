<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'governorates_json',
        'rate_minor',
        'estimated_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'governorates_json' => 'array',
        'rate_minor'        => 'integer',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
    ];

    /**
     * Find matching shipping zone for a given governorate or city.
     */
    public static function findForGovernorate(?string $governorate): ?self
    {
        if (empty($governorate)) {
            return static::where('is_active', true)->orderBy('sort_order')->first();
        }

        $cleanGov = trim(mb_strtolower($governorate));

        $zones = static::where('is_active', true)->orderBy('sort_order')->get();
        foreach ($zones as $zone) {
            $govs = array_map(fn($g) => mb_strtolower(trim((string)$g)), $zone->governorates_json ?? []);
            if (in_array($cleanGov, $govs, true)) {
                return $zone;
            }
        }

        // Fallback to first active zone
        return $zones->first();
    }
}
