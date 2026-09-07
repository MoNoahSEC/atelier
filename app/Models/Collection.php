<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'image_url',
        'status',
        'sort_order',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function mediaAssets(): MorphToMany
    {
        return $this->morphToMany(MediaAsset::class, 'mediable')->withPivot('group', 'sort_order')->orderByPivot('sort_order');
    }
}
