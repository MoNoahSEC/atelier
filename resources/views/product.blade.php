@extends('layouts.app')

@section('title', ($product->seo_title ?: $product->title) . ' — ' . ($settings['storeName'] ?? 'ATELIER'))
@section('meta_description', $product->seo_description ?: Str::limit(strip_tags($product->description ?? ''), 160))

@section('content')
@php
    $mainImg = $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85';
    $basePriceEgp = $product->retail_price_minor ? number_format($product->retail_price_minor / 100, 0) . ' EGP' : '780 EGP';
    $variants = $product->variants ?? collect();
    $mediaAssets = $product->mediaAssets ?? collect();

    // Map variants data for Alpine.js
    $variantsJson = $variants->map(function($v) use ($product, $mainImg) {
        $price = $v->effective_price_minor ? number_format($v->effective_price_minor / 100, 0) . ' EGP' : number_format($product->retail_price_minor / 100, 0) . ' EGP';
        $vImg = $v->image_url 
            ? (str_starts_with($v->image_url, 'http') ? $v->image_url : url($v->image_url))
            : (isset($v->attributes_json['image_url']) && !empty($v->attributes_json['image_url']) 
                ? (str_starts_with($v->attributes_json['image_url'], 'http') ? $v->attributes_json['image_url'] : url($v->attributes_json['image_url']))
                : $mainImg);
        return [
            'id'             => (string)$v->id,
            'title'          => $v->title,
            'attribute_name' => $v->attribute_name ?: 'Option',
            'price'          => $price,
            'inventory'      => $v->inventory,
            'image'          => $vImg,
            'color_hex'      => $v->attributes_json['color_hex'] ?? '#161616',
        ];
    })->values();
@endphp

