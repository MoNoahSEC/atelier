<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Collection;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        $collections = Collection::withCount('products')->orderBy('sort_order')->get();
        return view('admin.collections.index', compact('collections'));
    }

    public function create(): View
    {
        return view('admin.collections.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer|min:0',
            'status'      => 'required|in:active,draft',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $slug = Str::slug($request->title);
        $base = $slug;
        $i = 1;
        while (Collection::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'collection-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $imageUrl = '/storage/' . $path;

            MediaAsset::create([
                'type'       => 'image',
                'url'        => $imageUrl,
                'filename'   => $filename,
                'mime_type'  => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }

        $collection = Collection::create([
            'title'       => $request->title,
            'slug'        => $slug,
            'description' => $request->description,
            'image_url'   => $imageUrl,
            'sort_order'  => (int) ($request->sort_order ?? 0),
            'status'      => $request->status,
        ]);

        AuditLog::log('collection.create', 'collection', $collection->id, "Created collection: {$collection->title}");

        return redirect()->route('admin.collections.index')->with('success', "Collection \"{$collection->title}\" created successfully!");
    }

    public function edit(int $id): View
    {
        $collection = Collection::with('products')->findOrFail($id);
        return view('admin.collections.edit', compact('collection'));
    }

    public function update(Request $request, int $id)
    {
        $collection = Collection::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer|min:0',
            'status'      => 'required|in:active,draft',
        ]);

        $collection->update([
            'title'       => $request->title,
            'description' => $request->description,
            'sort_order'  => (int) ($request->sort_order ?? $collection->sort_order),
            'status'      => $request->status,
        ]);

        AuditLog::log('collection.update', 'collection', $collection->id, "Updated collection: {$collection->title}");

        return back()->with('success', "Collection \"{$collection->title}\" updated successfully!");
    }

    public function replaceImage(Request $request, int $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $collection = Collection::findOrFail($id);
        $file = $request->file('image');
        $filename = 'collection-cover-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        $asset = MediaAsset::create([
            'type'       => 'image',
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $collection->image_url = $url;
        $collection->save();

        AuditLog::log('collection.image_replace', 'collection', $collection->id, "Replaced cover image for collection {$collection->title}");

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'image_url' => $url]);
        }

        return back()->with('success', 'Collection cover image updated successfully!');
    }

    public function destroy(int $id)
    {
        $collection = Collection::findOrFail($id);
        $title = $collection->title;
        $collection->products()->detach();
        $collection->delete();

        AuditLog::log('collection.delete', 'collection', $id, "Deleted collection: {$title}");

        return redirect()->route('admin.collections.index')->with('success', "Collection \"{$title}\" deleted.");
    }
}
