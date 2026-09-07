@extends('layouts.admin')

@section('title', 'Add New Product')

@section('content')
<div class="max-w-5xl space-y-6" x-data="createProductForm()">

    <!-- Header -->
    <div class="border-b-2 border-black pb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-black mb-1 block">
                ← Back to Products List
            </a>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">+ Add New Product</h1>
            <p class="text-xs text-gray-500 mt-0.5">Upload photos, configure color variants, specifications and assign categories in one place</p>
        </div>
    </div>

    <!-- Product Create Form -->
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" onsubmit="syncQuillEditor()">
        @csrf

        <!-- 1. Essential Details -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">1. Essential Details</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Product Title *</label>
                    <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. The Titanium Minimalist Bifold" class="w-full border-2 border-black p-3 text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Product code</label>
                    <input type="text" value="Generated automatically after choosing the title and collection" readonly class="w-full border-2 border-black/15 bg-gray-50 p-3 text-xs font-mono text-gray-500 cursor-not-allowed">
                    <p class="mt-1 text-[10px] text-gray-500">Based on the collection and product name. No manual code is needed.</p>
                </div>
            </div>

            <div class="rounded-xl border border-amber-400/60 bg-amber-50 p-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Private supplier product link</label>
                <input type="url" name="supplier_product_url" value="{{ old('supplier_product_url') }}" placeholder="https://www.amazon.eg/... or your supplier link" class="w-full border border-black/30 bg-white p-3 text-xs font-mono focus:outline-none focus:ring-4 focus:ring-amber-200">
                <p class="mt-2 text-[10px] text-gray-600">Hidden from customers. The exact link is included in the Telegram alert when this item is ordered.</p>
            </div>

            <!-- Rich Text Description (Quill.js) -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Description & Craftsmanship Story (Rich Text)</label>
                <div id="quill-editor" class="bg-white">{!! old('description') !!}</div>
                <input type="hidden" name="description" id="hidden-description" value="{{ old('description') }}">
            </div>
        </div>

        <!-- 2. Product Images (Cover & Gallery) -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-6">
            <div class="border-b pb-2 flex items-center justify-between">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 flex items-center gap-2">
                    <span>📸</span>
                    <span>2. Product Images</span>
                </h2>
                <span class="text-[10px] font-mono text-gray-400">JPG, PNG, WebP · Max 5MB each</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Cover Image -->
                <div class="lg:col-span-5 space-y-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700">
                        Primary Cover Image * (Main Catalog Thumbnail)
                    </label>
                    
                    <div 
                        @click="$refs.coverInput.click()" @dragover.prevent="draggingCover = true" @dragleave.prevent="draggingCover = false" @drop.prevent="setCoverFromDrop($event)"
                        :class="draggingCover ? 'border-amber-500 bg-amber-50' : 'border-black bg-gray-50'" class="border-2 border-dashed p-4 hover:bg-gray-100 transition-colors cursor-pointer text-center relative aspect-square flex flex-col items-center justify-center group overflow-hidden shadow-sm"
                    >
                        <template x-if="coverPreview">
                            <img :src="coverPreview" alt="Cover Preview" class="w-full h-full object-cover absolute inset-0">
                        </template>

                        <div x-show="!coverPreview" class="space-y-2">
                            <span class="text-3xl block group-hover:scale-110 transition-transform">🖼️</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-black block">Click to Upload Cover</span>
                            <span class="text-[10px] text-gray-500 block">Required for catalog display</span>
                        </div>

                        <div x-show="coverPreview" class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold uppercase tracking-wider">
                            Change Cover Image ✎
                        </div>
                    </div>

                    <input 
                        type="file" 
                        name="cover_image" 
                        x-ref="coverInput" 
                        @change="previewCoverImage($event)" 
                        accept="image/jpeg,image/png,image/webp,image/jpg" 
                        class="hidden"
                    >
                </div>

                <!-- Gallery Images (Multiple) -->
                <div class="lg:col-span-7 space-y-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700">
                        Additional Gallery Photos (Multi-Upload)
                    </label>

                    <div 
                        @click="$refs.galleryInput.click()" @dragover.prevent="draggingGallery = true" @dragleave.prevent="draggingGallery = false" @drop.prevent="setGalleryFromDrop($event)"
                        :class="draggingGallery ? 'border-amber-500 bg-amber-50' : 'border-gray-400 bg-gray-50'" class="border-2 border-dashed hover:border-black p-4 hover:bg-gray-100 transition-colors cursor-pointer text-center flex flex-col items-center justify-center min-h-[120px]"
                    >
                        <span class="text-2xl mb-1">📂</span>
                        <span class="text-xs font-bold uppercase tracking-wider text-black">Click to Select Multiple Gallery Photos</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Select several photos at once from your computer</span>
                    </div>

                    <input 
                        type="file" 
                        name="gallery_images[]" 
                        x-ref="galleryInput" 
                        @change="setGalleryFiles($event.target.files)" 
                        multiple 
                        accept="image/jpeg,image/png,image/webp,image/jpg" 
                        class="hidden"
                    >

                    <!-- Gallery Thumbnails Preview Grid -->
                    <template x-if="galleryPreviews.length > 0">
                        <div class="mt-3 space-y-2">
                            <div class="flex items-center justify-between text-[10px] font-mono text-gray-500">
                                <span>Selected Gallery Photos (<span x-text="galleryPreviews.length"></span>)</span>
                                <button type="button" @click="galleryPreviews = []; galleryFiles = []; $refs.galleryInput.value = ''" class="text-red-600 underline">Clear All</button>
                            </div>
                            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                                <template x-for="(thumb, idx) in galleryPreviews" :key="idx">
                                    <div class="relative aspect-square border border-black overflow-hidden bg-white group">
                                        <img :src="thumb" class="w-full h-full object-cover">
                                        <button 
                                            type="button" 
                                            @click="removeGalleryPreview(idx)" 
                                            class="absolute top-1 right-1 bg-black text-white w-4 h-4 text-[10px] flex items-center justify-center opacity-80 hover:opacity-100"
                                            title="Remove"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- 3. Color Variants with Per-Color Images -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-3">
                    <input 
                        type="checkbox" 
                        name="has_color_variants" 
                        id="has_color_variants" 
                        value="1" 
                        x-model="hasVariants"
                        class="w-5 h-5 accent-black cursor-pointer"
                    >
                    <label for="has_color_variants" class="text-sm font-black uppercase tracking-tight text-black cursor-pointer">
                        🎨 This product comes in multiple colors / finishes
                    </label>
                </div>
                <span class="text-[10px] font-mono text-gray-400">Optional</span>
            </div>

            <!-- Dynamic Color Variants Manager -->
            <div x-show="hasVariants" x-cloak class="space-y-4 pt-2">
                <p class="text-xs text-gray-600 leading-relaxed">
                    Add individual color variants. Each color can have its own dedicated photo, stock availability, and optional custom price override.
                </p>

                <div class="flex gap-2 rounded-xl bg-amber-50 border border-amber-300 p-3">
                    <input type="text" x-model="quickColors" @keydown.enter.prevent="addColorsFromText()" placeholder="Type colors: Black, Cognac, Navy — press Enter" class="min-w-0 flex-1 border border-black/30 bg-white px-3 py-2 text-xs">
                    <button type="button" @click="addColorsFromText()" class="bg-black text-white px-3 text-[10px] font-bold uppercase">Add</button>
                </div>

                <div class="space-y-3">
                    <template x-for="(variant, index) in variants" :key="index">
                        <div class="border-2 border-black p-4 bg-gray-50 relative space-y-3 shadow-sm">
                            <div class="flex items-center justify-between border-b border-gray-300 pb-2">
                                <span class="text-xs font-mono font-bold uppercase text-black" x-text="'Finish #' + (index + 1)"></span>
                                <button 
                                    type="button" 
                                    @click="removeVariant(index)" 
                                    class="text-red-600 hover:text-red-800 text-xs font-bold uppercase underline"
                                >
                                    Remove Finish ×
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <!-- Color Name -->
                                <div class="sm:col-span-4">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Color Name *</label>
                                    <input 
                                        type="text" 
                                        :name="'variants[' + index + '][title]'" 
                                        x-model="variant.title" 
                                        @input="suggestColorHex(variant)"
                                        placeholder="e.g. Midnight Black / Tuscan Tan" 
                                        required 
                                        class="w-full border-2 border-black p-2 text-xs focus:outline-none bg-white font-medium"
                                    >
                                </div>

                                <!-- Color Hex Swatch -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Color Swatch</label>
                                    <div class="flex items-center gap-1.5">
                                        <input 
                                            type="color" 
                                            x-model="variant.color_hex" 
                                            class="w-8 h-8 border border-black cursor-pointer bg-white p-0.5"
                                        >
                                        <input 
                                            type="text" 
                                            x-model="variant.color_hex" 
                                            :name="'variants[' + index + '][color_hex]'" 
                                            class="w-full border border-black p-1 text-[11px] font-mono uppercase bg-white text-center"
                                        >
                                    </div>
                                </div>

                                <!-- Price Override -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Price Override (EGP)</label>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        :name="'variants[' + index + '][price_override]'" 
                                        x-model="variant.price_override" 
                                        placeholder="Base price" 
                                        class="w-full border-2 border-black p-2 text-xs font-mono focus:outline-none bg-white"
                                    >
                                </div>

                                <!-- Stock -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Variant Stock *</label>
                                    <input 
                                        type="number" 
                                        :name="'variants[' + index + '][inventory]'" 
                                        x-model="variant.inventory" 
                                        min="0" 
                                        required 
                                        class="w-full border-2 border-black p-2 text-xs font-mono font-bold focus:outline-none bg-white"
                                    >
                                </div>

                                <!-- Variant Photo Upload -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Color Photo</label>
                                    <div class="flex items-center gap-2">
                                        <label class="border-2 border-black px-2 py-1.5 text-[10px] font-bold uppercase bg-white hover:bg-gray-100 cursor-pointer text-center block w-full truncate">
                                            <span x-text="variant.imageName || 'Upload Photo'"></span>
                                            <input 
                                                type="file" 
                                                :name="'variants[' + index + '][image]'" 
                                                @change="onVariantImageSelected($event, index)" 
                                                accept="image/jpeg,image/png,image/webp,image/jpg" 
                                                class="hidden"
                                            >
                                        </label>
                                        <template x-if="variant.preview"><img :src="variant.preview" @click="pickColorFromImage($event, index)" title="Click a pixel to choose its color" class="mt-2 w-full aspect-square object-cover border border-black cursor-crosshair"></template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <button 
                    type="button" 
                    @click="addVariant()" 
                    class="border-2 border-black px-4 py-2 text-xs font-bold uppercase tracking-wider bg-white hover:bg-black hover:text-white transition-colors"
                >
                    + Add Another Color
                </button>
            </div>
        </div>

        <!-- 4. Specifications (Materials & Dimensions) -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">4. Materials & Technical Specifications</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Material Composition</label>
                    <input type="text" name="material" value="{{ old('material') }}" placeholder="e.g. Full-Grain Italian Leather & Titanium" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Dimensions (L × W × H)</label>
                    <input type="text" name="dimensions" value="{{ old('dimensions') }}" placeholder="e.g. 10.4 × 6.8 × 1.1 cm" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Weight</label>
                    <input type="text" name="weight" value="{{ old('weight') }}" placeholder="e.g. 74 grams" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>
        </div>

        <!-- 5. Base Pricing & Inventory -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">5. Base Pricing & Inventory</h2>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Base Selling Price (EGP) *</label>
                    <input type="number" step="0.01" name="retail_price" required value="{{ old('retail_price') }}" placeholder="e.g. 850" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none font-bold">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Sale / Compare Price (EGP)</label>
                    <input type="number" step="0.01" name="compare_at_price" value="{{ old('compare_at_price') }}" placeholder="e.g. 1200" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none">
                    <span class="text-[9px] text-gray-400">Strikethrough original price</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Cost Price (EGP)</label>
                    <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price') }}" placeholder="e.g. 350" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Base Inventory Stock *</label>
                    <input type="number" name="inventory" required value="{{ old('inventory', 20) }}" min="0" class="w-full border-2 border-black p-3 text-sm font-mono focus:outline-none font-bold">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Low Stock Alert Threshold</label>
                    <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold') }}" placeholder="Leave blank to use global default (5)" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Catalog Status *</label>
                    <select name="status" class="w-full border-2 border-black p-2.5 text-xs bg-white font-bold focus:outline-none">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>✅ Active (Visible in Store Immediately)</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>📝 Draft (Hidden)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 6. Assign to Collections -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b pb-2 flex items-center justify-between">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 flex items-center gap-2">
                    <span>📁</span>
                    <span>6. Assign to Collections</span>
                </h2>
                <a href="{{ route('admin.collections.index') }}" target="_blank" class="text-[10px] font-bold uppercase tracking-wider text-gray-500 hover:text-black underline">
                    Manage Collections ↗
                </a>
            </div>

            @if($collections && $collections->count() > 0)
                <p class="text-xs text-gray-600">Select which categories / collections this product will appear in:</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    @php $oldCollections = old('collection_ids', []); @endphp
                    @foreach($collections as $col)
                        <label class="flex items-center gap-2.5 border-2 border-black p-3 bg-gray-50 hover:bg-white cursor-pointer transition-colors text-xs font-bold shadow-sm">
                            <input 
                                type="checkbox" 
                                name="collection_ids[]" 
                                value="{{ $col->id }}" 
                                {{ in_array($col->id, (array)$oldCollections) ? 'checked' : '' }} 
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

        <!-- 7. Search Engine Optimization (SEO) -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">7. Search Engine Optimization (SEO)</h2>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Custom Meta Title</label>
                <input type="text" name="seo_title" value="{{ old('seo_title') }}" placeholder="Leave blank to use product title" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Custom Meta Description</label>
                <textarea name="seo_description" rows="2" placeholder="Leave blank to generate automatically" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">{{ old('seo_description') }}</textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('admin.products.index') }}" class="border border-black bg-white px-6 py-3.5 text-xs font-bold uppercase hover:bg-gray-100">
                Cancel
            </a>
            <button type="submit" class="bg-black text-white px-8 py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-transform active:translate-y-0.5">
                Save & Publish Product →
            </button>
        </div>

    </form>
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

function createProductForm() {
    return {
        coverPreview: null,
        galleryPreviews: [],
        galleryFiles: [],
        draggingCover: false,
        draggingGallery: false,
        quickColors: '',
        hasVariants: false,
        variants: [
            { title: 'Classic Black', color_hex: '#000000', price_override: '', inventory: 15, imageName: '', preview: '' },
            { title: 'Cognac Brown', color_hex: '#8B4513', price_override: '', inventory: 15, imageName: '', preview: '' }
        ],
        previewCoverImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.coverPreview = URL.createObjectURL(file);
            }
        },
        setCoverFromDrop(event) {
            this.draggingCover = false;
            const file = [...event.dataTransfer.files].find(file => file.type.startsWith('image/'));
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.coverInput.files = transfer.files;
            this.previewCoverImage({ target: this.$refs.coverInput });
        },
        setGalleryFromDrop(event) {
            this.draggingGallery = false;
            this.setGalleryFiles(event.dataTransfer.files);
        },
        setGalleryFiles(files) {
            this.galleryFiles = [...this.galleryFiles, ...Array.from(files).filter(file => file.type.startsWith('image/'))];
            const transfer = new DataTransfer();
            this.galleryFiles.forEach(file => transfer.items.add(file));
            this.$refs.galleryInput.files = transfer.files;
            this.galleryPreviews = this.galleryFiles.map(file => URL.createObjectURL(file));
        },
        removeGalleryPreview(index) {
            this.galleryFiles.splice(index, 1);
            this.galleryPreviews = this.galleryFiles.map(file => URL.createObjectURL(file));
            const transfer = new DataTransfer();
            this.galleryFiles.forEach(file => transfer.items.add(file));
            this.$refs.galleryInput.files = transfer.files;
        },
        addVariant() {
            this.variants.push({
                title: '',
                color_hex: '#1A1A1A',
                price_override: '',
                inventory: 10,
                imageName: '', preview: ''
            });
        },
        addColorsFromText() {
            this.quickColors.split(',').map(color => color.trim()).filter(Boolean).forEach(title => {
                if (!this.variants.some(variant => variant.title.toLowerCase() === title.toLowerCase())) {
                    const variant = { title, color_hex: '#1A1A1A', price_override: '', inventory: 10, imageName: '', preview: '' };
                    this.suggestColorHex(variant);
                    this.variants.push(variant);
                }
            });
            this.quickColors = '';
        },
        suggestColorHex(variant) {
            const colors = { black:'#000000', white:'#FFFFFF', brown:'#8B4513', cognac:'#9A4F20', tan:'#C19A6B', navy:'#14213D', blue:'#2563EB', green:'#2F5D50', red:'#B91C1C', grey:'#6B7280', gray:'#6B7280', silver:'#B8BDC5', gold:'#C9A227', 'اسود':'#000000', 'ابيض':'#FFFFFF', 'بني':'#8B4513', 'كحلي':'#14213D', 'ازرق':'#2563EB', 'اخضر':'#2F5D50', 'احمر':'#B91C1C', 'ذهبي':'#C9A227' };
            const name = variant.title.toLowerCase();
            const match = Object.keys(colors).find(key => name.includes(key));
            if (match) variant.color_hex = colors[match];
        },
        removeVariant(index) {
            this.variants.splice(index, 1);
        },
        onVariantImageSelected(event, index) {
            const file = event.target.files[0];
            if (file) {
                this.variants[index].imageName = file.name;
                this.variants[index].preview = URL.createObjectURL(file);
            }
        },
        pickColorFromImage(event, index) {
            const image = event.currentTarget, rect = image.getBoundingClientRect(), canvas = document.createElement('canvas');
            canvas.width = image.naturalWidth; canvas.height = image.naturalHeight;
            const context = canvas.getContext('2d'); context.drawImage(image, 0, 0);
            const x = Math.min(canvas.width - 1, Math.max(0, Math.floor((event.clientX - rect.left) / rect.width * canvas.width)));
            const y = Math.min(canvas.height - 1, Math.max(0, Math.floor((event.clientY - rect.top) / rect.height * canvas.height)));
            const pixel = context.getImageData(x, y, 1, 1).data;
            this.variants[index].color_hex = '#' + [...pixel].slice(0, 3).map(value => value.toString(16).padStart(2, '0')).join('').toUpperCase();
        }
    };
}
</script>
@endpush
@endsection