<div 
    x-data="{ 
        activeImage: '{{ $mainImg }}',
        selectedImage: '{{ $mainImg }}',
        activePrice: '{{ count($variantsJson) > 0 ? $variantsJson[0]['price'] : $basePriceEgp }}',
        activeStock: {{ count($variantsJson) > 0 ? $variantsJson[0]['inventory'] : $product->inventory }},
        selectedVariantId: '{{ count($variantsJson) > 0 ? $variantsJson[0]['id'] : '' }}',
        selectedOptionTitle: '{{ count($variantsJson) > 0 ? $variantsJson[0]['title'] : '' }}',
        qty: 1,
        variantsMap: {{ json_encode($variantsJson) }},
        selectVariant(v) {
            this.selectedVariantId = v.id;
            this.selectedOptionTitle = v.title;
            this.activePrice = v.price;
            this.activeStock = v.inventory;
            if (v.image) {
                this.activeImage = v.image;
                this.selectedImage = v.image;
            }
        },
        previewVariant(v) { if (v.image) this.activeImage = v.image; },
        restoreSelectedImage() { this.activeImage = this.selectedImage || '{{ $mainImg }}'; },
        shareProduct() {
            if (navigator.share) {
                navigator.share({ title: '{{ $product->title }}', url: window.location.href });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }
    }"
    class="bg-[#F5F5F0] min-h-screen py-5 sm:py-8 lg:py-20"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        
        <!-- A compact return link is clearer than a long breadcrumb trail on phones. -->
        <div class="mb-5 sm:mb-7">
            <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="inline-flex items-center gap-2 text-[10px] font-editorial font-bold uppercase tracking-[0.16em] text-black/60 hover:text-black transition-colors">
                <span aria-hidden="true">←</span>{{ ($settings['storefront_lang'] ?? 'en') === 'ar' ? 'العودة للمنتجات' : 'Back to catalog' }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
            
            <!-- Product Gallery Column -->
            <div class="lg:col-span-7 space-y-4">
                <div class="relative w-full overflow-hidden border-2 border-black bg-white p-3 shadow-[12px_12px_0px_0px_rgba(0,0,0,1)]">
                    <div class="product-media-frame relative">
                        <img 
                            :src="activeImage" 
                            alt="{{ $product->title }}" 
                            class="w-full h-full object-contain object-center transition-all duration-500 ease-out"
                            fetchpriority="high" decoding="async"
                        >
                    </div>
                </div>

                <!-- Thumbnails Rail -->
                @if(count($mediaAssets) > 0 || count($variants) > 0)
                    @php
                        $allThumbAssets = collect($mediaAssets)->filter(fn($a) => !empty($a->url));
                        $uniqueAssetUrls = $allThumbAssets->map(fn($a) => str_starts_with($a->url, 'http') ? $a->url : url($a->url))->unique()->values();
                    @endphp
                    <div class="flex items-center gap-3 overflow-x-auto pb-2">
                        <!-- Main cover thumb -->
                        <button 
                            type="button" 
                            @click="activeImage = '{{ $mainImg }}'; selectedImage = '{{ $mainImg }}'"
                            @mouseenter="activeImage = '{{ $mainImg }}'" @mouseleave="restoreSelectedImage()"
                            class="w-16 h-16 border p-0.5 bg-white shrink-0 cursor-pointer transition-all duration-200"
                            :class="activeImage === '{{ $mainImg }}' ? 'border-2 border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'border-black/30 opacity-70 hover:opacity-100'"
                        >
                            <img src="{{ $mainImg }}" alt="Cover" class="w-full h-full object-contain bg-white" loading="eager" decoding="async">
                        </button>

                        @foreach($uniqueAssetUrls as $thumbUrl)
                            @if($thumbUrl !== $mainImg)
                                <button 
                                    type="button" 
                                    @click="activeImage = '{{ $thumbUrl }}'; selectedImage = '{{ $thumbUrl }}'"
                                    @mouseenter="activeImage = '{{ $thumbUrl }}'" @mouseleave="restoreSelectedImage()"
                                    class="w-16 h-16 border p-0.5 bg-white shrink-0 cursor-pointer transition-all duration-200"
                                    :class="activeImage === '{{ $thumbUrl }}' ? 'border-2 border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'border-black/30 opacity-70 hover:opacity-100'"
                                >
                                    <img src="{{ $thumbUrl }}" alt="" class="w-full h-full object-contain bg-white" loading="lazy" decoding="async">
                                </button>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(count($variantsJson) > 0)
                    <div class="mt-4 rounded-xl border border-black/10 bg-white/70 p-3 sm:p-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <span class="font-editorial font-bold text-[10px] uppercase tracking-[.16em] text-black/55">{{ ($settings['storefront_lang'] ?? 'en') === 'ar' ? 'اختر اللون' : 'Choose color' }}</span>
                            <span class="text-xs text-black/65" x-text="selectedOptionTitle"></span>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            @foreach($variantsJson as $vItem)
                                <button type="button" @click="selectVariant({{ json_encode($vItem) }})" @mouseenter="previewVariant({{ json_encode($vItem) }})" @mouseleave="restoreSelectedImage()" class="group flex flex-col items-center gap-1.5" :aria-label="'{{ addslashes($vItem['title']) }}'">
                                    <span class="w-9 h-9 rounded-full border-2 transition-all shadow-sm" style="background-color: {{ $vItem['color_hex'] }}" :class="selectedVariantId === '{{ $vItem['id'] }}' ? 'ring-2 ring-black ring-offset-2 border-black scale-110' : 'border-black/20 hover:scale-105'"></span>
                                    <span class="max-w-[68px] truncate text-[9px] font-semibold text-black/60 group-hover:text-black">{{ $vItem['title'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Product Specs & Action Column -->
            <div class="lg:col-span-5 space-y-6">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <template x-if="activeStock > 0">
                            <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black">
                                IN STOCK · 24H EXPRESS DISPATCH
                            </span>
                        </template>
                        <template x-if="activeStock <= 0">
                            <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-red-600">
                                CURRENTLY OUT OF STOCK
                            </span>
                        </template>
                        <button @click="shareProduct()" type="button" class="text-xs font-editorial font-bold uppercase tracking-wider text-black hover:underline cursor-pointer">
                            ↗ Share
                        </button>
                    </div>

                    <h1 class="font-sans font-extrabold text-[clamp(1.75rem,4vw,3.25rem)] tracking-[-0.04em] text-black leading-[1.06]"
                        style="word-break: break-word; overflow-wrap: break-word;">
                        {{ $product->title }}
                    </h1>

                    <div class="pt-2">
                        @php
                            $isArProd = ($settings['storefront_lang'] ?? 'en') === 'ar';
                        @endphp
                        <span class="font-editorial font-black text-3xl text-black" x-text="activePrice"></span>
                        @if($product->compare_at_price_minor)
                            <span class="text-sm text-gray-400 line-through font-editorial ml-2">
                                {{ number_format($product->compare_at_price_minor / 100, 0) }} {{ $isArProd ? 'ج.م' : 'EGP' }}
                            </span>
                        @endif
                        <span class="text-[10px] text-black/60 font-editorial font-bold uppercase tracking-widest block mt-0.5">
                            {{ $isArProd ? 'الضريبة والتوصيل يُحسبان عند إتمام الطلب' : 'Taxes and delivery are calculated at checkout' }}
                        </span>
                    </div>
                </div>

                <!-- Rich Description -->
                <div class="border-t border-b border-black/10 py-4 font-sans text-xs sm:text-sm text-black/80 leading-relaxed prose prose-sm max-w-none">
                    {!! $product->description !!}
                </div>

                <!-- Variant Selection -->
                @if(count($variantsJson) > 0)
                    <div class="hidden space-y-3">
                        <span class="font-editorial font-bold text-xs uppercase tracking-wider text-black block">
                            {{ $isArProd ? 'الخيار المختار:' : 'Option:' }} <span x-text="selectedOptionTitle" class="font-sans font-normal text-black/70"></span>
                        </span>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            @foreach($variantsJson as $vItem)
                                <button 
                                    type="button" 
                                    @click="selectVariant({{ json_encode($vItem) }})"
                                    class="flex items-center gap-2 border px-3.5 py-2.5 text-xs font-editorial font-bold uppercase tracking-wider transition-all min-h-[44px] cursor-pointer"
                                    :class="selectedVariantId === '{{ $vItem['id'] }}' ? 'border-2 border-black bg-black text-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]' : 'border-black/30 bg-white text-black hover:border-black'"
                                >
                                    <span>{{ $vItem['title'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Technical Specifications (Dimensions & Materials) -->
                @if($product->material || $product->dimensions || $product->weight)
                    <div class="border-2 border-black p-4 bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] space-y-2 text-xs">
                        <h3 class="font-editorial font-bold uppercase text-[11px] tracking-wider text-black border-b border-black/10 pb-1.5">
                            📐 {{ $isArProd ? 'المواصفات الفنية والخامات' : 'Technical Specifications' }}
                        </h3>
                        <dl class="space-y-1.5 font-sans">
                            @if($product->material)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 font-bold uppercase text-[10px]">{{ $isArProd ? 'الخامة' : 'Material' }}</dt>
                                    <dd class="text-black font-semibold text-right">{{ $product->material }}</dd>
                                </div>
                            @endif
                            @if($product->dimensions)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 font-bold uppercase text-[10px]">{{ $isArProd ? 'الأبعاد' : 'Dimensions' }}</dt>
                                    <dd class="text-black font-mono font-semibold text-right">{{ $product->dimensions }}</dd>
                                </div>
                            @endif
                            @if($product->weight)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 font-bold uppercase text-[10px]">{{ $isArProd ? 'الوزن' : 'Weight' }}</dt>
                                    <dd class="text-black font-mono font-semibold text-right">{{ $product->weight }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                <!-- Quantity & Purchase Actions -->
                <form method="POST" action="{{ route('cart.add') }}" class="space-y-4 pt-2">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="variant_id" :value="selectedVariantId">

                    <!-- Quantity Control -->
                    <div class="flex items-center justify-between border-2 border-black bg-white p-3 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        <span class="font-editorial font-bold text-xs uppercase tracking-wider text-black">{{ $isArProd ? 'الكمية المطلوبة' : 'Quantity' }}</span>
                        <div class="flex items-center border-2 border-black bg-[#F5F5F0]">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" class="w-9 h-9 flex items-center justify-center font-bold text-base hover:bg-black hover:text-white transition-colors">−</button>
                            <input type="number" name="qty" x-model="qty" min="1" max="20" class="w-12 h-9 text-center font-mono font-bold text-xs border-x-2 border-black bg-white focus:outline-none" readonly>
                            <button type="button" @click="qty = Math.min(20, qty + 1)" class="w-9 h-9 flex items-center justify-center font-bold text-base hover:bg-black hover:text-white transition-colors">+</button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2.5">
                        <button type="submit" class="btn-luxury w-full py-4 text-center text-xs tracking-[0.2em] flex items-center justify-center gap-2 cursor-pointer">
                            <span>{{ $isArProd ? 'إضافة إلى السلة' : 'Add to Cart' }}</span>
                            <span class="text-sm font-bold">+</span>
                        </button>
                        
                        <a :href="'{{ route('checkout') }}?product_id={{ $product->id }}' + (selectedVariantId ? '&variant_id=' + selectedVariantId : '')" 
                           class="btn-luxury-outline w-full py-3.5 text-center text-xs tracking-[0.2em] block">
                            {{ $isArProd ? 'شراء فوري مباشر ←' : 'Buy Now →' }}
                        </a>
                    </div>
                </form>

                <div class="text-center pt-1">
                    <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black/50 hover:text-black transition-colors">
                        {{ $isArProd ? '← العودة لتصفح جميع المنتجات' : '← Continue Shopping' }}
                    </a>
                </div>

                <!-- Warranty Highlights -->
                <div class="grid grid-cols-2 gap-3 pt-6 border-t border-black/10 text-xs">
                    <div class="border border-black p-3 bg-white">
                        <span class="font-editorial font-bold uppercase block text-black">{{ $isArProd ? 'ضمان عامين' : '2-Year Warranty' }}</span>
                        <span class="text-[10px] text-black/60">{{ $isArProd ? 'صيانة أو استبدال فوري' : 'Full repair or replacement' }}</span>
                    </div>
                    <div class="border border-black p-3 bg-white">
                        <span class="font-editorial font-bold uppercase block text-black">{{ $isArProd ? 'استبدال سريع' : 'Fast Exchange' }}</span>
                        <span class="text-[10px] text-black/60">{{ $isArProd ? 'خدمة استبدال لباب البيت' : 'Free door-to-door swaps' }}</span>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

{{-- Related Products Section --}}
@if(isset($relatedProducts) && $relatedProducts->count() > 0)
<section class="bg-[#F5F5F0] border-t border-black py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">

        <div class="flex items-baseline justify-between mb-8">
            <h2 class="font-display text-fluid-title uppercase text-black" style="font-size: clamp(1.4rem, 4vw, 2.5rem);">
                {{ $isArProd ? 'قد يعجبك أيضاً' : 'You May Also Like' }}
            </h2>
            <a href="{{ route('collections.show', ['slug' => 'all']) }}"
               class="font-editorial font-bold text-[10px] uppercase tracking-[0.2em] text-black hover:opacity-60 transition-opacity flex items-center gap-1">
                {{ $isArProd ? 'عرض الكل ←' : 'View All →' }}
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($relatedProducts->take(4) as $related)
            @php
                $relImg = $related->image_url
                    ? (str_starts_with($related->image_url, 'http') ? $related->image_url : url($related->image_url))
                    : 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=600&q=80';
                $relPrice = $related->retail_price_minor
                    ? number_format($related->retail_price_minor / 100, 0) . ' EGP'
                    : '780 EGP';
                $relatedAlt = $related->mediaAssets->first()?->url ?? $relImg;
                $relatedAlt = str_starts_with($relatedAlt, 'http') ? $relatedAlt : url($relatedAlt);
            @endphp
            <a href="{{ route('products.show', ['slug' => $related->slug]) }}"
               x-data='{ primary: @json($relImg), alternate: @json($relatedAlt), current: @json($relImg) }' @mouseenter="current = alternate" @mouseleave="current = primary"
               class="product-card group block bg-white border border-black overflow-hidden">
                <div class="product-media-frame relative w-full">
                    <img
                        :src="current"
                        alt="{{ $related->title }}"
                        class="w-full h-full object-contain object-center group-hover:scale-105 transition-transform duration-500 ease-out"
                        style="position: absolute; inset: 0; width: 100%; height: 100%;"
                        loading="lazy"
                    >
                </div>
                <div class="p-3 border-t border-black/10">
                    <p class="font-editorial font-bold text-[10px] uppercase tracking-wider text-black truncate mb-1">{{ $related->title }}</p>
                    <p class="font-editorial font-bold text-sm text-black">{{ $relPrice }}</p>
                </div>
            </a>
            @endforeach
        </div>

    </div>
</section>
@endif

@endsection
