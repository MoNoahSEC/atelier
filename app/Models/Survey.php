<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'question',
        'type',
        'options_json',
        'is_active',
        'target_page',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Compute response summary metrics for this survey.
     */
    public function getMetrics(): array
    {
        $totalResponses = $this->responses()->count();
        $avgRating = $this->type === 'rating' ? round((float)$this->responses()->avg('rating'), 1) : null;
        
        $optionCounts = [];
        if ($this->type === 'single_choice' && !empty($this->options_json)) {
            foreach ($this->options_json as $opt) {
                $optionCounts[$opt] = 0;
            }
            $counts = $this->responses()->selectRaw('selected_option, count(*) as cnt')
                ->groupBy('selected_option')
                ->pluck('cnt', 'selected_option')
                ->toArray();

            foreach ($counts as $k => $v) {
                if ($k) {
                    $optionCounts[$k] = (int)$v;
                }
            }
        }

        return [
            'total_responses' => $totalResponses,
            'average_rating'  => $avgRating,
            'option_counts'   => $optionCounts,
        ];
    }
}
