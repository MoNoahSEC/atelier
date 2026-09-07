<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'cart_data_json',
        'abandoned_at',
        'followed_up_at',
        'follow_up_note',
    ];

    protected $casts = [
        'cart_data_json'  => 'array',
        'abandoned_at'    => 'datetime',
        'followed_up_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
