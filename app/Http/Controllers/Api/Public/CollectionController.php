<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    public function index(): JsonResponse
    {
        $collections = Collection::where('status', 'active')
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'title', 'description', 'image_url']);

        return response()->json([
            'success' => true,
            'data'    => $collections,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $collection = Collection::where('status', 'active')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $collection,
        ]);
    }
}
