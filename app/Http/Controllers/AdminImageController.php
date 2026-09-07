<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminImageController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE_KB = 5120; // 5MB

    /**
     * Replace the main product cover image.
     */
    public function replaceProductImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_SIZE_KB,
        ], [
            'image.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'image.max'   => 'The image size cannot exceed 5MB.',
        ]);

        $product = Product::findOrFail($id);
        $file = $request->file('image');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        // If previous main image was stored locally, optionally delete old file
        $product->update(['image_url' => $url]);

        return response()->json([
            'success'   => true,
            'message'   => "Main image updated for \"{$product->title}\".",
            'image_url' => $url,
            'filename'  => $filename,
        ]);
    }

    /**
     * Replace a specific gallery media asset.
     */
    public function replaceMediaAsset(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_SIZE_KB,
        ], [
            'image.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'image.max'   => 'The image size cannot exceed 5MB.',
        ]);

        $asset = MediaAsset::findOrFail($id);
        $file = $request->file('image');

        // Delete old file if exists
        $oldPath = 'media/' . $asset->filename;
        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        $asset->update([
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => "Gallery image updated successfully.",
            'image_url' => $url,
            'filename'  => $filename,
            'asset_id'  => $asset->id,
        ]);
    }

    /**
     * Upload and attach a new gallery image to a product.
     */
    public function uploadGalleryImage(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_SIZE_KB,
        ], [
            'image.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'image.max'   => 'The image size cannot exceed 5MB.',
        ]);

        $product = Product::findOrFail($productId);
        $file = $request->file('image');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        $asset = MediaAsset::create([
            'type'       => 'image',
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'metadata'   => ['hash' => md5_file($file->path())],
        ]);

        $nextSort = ($product->mediaAssets()->max('sort_order') ?? 0) + 1;
        $product->mediaAssets()->attach($asset->id, ['group' => 'gallery', 'sort_order' => $nextSort]);

        return response()->json([
            'success'   => true,
            'message'   => "New gallery image attached to \"{$product->title}\".",
            'image_url' => $url,
            'asset_id'  => $asset->id,
            'filename'  => $filename,
        ], 201);
    }

    /**
     * Replace the promotional homepage banner image.
     */
    public function replacePromoBanner(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_SIZE_KB,
        ], [
            'image.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'image.max'   => 'The banner image size cannot exceed 5MB.',
        ]);

        $file = $request->file('image');
        $filename = 'banner_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        Setting::set('homepage_banner_image', $url);
        Setting::set('homeBannerImage', $url);

        return response()->json([
            'success'   => true,
            'message'   => 'Homepage promotional banner updated successfully.',
            'image_url' => $url,
        ]);
    }

    /**
     * Replace a collection cover image.
     */
    public function replaceCollectionImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_SIZE_KB,
        ], [
            'image.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'image.max'   => 'The collection image size cannot exceed 5MB.',
        ]);

        $collection = Collection::findOrFail($id);
        $file = $request->file('image');

        $filename = 'collection_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        $collection->update(['image_url' => $url]);

        return response()->json([
            'success'   => true,
            'message'   => "Collection image updated for \"{$collection->title}\".",
            'image_url' => $url,
        ]);
    }

    /**
     * Replace Client Access Portal / Account Login Background.
     */
    public function replacePortalBackground(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_SIZE_KB,
        ], [
            'image.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'image.max'   => 'The portal background image size cannot exceed 5MB.',
        ]);

        $file = $request->file('image');
        $filename = 'portal_bg_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        Setting::set('account_portal_background_url', $url);

        return response()->json([
            'success'   => true,
            'message'   => 'Client Access Portal background wallpaper updated successfully.',
            'image_url' => $url,
        ]);
    }
}
