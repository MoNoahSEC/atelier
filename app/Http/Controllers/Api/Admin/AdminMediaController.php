<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm', 'model/gltf-binary', 'model/gltf+json'];
    private const ALLOWED_EXTS  = 'jpeg,jpg,png,webp,gif,mp4,webm,glb,gltf';
    private const MAX_SIZE_KB   = 51200; // 50MB for video/3D

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:' . self::ALLOWED_EXTS . '|max:' . self::MAX_SIZE_KB,
        ]);

        $file      = $request->file('file');
        $mimeType  = $file->getMimeType();
        $fileHash  = md5_file($file->path());

        // Deduplication check
        $existing = MediaAsset::where('metadata->hash', $fileHash)->first();
        if ($existing) {
            $existing->is_duplicate = true; // Temporary flag for response
            return response()->json([
                'success' => true,
                'data'    => $existing,
            ]);
        }
        
        $type = 'image';
        if (str_starts_with($mimeType, 'video/')) {
            $type = 'video';
        } elseif (str_starts_with($mimeType, 'model/') || in_array($file->getClientOriginalExtension(), ['glb', 'gltf'])) {
            $type = '3d';
        }

        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path      = $file->storeAs('media', $filename, 'public');

        // Store as a root-relative path so it works in any environment
        // (dev, staging, production) without hardcoding the host.
        // resolveMediaUrl() in the storefront will prefix the correct origin.
        $url = '/storage/' . $path;

        $mediaAsset = MediaAsset::create([
            'type'       => $type,
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $mimeType,
            'size_bytes' => $file->getSize(),
            'metadata'   => ['hash' => $fileHash],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $mediaAsset,
        ]);
    }

    public function index(): JsonResponse
    {
        $media = MediaAsset::latest()->get();
        return response()->json(['success' => true, 'data' => $media]);
    }

    public function destroy(string $filename): JsonResponse
    {
        $asset = MediaAsset::where('filename', $filename)->first();
        if (! $asset) {
            return response()->json(['success' => false, 'message' => 'File not found in database.'], 404);
        }

        // Deletion Protection: Check if this media is referenced in the mediables table
        $usageCount = \Illuminate\Support\Facades\DB::table('mediables')->where('media_asset_id', $asset->id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete media asset. It is currently referenced by {$usageCount} item(s)."
            ], 422);
        }

        $path = 'media/' . $filename;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $asset->delete();

        return response()->json(['success' => true, 'message' => 'File deleted.']);
    }

    /**
     * Update focal point (object-position) for an image.
     * Stores coordinates as "50% 50%" format.
     */
    public function updateFocalPoint(Request $request, string $filename): JsonResponse
    {
        $request->validate([
            'x' => 'required|numeric|min:0|max:100',
            'y' => 'required|numeric|min:0|max:100',
        ]);

        $asset = MediaAsset::where('filename', $filename)->first();
        if (! $asset) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        if ($asset->type !== 'image') {
            return response()->json(['success' => false, 'message' => 'Focal point can only be set on images.'], 422);
        }

        $metadata = $asset->metadata ?? [];
        $metadata['focalPoint'] = "{$request->x}% {$request->y}%";
        $asset->metadata = $metadata;
        $asset->save();

        return response()->json(['success' => true, 'data' => $asset]);
    }

    /**
     * Update crop mode (cover vs contain) for an image.
     */
    public function updateCropMode(Request $request, string $filename): JsonResponse
    {
        $request->validate([
            'cropMode' => 'required|in:cover,contain',
        ]);

        $asset = MediaAsset::where('filename', $filename)->first();
        if (! $asset) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        if ($asset->type !== 'image') {
            return response()->json(['success' => false, 'message' => 'Crop mode can only be set on images.'], 422);
        }

        $metadata = $asset->metadata ?? [];
        $metadata['cropMode'] = $request->cropMode;
        $asset->metadata = $metadata;
        $asset->save();

        return response()->json(['success' => true, 'data' => $asset]);
    }
}
