<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Collection;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductSkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['collections', 'mediaAssets', 'variants'])->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('collection_id')) {
            $query->whereHas('collections', function ($q) use ($request) {
                $q->where('collections.id', $request->input('collection_id'));
            });
        }

        $products = $query->paginate(15)->withQueryString();
        $collections = Collection::orderBy('sort_order')->get();

        return view('admin.products.index', compact('products', 'collections'));
    }

    public function create(): View
    {
        $collections = Collection::orderBy('sort_order')->get();
        return view('admin.products.create', compact('collections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'                  => 'required|string|max:255',
            'retail_price'           => 'required|numeric|min:0',
            'compare_at_price'       => 'nullable|numeric|min:0',
            'cost_price'             => 'nullable|numeric|min:0',
            'inventory'              => 'required|integer|min:0',
            'low_stock_threshold'    => 'nullable|integer|min:0',
            'status'                 => 'required|in:active,draft,archived',
            'description'            => 'nullable|string',
            'material'               => 'nullable|string|max:255',
            'weight'                 => 'nullable|string|max:100',
            'dimensions'             => 'nullable|string|max:100',
            'seo_title'              => 'nullable|string|max:255',
            'seo_description'        => 'nullable|string',
            'supplier_product_url'   => 'nullable|url|max:2000',
            'cover_image'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'gallery_images.*'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'collection_ids'         => 'nullable|array',
            'collection_ids.*'       => 'exists:collections,id',
            'variants'               => 'nullable|array',
            'variants.*.title'       => 'nullable|string|max:100',
            'variants.*.color_hex'   => 'nullable|string|max:20',
            'variants.*.price_override' => 'nullable|numeric|min:0',
            'variants.*.inventory'   => 'nullable|integer|min:0',
            'variants.*.image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $slug = Str::slug($request->title);
        $base = $slug;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $coverUrl = null;
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $filename = 'cover-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $coverUrl = '/storage/' . $path;

            MediaAsset::create([
                'type'       => 'image',
                'url'        => $coverUrl,
                'filename'   => $filename,
                'mime_type'  => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }

        $collectionIds = $request->input('collection_ids', []);
        $primaryCollection = Collection::query()->whereIn('id', $collectionIds)->orderBy('sort_order')->first();
        $sku = app(ProductSkuService::class)->make($request->title, $primaryCollection);

        $product = Product::create([
            'title'                  => $request->title,
            'slug'                   => $slug,
            'sku'                    => $sku,
            'description'            => $request->description,
            'material'               => $request->material,
            'weight'                 => $request->weight,
            'dimensions'             => $request->dimensions,
            'seo_title'              => $request->seo_title,
            'seo_description'        => $request->seo_description,
            'image_url'              => $coverUrl,
            'retail_price_minor'     => (int) round(((float)$request->retail_price) * 100),
            'compare_at_price_minor' => $request->filled('compare_at_price') ? (int) round(((float)$request->compare_at_price) * 100) : null,
            'cost_price_minor'       => $request->filled('cost_price') ? (int) round(((float)$request->cost_price) * 100) : 0,
            'inventory'              => (int) $request->inventory,
            'low_stock_threshold'    => $request->filled('low_stock_threshold') ? (int) $request->low_stock_threshold : null,
            'status'                 => $request->status,
            'currency'               => 'EGP',
            'attributes_json'        => array_filter([
                'supplier_product_url' => $request->input('supplier_product_url'),
            ]),
        ]);

        // Sync Collections
        if ($request->filled('collection_ids')) {
            $product->collections()->sync($collectionIds);
        }

        // Upload Gallery Images
        if ($request->hasFile('gallery_images')) {
            $position = 1;
            foreach ($request->file('gallery_images') as $galleryFile) {
                if ($galleryFile->isValid()) {
                    $filename = 'gallery-' . time() . '-' . Str::random(8) . '.' . $galleryFile->getClientOriginalExtension();
                    $path = $galleryFile->storeAs('media', $filename, 'public');
                    $url = '/storage/' . $path;

                    $asset = MediaAsset::create([
                        'type'       => 'image',
                        'url'        => $url,
                        'filename'   => $filename,
                        'mime_type'  => $galleryFile->getClientMimeType(),
                        'size_bytes' => $galleryFile->getSize(),
                    ]);

                    $product->mediaAssets()->attach($asset->id, ['group' => 'gallery', 'sort_order' => $position++]);
                }
            }
        }

        // Create Color Variants
        if ($request->filled('has_color_variants') && $request->filled('variants')) {
            foreach ($request->input('variants', []) as $index => $varData) {
                $colorName = trim((string)($varData['title'] ?? ''));
                if (empty($colorName)) {
                    continue;
                }

                $varImgUrl = null;
                if ($request->hasFile("variants.{$index}.image")) {
                    $vFile = $request->file("variants.{$index}.image");
                    $vFilename = 'variant-' . time() . '-' . Str::random(8) . '.' . $vFile->getClientOriginalExtension();
                    $vPath = $vFile->storeAs('media', $vFilename, 'public');
                    $varImgUrl = '/storage/' . $vPath;

                    MediaAsset::create([
                        'type'       => 'image',
                        'url'        => $varImgUrl,
                        'filename'   => $vFilename,
                        'mime_type'  => $vFile->getClientMimeType(),
                        'size_bytes' => $vFile->getSize(),
                    ]);
                }

                $colorHex = $varData['color_hex'] ?? '#000000';
                $priceOverride = !empty($varData['price_override']) ? (int) round(((float)$varData['price_override']) * 100) : null;
                $varStock = isset($varData['inventory']) && $varData['inventory'] !== '' ? (int)$varData['inventory'] : (int)$product->inventory;

                ProductVariant::create([
                    'product_id'           => $product->id,
                    'sku'                  => strtoupper($product->sku . '-' . Str::slug($colorName)),
                    'title'                => $colorName,
                    'attribute_name'       => 'Color',
                    'attribute_value'      => $colorName,
                    'price_override_minor' => $priceOverride,
                    'inventory'            => $varStock,
                    'image_url'            => $varImgUrl,
                    'attributes_json'      => [
                        'color'     => $colorName,
                        'color_hex' => $colorHex,
                        'image_url' => $varImgUrl,
                    ],
                    'status'               => 'active',
                ]);
            }
        }

        AuditLog::log('product.create', 'product', $product->id, "Created product: {$product->title} (SKU: {$product->sku})");

        return redirect()->route('admin.products.edit', $product->id)
            ->with('success', "Product \"{$product->title}\" created successfully with all images, variants and collection assignments!");
    }

    public function edit(int $id): View
    {
        $product = Product::with(['collections', 'mediaAssets', 'variants'])->findOrFail($id);
        $collections = Collection::orderBy('sort_order')->get();
        return view('admin.products.edit', compact('product', 'collections'));
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'title'                  => 'required|string|max:255',
            'retail_price'           => 'required|numeric|min:0',
            'compare_at_price'       => 'nullable|numeric|min:0',
            'cost_price'             => 'nullable|numeric|min:0',
            'inventory'              => 'required|integer|min:0',
            'low_stock_threshold'    => 'nullable|integer|min:0',
            'status'                 => 'required|in:active,draft,archived',
            'description'            => 'nullable|string',
            'material'               => 'nullable|string|max:255',
            'weight'                 => 'nullable|string|max:100',
            'dimensions'             => 'nullable|string|max:100',
            'seo_title'              => 'nullable|string|max:255',
            'seo_description'        => 'nullable|string',
            'supplier_product_url'   => 'nullable|url|max:2000',
            'collection_ids'         => 'nullable|array',
            'collection_ids.*'       => 'exists:collections,id',
        ]);

        $productAttributes = $product->attributes_json ?? [];
        $productAttributes['supplier_product_url'] = $request->input('supplier_product_url');
        if (empty($productAttributes['supplier_product_url'])) {
            unset($productAttributes['supplier_product_url']);
        }

        $collectionIds = $request->input('collection_ids', []);
        $primaryCollection = Collection::query()->whereIn('id', $collectionIds)->orderBy('sort_order')->first()
            ?? $product->collections()->orderBy('sort_order')->first();
        $sku = app(ProductSkuService::class)->make($request->title, $primaryCollection, $product->id);

        $product->update([
            'title'                  => $request->title,
            'sku'                    => $sku,
            'description'            => $request->description,
            'material'               => $request->material,
            'weight'                 => $request->weight,
            'dimensions'             => $request->dimensions,
            'seo_title'              => $request->seo_title,
            'seo_description'        => $request->seo_description,
            'retail_price_minor'     => (int) round(((float)$request->retail_price) * 100),
            'compare_at_price_minor' => $request->filled('compare_at_price') ? (int) round(((float)$request->compare_at_price) * 100) : null,
            'cost_price_minor'       => $request->filled('cost_price') ? (int) round(((float)$request->cost_price) * 100) : 0,
            'inventory'              => (int) $request->inventory,
            'low_stock_threshold'    => $request->filled('low_stock_threshold') ? (int) $request->low_stock_threshold : null,
            'status'                 => $request->status,
            'attributes_json'        => $productAttributes,
        ]);

        if ($request->has('collection_ids')) {
            $product->collections()->sync($collectionIds);
        }
        app(ProductSkuService::class)->syncVariantSkus($product->fresh());

        AuditLog::log('product.update', 'product', $product->id, "Updated product: {$product->title}");

        return back()->with('success', "Product \"{$product->title}\" updated successfully!");
    }

    public function toggleStatus(int $id)
    {
        $product = Product::findOrFail($id);
        $product->status = $product->status === 'active' ? 'draft' : 'active';
        $product->save();

        AuditLog::log('product.toggle_status', 'product', $product->id, "Changed status of {$product->title} to {$product->status}");

        return back()->with('success', "Product is now {$product->status}.");
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $title = $product->title;
        $product->collections()->detach();
        $product->mediaAssets()->detach();
        $product->variants()->delete();
        $product->delete();

        AuditLog::log('product.delete', 'product', $id, "Deleted product: {$title}");

        return redirect()->route('admin.products.index')->with('success', "Product \"{$title}\" permanently deleted.");
    }

    // Cover Image Replacement
    public function replaceImage(Request $request, int $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $product = Product::findOrFail($id);
        $file = $request->file('image');
        $filename = 'cover-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        $asset = MediaAsset::create([
            'type'       => 'image',
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $product->image_url = $url;
        $product->save();

        AuditLog::log('product.image_replace', 'product', $product->id, "Replaced cover image for {$product->title}");

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'image_url' => $url]);
        }

        return back()->with('success', 'Cover image updated successfully!');
    }

    // Gallery Image Upload
    public function uploadGallery(Request $request, int $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $product = Product::findOrFail($id);
        $file = $request->file('image');
        $filename = 'gallery-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');
        $url = '/storage/' . $path;

        $asset = MediaAsset::create([
            'type'       => 'image',
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $nextOrder = (int) $product->mediaAssets()->max('sort_order') + 1;
        $product->mediaAssets()->attach($asset->id, ['group' => 'gallery', 'sort_order' => $nextOrder]);

        AuditLog::log('product.gallery_upload', 'product', $product->id, "Added gallery image for {$product->title}");

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'image_url' => $url, 'asset_id' => $asset->id]);
        }

        return back()->with('success', 'Gallery image added successfully!');
    }

    // Detach / Delete Media from Product
    public function deleteMedia(int $productId, int $assetId)
    {
        $product = Product::findOrFail($productId);
        $product->mediaAssets()->detach($assetId);

        $asset = MediaAsset::find($assetId);
        if ($asset) {
            $usage = \Illuminate\Support\Facades\DB::table('mediables')->where('media_asset_id', $assetId)->count();
            if ($usage === 0) {
                if (Storage::disk('public')->exists('media/' . $asset->filename)) {
                    Storage::disk('public')->delete('media/' . $asset->filename);
                }
                $asset->delete();
            }
        }

        AuditLog::log('product.media_delete', 'product', $productId, "Removed media asset #{$assetId} from {$product->title}");

        return back()->with('success', 'Image removed from product gallery.');
    }

    // Update Variant
    public function updateVariant(Request $request, int $id)
    {
        $variant = ProductVariant::findOrFail($id);
        
        $request->validate([
            'title'          => 'required|string|max:100',
            'attribute_name' => 'nullable|string|max:100',
            'price_override' => 'nullable|numeric|min:0',
            'inventory'      => 'required|integer|min:0',
            'color_hex'      => 'nullable|string|max:20',
            'image_url'      => 'nullable|string|max:500',
            'image_file'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imageUrl = $request->input('image_url', $variant->image_url);
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = 'variant-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
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

        $priceOverrideMinor = $request->filled('price_override') 
            ? (int) round(((float)$request->input('price_override')) * 100) 
            : null;

        $colorHex = $request->input('color_hex', $variant->attributes_json['color_hex'] ?? '#000000');

        $variant->update([
            'title'                => $request->input('title'),
            'attribute_name'       => $request->input('attribute_name', $variant->attribute_name ?? 'Color'),
            'attribute_value'      => $request->input('title'),
            'price_override_minor' => $priceOverrideMinor,
            'inventory'            => (int) $request->input('inventory'),
            'image_url'            => $imageUrl,
            'attributes_json'      => [
                'color'     => $request->input('title'),
                'color_hex' => $colorHex,
                'image_url' => $imageUrl,
            ],
        ]);

        return back()->with('success', "Updated variant: {$variant->title}");
    }

    // Add new Variant
    public function addVariant(Request $request, int $productId)
    {
        $product = Product::findOrFail($productId);
        $request->validate([
            'title'          => 'required|string|max:100',
            'attribute_name' => 'nullable|string|max:100',
            'price_override' => 'nullable|numeric|min:0',
            'inventory'      => 'required|integer|min:0',
            'color_hex'      => 'nullable|string|max:20',
            'image_url'      => 'nullable|string|max:500',
            'image_file'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imageUrl = $request->input('image_url');
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = 'variant-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
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

        $priceOverrideMinor = $request->filled('price_override') 
            ? (int) round(((float)$request->input('price_override')) * 100) 
            : null;

        $colorHex = $request->input('color_hex', '#000000');

        $variant = $product->variants()->create([
            'sku'                  => $product->sku . '-' . strtoupper(Str::slug($request->title)),
            'title'                => $request->title,
            'attribute_name'       => $request->input('attribute_name', 'Color'),
            'attribute_value'      => $request->title,
            'price_override_minor' => $priceOverrideMinor,
            'retail_price_minor'   => $product->retail_price_minor,
            'inventory'            => (int) $request->inventory,
            'image_url'            => $imageUrl,
            'attributes_json'      => [
                'color'     => $request->title,
                'color_hex' => $colorHex,
                'image_url' => $imageUrl,
            ],
        ]);

        return back()->with('success', "Added variant \"{$variant->title}\".");
    }

    // Delete Variant
    public function deleteVariant(int $id)
    {
        $variant = ProductVariant::findOrFail($id);
        $variant->delete();
        return back()->with('success', 'Variant removed.');
    }
}
