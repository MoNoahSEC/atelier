@extends('layouts.admin')

@section('title', 'Edit ' . $product->title)

@section('content')
<div class="max-w-5xl space-y-8" x-data="productEditor()">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-black mb-1 block">
                ← Back to Products List
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black uppercase tracking-tight text-black">{{ $product->title }}</h1>
                <span class="text-[10px] font-mono px-2 py-0.5 border font-bold uppercase {{ $product->status === 'active' ? 'bg-green-100 text-green-800 border-green-600' : 'bg-gray-100 text-gray-700 border-gray-400' }}">
                    {{ $product->status }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="border border-black bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-gray-100 transition-colors">
                ↗ View Live Page
            </a>
            <form action="{{ route('admin.products.delete', $product->id) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete {{ addslashes($product->title) }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="border border-red-600 text-red-600 px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-red-50">
                    🗑 Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Details Form -->
    <form id="product-main-form" action="{{ route('admin.products.update', $product->id) }}" method="POST" class="space-y-6" onsubmit="syncQuillEditor()">
        @csrf

        <!-- 1. Core Details -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">1. Essential Specifications</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Product Title *</label>
                    <input type="text" name="title" required value="{{ old('title', $product->title) }}" class="w-full border-2 border-black p-3 text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Automatic product code</label>
                    <input type="text" value="{{ $product->sku }}" readonly class="w-full border-2 border-black/15 bg-gray-50 p-3 text-sm font-mono text-gray-700 cursor-not-allowed">
                    <p class="mt-1 text-[10px] text-gray-500">Updates automatically from the product title and first selected collection when you save.</p>
                </div>
            </div>

            <div class="rounded-xl border border-amber-400/60 bg-amber-50 p-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Private supplier product link</label>
                <input type="url" name="supplier_product_url" value="{{ old('supplier_product_url', $product->attributes_json['supplier_product_url'] ?? '') }}" placeholder="https://www.amazon.eg/... or your supplier link" class="w-full border border-black/30 bg-white p-3 text-xs font-mono focus:outline-none focus:ring-4 focus:ring-amber-200">
                <p class="mt-2 text-[10px] text-gray-600">Hidden from customers. Sent in the Telegram order notification for fast purchasing from your supplier.</p>
            </div>

            <!-- Rich Text Description (Quill.js) -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Description & Craftsmanship Story (Rich Text)</label>
                <div id="quill-editor" class="bg-white">{!! $product->description !!}</div>
                <input type="hidden" name="description" id="hidden-description" value="{{ old('description', $product->description) }}">
            </div>
        </div>

        <!-- 2. Dimensions & Materials Specifications -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">2. Materials & Technical Specifications</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Material Composition</label>
                    <input type="text" name="material" value="{{ old('material', $product->material) }}" placeholder="e.g. Tuscan Full-Grain Leather & Titanium" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Dimensions (L × W × H)</label>
                    <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" placeholder="e.g. 10.4 × 6.8 × 1.1 cm" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Weight</label>
                    <input type="text" name="weight" value="{{ old('weight', $product->weight) }}" placeholder="e.g. 74 grams" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>
        </div>

        <!-- 3. Pricing & Inventory -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">3. Pricing & Stock Control</h2>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Selling Price (EGP) *</label>
                    <input type="number" step="0.01" name="retail_price" required value="{{ old('retail_price', $product->retail_price_minor / 100) }}" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Sale / Compare Price (EGP)</label>
                    <input type="number" step="0.01" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price_minor ? $product->compare_at_price_minor / 100 : '') }}" placeholder="e.g. 1800 (strikethrough)" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none">
                    <span class="text-[10px] text-gray-400">Shows discount on storefront</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Cost Price (EGP)</label>
                    <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price_minor / 100) }}" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Available Stock *</label>
                    <input type="number" name="inventory" required value="{{ old('inventory', $product->inventory) }}" min="0" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none font-bold">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Low Stock Warning Threshold</label>
                    <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" placeholder="Leave blank to use global default" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                    <span class="text-[9px] text-gray-400">Triggers alert when stock $\le$ this number</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Catalog Status *</label>
                    <select name="status" class="w-full border-2 border-black p-2.5 text-xs bg-white font-bold focus:outline-none">
                        <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>✅ Active (Visible in Store)</option>
                        <option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>📝 Draft (Hidden)</option>
                        <option value="archived" {{ old('status', $product->status) === 'archived' ? 'selected' : '' }}>📦 Archived</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 4. Collections -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b pb-2 flex items-center justify-between">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 flex items-center gap-2">
                    <span>📁</span>
                    <span>4. Assigned Collections</span>
                </h2>
                <a href="{{ route('admin.collections.index') }}" target="_blank" class="text-[10px] font-bold uppercase tracking-wider text-gray-500 hover:text-black underline">
                    Manage Collections ↗
                </a>
            </div>

            @if($collections && $collections->count() > 0)
                <p class="text-xs text-gray-600">Select which categories / collections this product appears in:</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    @php $assignedIds = $product->collections->pluck('id')->toArray(); @endphp
                    @foreach($collections as $col)
                        <label class="flex items-center gap-2.5 border-2 border-black p-3 bg-gray-50 hover:bg-white cursor-pointer transition-colors text-xs font-bold shadow-sm">
                            <input 
                                type="checkbox" 
                                name="collection_ids[]" 
                                value="{{ $col->id }}" 
                                {{ in_array($col->id, $assignedIds) ? 'checked' : '' }} 
                                class="w-4 h-4 accent-black cursor-pointer"
                            >
                            <span>{{ $col->title }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="bg-amber-50 border-2 border-amber-400 p-4 text-xs text-amber-900 space-y-2">
                    <p class="font-bold uppercase">No collections created yet!</p>
                    <p>Create your first collection/category so you can organize your store products.</p>
                    <a href="{{ route('admin.collections.index') }}" class="inline-block bg-black text-white px-3 py-1.5 text-[10px] font-bold uppercase">
                        + Create Collections Now →
                    </a>
                </div>
            @endif
        </div>

        <!-- 5. Search Engine Optimization (SEO) -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">5. Search Engine Optimization (SEO)</h2>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Custom Meta Title</label>
                <input type="text" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}" placeholder="Leave blank to use product title" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Custom Meta Description</label>
                <textarea name="seo_description" rows="2" placeholder="Leave blank to generate automatically from product description" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">{{ old('seo_description', $product->seo_description) }}</textarea>
            </div>
        </div>

        <!-- Save Button -->
        <div>
            <button type="submit" class="bg-black text-white px-8 py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-transform active:translate-y-0.5">
                Save Product Changes →
            </button>
        </div>
    </form>

    <!-- 6. Smart Image Management & Bulk Multi-Upload -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-6">
        <div class="border-b-2 border-black pb-3 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div>
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">MEDIA STUDIO</span>
                <h2 class="text-xl font-black uppercase tracking-tight text-black">Product Images & Multi-Upload</h2>
            </div>
            <button type="button" onclick="document.getElementById('bulk-gallery-input').click()" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                📸 Bulk Upload Photos
            </button>
        </div>

        <!-- Hidden Bulk Form -->
        <form id="bulk-gallery-form" action="{{ route('admin.products.bulk-gallery', $product->id) }}" method="POST" enctype="multipart/form-data" class="hidden">
            @csrf
            <input type="file" id="bulk-gallery-input" name="images[]" multiple accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('bulk-gallery-form').submit()">
        </form>

        <!-- Grid of Images -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            
            <!-- Primary Cover Image Card -->
            <div class="border-2 border-black p-3 bg-gray-50 flex flex-col justify-between">
                <div>
                    <span class="inline-block bg-black text-white px-1.5 py-0.5 text-[9px] font-bold uppercase mb-2">PRIMARY COVER</span>
                    <div class="text-xs font-bold text-gray-900 mb-2 truncate">Cover Image — {{ $product->title }}</div>
                </div>

                @php
                    $coverSrc = $product->image_url ?: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=500';
                    $coverUrl = str_starts_with($coverSrc, 'http') ? $coverSrc : url($coverSrc);
                @endphp

                <div 
                    class="relative aspect-square border border-black bg-white overflow-hidden cursor-pointer group hover:opacity-90 transition-all"
                    onclick="document.getElementById('cover-file-input').click()"
                    title="Click to replace Cover Image for {{ $product->title }}"
                >
                    <img id="cover-preview" src="{{ $coverUrl }}" alt="Cover Image — {{ $product->title }}" class="w-full h-full object-cover">
                    
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white p-2 text-center">
                        <span class="text-base mb-1">📷</span>
                        <span class="text-[10px] font-bold uppercase">Click to Replace</span>
                    </div>

                    <div id="cover-loader" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center text-white" style="display: none;">
                        <div class="spinner mb-1"></div>
                        <span class="text-[9px] font-bold uppercase">Uploading...</span>
                    </div>
                </div>

                <form id="cover-form" action="{{ route('admin.products.replace-image', $product->id) }}" method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="file" id="cover-file-input" name="image" accept="image/jpeg,image/png,image/webp" onchange="uploadCoverImage(this)">
                </form>

                <div class="mt-2 text-[10px] text-gray-600 text-center font-bold uppercase">
                    Image for: {{ $product->title }}
                </div>
            </div>

            <!-- Gallery Images -->
            @foreach($product->mediaAssets as $index => $asset)
                @php
                    $assetSrc = $asset->url ?: ('/storage/media/' . $asset->filename);
                    $assetUrl = str_starts_with($assetSrc, 'http') ? $assetSrc : url($assetSrc);
                    $slotNum = $index + 1;
                @endphp
                <div class="border border-black p-3 bg-white flex flex-col justify-between group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[9px] font-bold uppercase bg-gray-100 px-1 py-0.5">GALLERY #{{ $slotNum }}</span>
                        <form action="{{ route('admin.products.delete-media', ['productId' => $product->id, 'assetId' => $asset->id]) }}" method="POST" class="inline" onsubmit="return confirm('Remove gallery image #{{ $slotNum }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-[10px] font-bold" title="Delete image">✕</button>
                        </form>
                    </div>

                    <div class="text-xs font-bold text-gray-900 mb-2 truncate">Gallery Image {{ $slotNum }} — {{ $product->title }}</div>

                    <div 
                        class="relative aspect-square border border-black bg-gray-50 overflow-hidden cursor-pointer hover:opacity-90 transition-all"
                        onclick="document.getElementById('gallery-file-{{ $asset->id }}').click()"
                        title="Click to replace Gallery Image {{ $slotNum }}"
                    >
                        <img id="gallery-preview-{{ $asset->id }}" src="{{ $assetUrl }}" alt="Gallery Image {{ $slotNum }} — {{ $product->title }}" class="w-full h-full object-cover">

                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white p-2 text-center">
                            <span class="text-base mb-1">📷</span>
                            <span class="text-[10px] font-bold uppercase">Click to Replace</span>
                        </div>
                    </div>

                    <form id="gallery-form-{{ $asset->id }}" action="{{ route('admin.media.replace', $asset->id) }}" method="POST" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" id="gallery-file-{{ $asset->id }}" name="image" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
                    </form>

                    <div class="mt-2 text-[10px] text-gray-500 text-center font-mono truncate">
                        {{ $asset->filename }}
                    </div>
                </div>
            @endforeach

            <!-- Drag & Drop Multi-Upload Box -->
            <div 
                class="border-2 border-dashed border-black p-4 flex flex-col items-center justify-center text-center bg-gray-50 hover:bg-gray-100 cursor-pointer min-h-[220px]" 
                onclick="document.getElementById('bulk-gallery-input').click()"
                ondragover="event.preventDefault(); this.classList.add('border-amber-500','bg-amber-50')"
                ondragleave="this.classList.remove('border-amber-500','bg-amber-50')"
                ondrop="event.preventDefault(); this.classList.remove('border-amber-500','bg-amber-50'); const d=new DataTransfer(); [...event.dataTransfer.files].filter(f=>f.type.startsWith('image/')).forEach(f=>d.items.add(f)); const i=document.getElementById('bulk-gallery-input'); i.files=d.files; if(i.files.length)i.form.submit();"
            >
                <span class="text-3xl block mb-2">📥</span>
                <span class="text-xs font-black uppercase tracking-wider block">+ Drag & Drop Photos</span>
                <span class="text-[10px] text-gray-500 mt-1 block">Select multiple JPG, PNG, WebP files</span>
            </div>

        </div>
    </div>

    <!-- 7. Advanced Product Variants Architecture -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-6">
        <div class="border-b-2 border-black pb-3">
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">MULTI-ATTRIBUTE ARCHITECTURE</span>
            <h2 class="text-xl font-black uppercase tracking-tight text-black">Product Variants & Overrides</h2>
            <p class="text-xs text-gray-500 mt-0.5">Define variant options (e.g. Size, Color, Edition) with per-variant stock, price overrides, and photo binding.</p>
        </div>

        @if($product->variants->count() > 0)
            <div class="space-y-3">
                @foreach($product->variants as $variant)
                    <form action="{{ route('admin.products.update-variant', $variant->id) }}" method="POST" class="p-4 border border-black bg-gray-50 grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                        @csrf
                        
                        <!-- Attribute Name & Value -->
                        <div class="sm:col-span-2">
                            <label class="block text-[9px] font-bold uppercase text-gray-500 mb-0.5">Attribute (e.g. Color: Carbon Black)</label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" name="attribute_name" value="{{ $variant->attribute_name ?? 'Color' }}" placeholder="Attr (e.g. Color)" class="w-24 border border-black p-1.5 text-xs bg-white font-bold">
                                <span class="font-bold">:</span>
                                <input type="text" name="title" value="{{ $variant->title }}" placeholder="Value (e.g. Matte Black)" required class="flex-1 border border-black p-1.5 text-xs bg-white font-bold">
                            </div>
                        </div>

                        <!-- Price Override -->
                        <div>
                            <label class="block text-[9px] font-bold uppercase text-gray-500 mb-0.5">Price Override (EGP)</label>
                            <input type="number" step="0.01" name="price_override" value="{{ $variant->price_override_minor ? $variant->price_override_minor / 100 : '' }}" placeholder="Base ({{ number_format($product->retail_price_minor / 100, 0) }})" class="w-full border border-black p-1.5 text-xs bg-white font-mono font-bold">
                        </div>

                        <!-- Stock -->
                        <div>
                            <label class="block text-[9px] font-bold uppercase text-gray-500 mb-0.5">Stock</label>
                            <input type="number" name="inventory" value="{{ $variant->inventory }}" min="0" required class="w-full border border-black p-1.5 text-xs bg-white font-mono font-bold">
                        </div>

                        <div>
                            <label class="block text-[9px] font-bold uppercase text-gray-500 mb-0.5">Color</label>
                            @php $variantColor = $variant->attributes_json['color_hex'] ?? '#1A1A1A'; @endphp
                            <div class="flex gap-1">
                                <input type="color" value="{{ $variantColor }}" oninput="this.nextElementSibling.value=this.value" class="w-9 h-8 border border-black p-0.5 bg-white">
                                <input type="text" name="color_hex" value="{{ $variantColor }}" class="min-w-0 flex-1 border border-black p-1.5 text-[10px] font-mono uppercase bg-white">
                            </div>
                        </div>

                        <!-- Image Selector & Actions -->
                        <div class="flex items-center gap-2">
                            <select name="image_url" class="border border-black p-1.5 text-xs bg-white flex-1">
                                <option value="">Default Image</option>
                                @if($product->image_url)
                                    <option value="{{ $product->image_url }}" {{ $variant->image_url === $product->image_url ? 'selected' : '' }}>Cover Photo</option>
                                @endif
                                @foreach($product->mediaAssets as $gIdx => $mAsset)
                                    <option value="{{ $mAsset->url }}" {{ $variant->image_url === $mAsset->url ? 'selected' : '' }}>Gallery #{{ $gIdx + 1 }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-black text-white px-3 py-1.5 text-xs font-bold uppercase hover:bg-gray-800">
                                Save
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        @endif

        <!-- Add Variant Form -->
        <div class="pt-4 border-t border-gray-200">
            <h3 class="text-xs font-bold uppercase tracking-wider text-black mb-3">+ Add New Variant</h3>
            <form action="{{ route('admin.products.add-variant', $product->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Attribute Name *</label>
                    <input type="text" name="attribute_name" value="Color" required placeholder="e.g. Color, Size" class="w-full border-2 border-black p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Option Value *</label>
                    <input type="text" name="title" required placeholder="e.g. Navy Blue, 42mm" class="w-full border-2 border-black p-2 text-xs font-bold">
                </div>
                <div>
                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Price Override (EGP)</label>
                    <input type="number" step="0.01" name="price_override" placeholder="Leave blank for base" class="w-full border-2 border-black p-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Initial Stock *</label>
                    <input type="number" name="inventory" value="10" min="0" required class="w-full border-2 border-black p-2 text-xs font-mono font-bold">
                </div>
                <div>
                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Color swatch</label>
                    <div class="flex gap-1"><input type="color" value="#1A1A1A" oninput="this.nextElementSibling.value=this.value" class="w-9 h-9 border border-black p-0.5"><input type="text" name="color_hex" value="#1A1A1A" class="min-w-0 flex-1 border-2 border-black p-2 text-[10px] font-mono"></div>
                </div>
                <div>
                    <button type="submit" class="w-full bg-black text-white py-2 px-3 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        Add Variant →
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
let quill;
document.addEventListener('DOMContentLoaded', function () {
    const editorContainer = document.getElementById('quill-editor');
    if (editorContainer) {
        quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });
    }
});

function syncQuillEditor() {
    if (quill) {
        document.getElementById('hidden-description').value = quill.root.innerHTML;
    }
}

function productEditor() {
    return {};
}

function uploadCoverImage(input) {
    if (!input.files || !input.files[0]) return;
    document.getElementById('cover-loader').style.display = 'flex';
    document.getElementById('cover-form').submit();
}
</script>
@endpush
@endsection
