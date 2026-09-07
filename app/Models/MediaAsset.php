<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'url',
        'filename',
        'mime_type',
        'size_bytes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function models()
    {
        return $this->morphTo();
    }
}
