<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'status',
        'seo_title',
        'seo_description',
    ];

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }
}
