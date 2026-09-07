<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->with(['sections' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->with('mediaAssets');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $page
        ]);
    }
}
