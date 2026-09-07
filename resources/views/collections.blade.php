@extends('layouts.app')

@php
    $isArCol = ($settings['storefront_lang'] ?? 'en') === 'ar';
@endphp

@section('title', ($isArCol ? 'جميع المنتجات — ' : ($collectionTitle ?? 'All Products') . ' — ') . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
        
        <!-- Collection Editorial Header -->
        <div class="border-b-2 border-black pb-8 mb-10 reveal-on-scroll">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 bg-black"></span>
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black/70">
                    {{ $isArCol ? ($currentSlug === 'all' ? 'جميع المنتجات' : 'تصنيف المنتجات') : ($currentSlug === 'all' ? 'FULL ARCHIVE' : 'CATEGORY SPOTLIGHT') }}
                </span>
            </div>
            <h1 class="font-editorial font-black text-3xl sm:text-5xl uppercase tracking-tight text-black">
                {{ $isArCol ? ($currentSlug === 'all' ? 'جميع المنتجات الفاخرة' : ($collectionTitle ?? 'المنتجات')) : ($collectionTitle ?? 'All Products') }}
            </h1>
            <p class="font-sans text-xs sm:text-sm text-black/75 mt-2 max-w-2xl leading-relaxed">
                {{ $isArCol ? 'محافظ ذكية بخاصية حجب RFID، حوامل بطاقات مغناطيسية وإكسسوارات جلد طبيعي فاخرة مصنوعة يدوياً.' : ($collectionDescription ?? 'Precision engineered RFID smart wallets, magnetic phone attachments, and handcrafted full-grain leather EDC accessories.') }}
            </p>
        </div>

        <!-- Filter & Category Rail with Live Counts -->
        <div class="flex items-center gap-2.5 overflow-x-auto pb-4 mb-10 text-xs font-editorial font-bold uppercase tracking-wider scrollbar-none">
            <a 
                href="{{ route('collections.show', ['slug' => 'all']) }}" 
                class="px-4 py-2.5 border-2 border-black transition-all flex items-center gap-2 whitespace-nowrap {{ ($currentSlug ?? 'all') === 'all' ? 'bg-black text-white shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'bg-white text-black hover:bg-black hover:text-white' }}"
            >
                <span>{{ $isArCol ? 'جميع القطع' : 'ALL PIECES' }}</span>
                <span class="text-[10px] font-mono opacity-80">({{ \App\Models\Product::active()->count() }})</span>
            </a>

            @foreach($collections as $col)
                @php $colProductCount = $col->products()->count(); @endphp
                <a 
                    href="{{ route('collections.show', ['slug' => $col->slug]) }}" 
                    class="px-4 py-2.5 border-2 border-black transition-all flex items-center gap-2 whitespace-nowrap {{ ($currentSlug ?? '') === $col->slug ? 'bg-black text-white shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'bg-white text-black hover:bg-black hover:text-white' }}"
                >
                    <span>{{ $col->title }}</span>
                    @if($colProductCount > 0)
                        <span class="text-[10px] font-mono opacity-80">({{ $colProductCount }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            @forelse($products as $index => $product)
                @php
                    $cover = $product->mediaAssets->firstWhere('is_cover', true) ?? $product->mediaAssets->first();
                    $img = $cover && $cover->disk_path
                        ? asset('storage/' . ltrim($cover->disk_path, '/'))
                        : ($product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80');
                    $price = $product->retail_price_minor ? number_format($product->retail_price_minor / 100, 0) . ' ' . ($isArCol ? 'ج.م' : 'EGP') : '—';
                    $hoverAsset = $product->mediaAssets->first();
                    $hoverImg = $hoverAsset && $hoverAsset->disk_path ? asset('storage/' . ltrim($hoverAsset->disk_path, '/')) : ($hoverAsset->url ?? $img);
                    $colorSwatches = $product->variants->map(function ($variant) {
                        $attributes = $variant->attributes_json ?? [];
                        $color = $attributes['color_hex'] ?? $attributes['hex'] ?? null;
                        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper($color) : null;
                    })->filter()->unique()->take(5)->values();
                @endphp
                <a 
                    href="{{ route('products.show', ['slug' => $product->slug]) }}" 
                    x-data="{ primary: '{{ $img }}', alternate: '{{ $hoverImg }}', current: '{{ $img }}' }" @mouseenter="current = alternate" @mouseleave="current = primary"
                    class="group block border-2 border-black bg-white p-4 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:shadow-[14px_14px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 transition-all duration-300 reveal-on-scroll"
                    style="transition-delay: {{ ($index % 3) * 100 }}ms;"
                >
                    <!-- Image Frame -->
                    <div class="product-media-frame relative mb-4 border border-black/10">
                        <img 
                            :src="current" 
                            alt="{{ $product->title }}" 
                            class="w-full h-full object-contain object-center group-hover:scale-105 transition-transform duration-500 ease-out"
                            loading="lazy"
                            decoding="async"
                        >
                        
                        <!-- Badge -->
                        <div class="absolute top-2 left-2">
                            <span class="bg-black text-white text-[9px] font-editorial font-bold uppercase tracking-widest px-2 py-0.5">
                                {{ $isArCol ? 'فاخر' : 'BESPOKE EDC' }}
                            </span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            @if($colorSwatches->isNotEmpty())
                                <span class="flex items-center gap-1" aria-label="{{ $isArCol ? 'الألوان المتاحة' : 'Available colors' }}">
                                    @foreach($product->variants as $variant)
                                        @php
                                            $variantColor = $variant->attributes_json['color_hex'] ?? null;
                                            $variantImage = $variant->image_url ? (str_starts_with($variant->image_url, 'http') ? $variant->image_url : url($variant->image_url)) : null;
                                        @endphp
                                        @if(is_string($variantColor) && preg_match('/^#[0-9a-fA-F]{6}$/', $variantColor))
                                            <i @if($variantImage) @mouseenter='current = @json($variantImage)' @mouseleave="current = primary" @endif class="w-2.5 h-2.5 rounded-full border border-black/25 cursor-pointer transition-transform hover:scale-150" style="background-color: {{ $variantColor }}" title="{{ $variant->title }}"></i>
                                        @endif
                                    @endforeach
                                    @if($product->variants->count() > $colorSwatches->count())<i class="text-[9px] not-italic text-black/50">+</i>@endif
                                </span>
                            @else
                                <span class="text-[9px] font-editorial uppercase tracking-widest text-black/45">{{ $isArCol ? 'قطعة مختارة' : 'Selected piece' }}</span>
                            @endif
                            <span class="text-xs text-black tracking-widest">★★★★★</span>
                        </div>
                        
                        <h3 class="font-editorial font-black text-base sm:text-lg uppercase tracking-normal text-black group-hover:underline line-clamp-1 leading-snug">
                            {{ $product->title }}
                        </h3>

                        <p class="font-sans text-xs text-black/65 line-clamp-2 leading-relaxed">
                            {{ $product->description }}
                        </p>

                        <div class="pt-3 flex items-center justify-between border-t border-black/10 mt-3">
                            <div>
                                <span class="font-editorial font-black text-base sm:text-lg text-black block leading-none">
                                    {{ $price }}
                                </span>
                                <span class="text-[9px] text-green-700 font-bold uppercase">{{ $isArCol ? 'متوفر · شحن سريع' : 'In Stock · Ships Fast' }}</span>
                            </div>
                            <span class="font-editorial font-bold text-xs uppercase tracking-wider bg-black text-white px-3 py-1.5 group-hover:bg-neutral-800 transition-colors">
                                {{ $isArCol ? 'عرض المنتج ←' : 'View Product →' }}
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-16 text-center border-2 border-dashed border-black/30 p-8 bg-white">
                    <span class="text-3xl block mb-2">📦</span>
                    <h3 class="font-editorial font-bold text-lg uppercase tracking-wider text-black">
                        {{ $isArCol ? 'لم يتم العثور على قطع في هذا التصنيف' : 'No pieces found in this specific collection' }}
                    </h3>
                    <p class="text-xs text-black/60 max-w-md mx-auto mt-1 mb-6">
                        {{ $isArCol ? 'تصفح تشكيلتنا الكاملة لاكتشاف جميع المحافظ وإكسسوارات الجلد الطبيعي.' : 'Browse all our handcrafted wallets, money clips, and EDC products.' }}
                    </p>
                    <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="btn-luxury inline-block px-8 py-3 text-xs tracking-wider">
                        {{ $isArCol ? 'تصفح جميع المنتجات (' . \App\Models\Product::active()->count() . ') ←' : 'Shop All Products (' . \App\Models\Product::active()->count() . ') →' }}
                    </a>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection
