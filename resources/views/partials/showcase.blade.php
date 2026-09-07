{{-- Organized High-Capacity Luxury Showcase — Amazon-Easy Fast Commerce --}}
@php
    $isArabicStore = ($settings['storefront_lang'] ?? 'en') === 'ar';
    $bestSellerId = (int) ($settings['featured_bestseller_product_id'] ?? 0);
@endphp
<section class="w-full bg-[#F5F5F0]">

    {{-- Section Header Strip --}}
    <div class="border-b border-black/10 py-5 px-4 sm:px-8 lg:px-14 bg-white/60">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 bg-black inline-block"></span>
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black">
                    {{ $isArabicStore ? 'منتجات مختارة' : 'Featured products' }} ({{ $products->count() }} {{ $isArabicStore ? 'متاح' : 'available' }})
                </span>
            </div>
            <a href="{{ route('collections.show', ['slug' => 'all']) }}"
               class="font-editorial font-bold text-[10px] uppercase tracking-[0.2em] text-black/60 hover:text-black transition-colors flex items-center gap-1">
                <span>{{ $isArabicStore ? 'كل المنتجات' : 'View all products' }}</span>
                <span>→</span>
            </a>
        </div>
    </div>

    {{-- 1. Main High-Density Product Grid --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-8 lg:px-14 py-8 lg:py-12">
        @if(isset($products) && count($products) > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($products as $index => $product)
            @php
                $cover = $product->mediaAssets->firstWhere('is_cover', true) ?? $product->mediaAssets->first();
                $img = $cover && $cover->disk_path
                    ? asset('storage/' . ltrim($cover->disk_path, '/'))
                    : ($product->image_url
                        ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url))
                        : 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80');
                $price = $product->retail_price_minor
                    ? number_format($product->retail_price_minor / 100, 0) . ' EGP'
                    : '—';
                $comparePrice = $product->compare_at_price_minor
                    ? number_format($product->compare_at_price_minor / 100, 0) . ' EGP'
                    : null;
                $variants = $product->variants ?? collect();
                $firstVariant = $variants->first();
                $hoverAsset = $product->mediaAssets->first();
                $hoverImg = $hoverAsset && !empty($hoverAsset->url)
                    ? (str_starts_with($hoverAsset->url, 'http') ? $hoverAsset->url : url($hoverAsset->url))
                    : $img;
                $colorSwatches = $variants->map(function ($variant) {
                    $attributes = $variant->attributes_json ?? [];
                    $color = $attributes['color_hex'] ?? $attributes['hex'] ?? null;
                    return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper($color) : null;
                })->filter()->unique()->take(5)->values();
            @endphp

            <div x-data='{ primary: @json($img), alternate: @json($hoverImg), current: @json($img) }' @mouseenter="current = alternate" @mouseleave="current = primary" class="group bg-white border-2 border-black p-3.5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between reveal-on-scroll">

                <!-- Product Media & Badges -->
                <div>
                    <a href="{{ route('products.show', ['slug' => $product->slug]) }}" class="product-media-frame block relative border border-black/10 mb-3">
                        <img
                            :src="current"
                            alt="{{ $product->title }}"
                            class="w-full h-full object-contain object-center group-hover:scale-105 transition-transform duration-500 ease-out will-change-transform"
                            loading="{{ $index < 4 ? 'eager' : 'lazy' }}"
                        >
                        
                        <!-- Top Badges -->
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            @if($bestSellerId > 0 && $product->id === $bestSellerId)
                                <span class="bg-black text-white font-editorial font-bold text-[8px] uppercase tracking-wider px-2 py-0.5 shadow-sm">
                                    {{ $isArabicStore ? 'الأكثر طلبًا' : 'Best seller' }}
                                </span>
                            @elseif($variants->count() > 1)
                                <span class="bg-white/95 text-black font-editorial font-bold text-[8px] uppercase tracking-wider px-2 py-0.5 border border-black/20">
                                    {{ $variants->count() }} {{ $isArabicStore ? 'ألوان' : 'colors' }}
                                </span>
                            @endif
                        </div>
                    </a>

                    <!-- Product Info -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between gap-2 text-[9px] font-editorial text-black/50">
                            @if($colorSwatches->isNotEmpty())
                                <span class="flex items-center gap-1" aria-label="{{ $isArabicStore ? 'الألوان المتاحة' : 'Available colors' }}">
                                    @foreach($variants as $variant)
                                        @php
                                            $variantColor = $variant->attributes_json['color_hex'] ?? null;
                                            $variantImage = $variant->image_url ? (str_starts_with($variant->image_url, 'http') ? $variant->image_url : url($variant->image_url)) : null;
                                        @endphp
                                        @if(is_string($variantColor) && preg_match('/^#[0-9a-fA-F]{6}$/', $variantColor))
                                            <i @if($variantImage) @mouseenter='current = @json($variantImage)' @mouseleave="current = primary" @endif class="w-2.5 h-2.5 rounded-full border border-black/25 cursor-pointer transition-transform hover:scale-150" style="background-color: {{ $variantColor }}" title="{{ $variant->title }}"></i>
                                        @endif
                                    @endforeach
                                    @if($variants->count() > $colorSwatches->count())<i class="not-italic">+</i>@endif
                                </span>
                            @else
                                <span>{{ $isArabicStore ? 'قطعة مختارة' : 'Selected piece' }}</span>
                            @endif
                            <span class="text-green-700 font-bold">● {{ $isArabicStore ? 'متوفر' : 'In stock' }}</span>
                        </div>

                        <a href="{{ route('products.show', ['slug' => $product->slug]) }}" class="block">
                            <h3 class="font-editorial font-black text-xs sm:text-sm uppercase tracking-normal text-black truncate group-hover:underline underline-offset-2">
                                {{ $product->title }}
                            </h3>
                        </a>

                        @if(!empty($product->material))
                            <p class="font-sans text-[10px] text-black/60 truncate">
                                {{ $product->material }}
                            </p>
                        @endif

                        <!-- Pricing -->
                        <div class="flex items-baseline gap-2 pt-1">
                            <span class="font-sans font-bold text-sm text-black">
                                {{ $price }}
                            </span>
                            @if($comparePrice)
                                <span class="font-sans text-[10px] text-black/40 line-through">
                                    {{ $comparePrice }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Amazon-Easy Fast Commerce Actions -->
                <div class="pt-3 border-t border-black/10 mt-3 grid grid-cols-2 gap-2">
                    <!-- 1-Click Quick Add to Bag -->
                    <form method="POST" action="{{ route('cart.add') }}" class="w-full">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        @if($firstVariant)
                            <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
                        @endif
                        <input type="hidden" name="qty" value="1">
                        <button type="submit" class="w-full border-2 border-black bg-white text-black py-2 px-1 text-[10px] font-editorial font-bold uppercase tracking-wider hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-1 cursor-pointer">
                            <span>{{ $isArabicStore ? '+ أضف للسلة' : '+ Add to bag' }}</span>
                        </button>
                    </form>

                    <!-- 1-Click Instant Buy Now -->
                    <a href="{{ route('checkout') }}?product_id={{ $product->id }}{{ $firstVariant ? '&variant_id=' . $firstVariant->id : '' }}" 
                       class="w-full border-2 border-black bg-black text-white py-2 px-1 text-[10px] font-editorial font-bold uppercase tracking-wider hover:bg-neutral-800 transition-colors flex items-center justify-center text-center">
                        {{ $isArabicStore ? 'اشترِ الآن ←' : 'Buy now →' }}
                    </a>
                </div>

            </div>
            @endforeach
        </div>

        {{-- Organized Category Exploration Section --}}
        @if(isset($collections) && $collections->count() > 0)
        <div class="mt-16 pt-12 border-t-2 border-black">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="font-editorial font-bold text-[9px] uppercase tracking-[0.25em] text-black/50 block mb-1">
                        {{ $isArabicStore ? 'تسوق حسب الفئة' : 'Shop by category' }}
                    </span>
                    <h2 class="font-display text-2xl sm:text-3xl uppercase text-black">
                        {{ $isArabicStore ? 'اختر ما يناسبك' : 'Choose what suits you' }}
                    </h2>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($collections->take(3) as $cSection)
                <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-black/10 mb-4">
                            <h3 class="font-editorial font-black text-base uppercase text-black">{{ $cSection->title }}</h3>
                            <span class="text-[10px] font-mono font-bold bg-black text-white px-2 py-0.5">
                                {{ $cSection->products->count() }} {{ $isArabicStore ? 'منتجات' : 'Items' }}
                            </span>
                        </div>
                        <p class="font-sans text-xs text-black/60 mb-6 line-clamp-2">
                            {{ $cSection->description ?? 'Handcrafted minimalist EDC and bespoke leather pieces designed for daily endurance.' }}
                        </p>
                    </div>

                    <a href="{{ route('collections.show', ['slug' => $cSection->slug]) }}" class="w-full text-center py-2.5 bg-black text-white font-editorial font-bold text-xs uppercase tracking-wider hover:bg-neutral-800 transition-colors block">
                        {{ $isArabicStore ? 'استكشف ' . $cSection->title . ' ←' : 'Explore ' . $cSection->title . ' →' }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Load More / Full Catalog Banner --}}
        <div class="text-center mt-12 pt-6">
            <a href="{{ route('collections.show', ['slug' => 'all']) }}"
               class="inline-flex items-center gap-3 font-editorial font-black text-xs uppercase tracking-[0.25em] border-2 border-black bg-white px-8 py-4 text-black hover:bg-black hover:text-white transition-all duration-300 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-1 hover:translate-y-1">
                <span>{{ $isArabicStore ? 'تصفح كل ' . $products->count() . ' المنتجات' : 'Browse all ' . $products->count() . ' products' }}</span>
                <span>→</span>
            </a>
        </div>

        @else
        <div class="py-20 text-center bg-white border-2 border-dashed border-black/20 p-8">
            <p class="font-editorial font-bold text-xs uppercase tracking-widest text-black/40">No products yet in archive</p>
        </div>
        @endif
    </div>

</section>
