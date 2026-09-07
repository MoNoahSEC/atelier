<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramRecipient extends Model
{
    protected $fillable = [
        'label',
        'chat_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Return only active recipients. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
