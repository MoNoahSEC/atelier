<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminPageController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = Page::latest()->get();
        return response()->json(['success' => true, 'data' => $pages]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug'  => 'nullable|string|max:255|unique:pages,slug',
        ]);

        $slug = $request->slug ?: Str::slug($request->title);

        $page = Page::create([
            'title' => $request->title,
            'slug' => $slug,
            'status' => 'draft',
        ]);

        return response()->json(['success' => true, 'data' => $page], 201);
    }

    public function show(int $id): JsonResponse
    {
        $page = Page::with('sections')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $page]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::with('sections.mediaAssets')->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'slug'  => 'required|string|max:255|unique:pages,slug,' . $id,
            'status' => 'required|in:draft,published',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
        ]);

        // ─── Publish Guard ────────────────────────────────────────────────
        if ($request->status === 'published' && $page->status !== 'published') {
            $errors = [];
            foreach ($page->sections as $section) {
                // Validate that every section's type is known
                $knownTypes = ['hero', 'product_carousel', 'video_feature', '3d_showcase'];
                if (!in_array($section->type, $knownTypes)) {
                    $errors[] = "Section #{$section->id} has unsupported type: '{$section->type}'";
                }
            }
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page cannot be published due to validation errors.',
                    'errors'  => $errors,
                ], 422);
            }
        }

        $page->update($request->only('title', 'slug', 'status', 'seo_title', 'seo_description'));

        // Trigger ISR revalidation when publishing
        if ($request->status === 'published') {
            $this->triggerRevalidation($page->slug);
        }

        return response()->json(['success' => true, 'data' => $page]);
    }

    private function triggerRevalidation(string $slug): void
    {
        $nextUrl = config('services.nextjs.revalidate_url');
        $secret  = config('services.nextjs.revalidate_secret');

        if (!$nextUrl || !$secret) {
            return; // Not configured — skip silently in local dev
        }

        try {
            Http::withToken($secret)->post($nextUrl, [
                'path'   => "/{$slug}",
                'secret' => $secret,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CMS revalidation failed', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return response()->json(['success' => true, 'message' => 'Page deleted']);
    }

    // ─── Sections ────────────────────────────────────────────────────────────

    public function storeSection(Request $request, int $pageId): JsonResponse
    {
        $page = Page::findOrFail($pageId);

        $request->validate([
            'type' => 'required|string',
            'settings' => 'nullable|array',
            'sort_order' => 'integer',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'exists:media_assets,id',
        ]);

        $section = $page->sections()->create([
            'type' => $request->type,
            'settings' => $request->settings ?? [],
            'sort_order' => $request->sort_order ?? 0,
        ]);

        if ($request->has('media_asset_ids')) {
            $section->mediaAssets()->sync($request->media_asset_ids);
        }

        return response()->json(['success' => true, 'data' => $section], 201);
    }

    public function updateSection(Request $request, int $pageId, int $sectionId): JsonResponse
    {
        $section = PageSection::where('page_id', $pageId)->findOrFail($sectionId);

        $request->validate([
            'settings' => 'nullable|array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'exists:media_assets,id',
        ]);

        $section->update($request->only('settings', 'sort_order', 'is_active'));

        if ($request->has('media_asset_ids')) {
            $section->mediaAssets()->sync($request->media_asset_ids);
        }

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function destroySection(int $pageId, int $sectionId): JsonResponse
    {
        $section = PageSection::where('page_id', $pageId)->findOrFail($sectionId);
        $section->delete();

        return response()->json(['success' => true, 'message' => 'Section deleted']);
    }
}
