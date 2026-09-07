<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCollectionController extends Controller
{
    public function index(): JsonResponse
    {
        $collections = Collection::latest()->get();
        return response()->json(['success' => true, 'data' => $collections]);
    }

    public function show(int $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        return response()->json(['success' => true, 'data' => $collection]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:collections,slug',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|url',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $collection = Collection::create($validated);
        return response()->json(['success' => true, 'data' => $collection], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'slug'        => "sometimes|string|max:255|unique:collections,slug,{$id}",
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string',
        ]);

        $collection->update($validated);
        return response()->json(['success' => true, 'data' => $collection]);
    }

    public function destroy(int $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();
        return response()->json(['success' => true, 'message' => 'Collection deleted.']);
    }
}
