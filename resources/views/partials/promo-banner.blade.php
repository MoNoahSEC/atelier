{{-- Top Promotional Banner (Admin Controlled with Dimensions, Crop, & Focal Point) --}}
@php
    $isArabicStore = ($settings['storefront_lang'] ?? 'en') === 'ar';
    $bannerImg = $settings['homepage_banner_image'] ?? '';
    $bannerVideo = $settings['homepage_banner_video'] ?? '';
    $bannerTitle = $settings['homepage_banner_title'] ?? '';
    $bannerSubtitle = $settings['homepage_banner_subtitle'] ?? '';
    $bannerLink = $settings['homepage_banner_link'] ?? '';
    
    // Fallback image if title exists but image not uploaded yet
    if (empty($bannerImg) && (!empty($bannerTitle) || !empty($bannerSubtitle))) {
        $bannerImg = 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1800&q=85';
    }
    
    $bannerImgUrl = $bannerImg ? (str_starts_with($bannerImg, 'http') ? $bannerImg : url($bannerImg)) : '';
    $bannerVideoUrl = $bannerVideo ? (str_starts_with($bannerVideo, 'http') ? $bannerVideo : url($bannerVideo)) : '';

    // Dimensions & Position Settings
    $bannerHeightSetting = $settings['homepage_banner_height'] ?? '48vh';
    $heightMap = [
        'compact' => 'h-[280px] sm:h-[340px] lg:h-[380px]',
        'medium'  => 'h-[360px] sm:h-[440px] lg:h-[500px]',
        'large'   => 'h-[460px] sm:h-[560px] lg:h-[640px]',
        'full'    => 'h-[70vh] sm:h-[80vh] lg:h-[88vh]',
    ];
    $heightClass = $heightMap[$bannerHeightSetting] ?? (str_ends_with($bannerHeightSetting, 'vh') || str_ends_with($bannerHeightSetting, 'px') ? '' : 'h-[36vh] sm:h-[44vh] lg:h-[48vh]');
    $customHeightStyle = (!isset($heightMap[$bannerHeightSetting]) && (str_ends_with($bannerHeightSetting, 'vh') || str_ends_with($bannerHeightSetting, 'px') || str_ends_with($bannerHeightSetting, '%'))) 
        ? "height: {$bannerHeightSetting};" 
        : '';

    $bannerPosition = $settings['homepage_banner_position'] ?? 'center center';
    $bannerFit = $settings['homepage_banner_fit'] ?? 'cover';
    $bannerZoom = (float)($settings['homepage_banner_zoom'] ?? 100) / 100;
    if ($bannerZoom < 0.5) $bannerZoom = 1;
    $bannerOverlay = $settings['homepage_banner_overlay'] ?? 'medium';

    $overlayClasses = [
        'none'   => 'bg-transparent',
        'light'  => 'bg-black/25',
        'medium' => 'bg-gradient-to-t from-black via-black/60 to-black/40 group-hover:via-black/50',
        'dark'   => 'bg-gradient-to-t from-black via-black/80 to-black/60',
    ];
    $overlayClass = $overlayClasses[$bannerOverlay] ?? $overlayClasses['medium'];
@endphp

@if(!empty($bannerImgUrl) || !empty($bannerVideoUrl))
<section class="relative w-full overflow-hidden border-b border-black bg-black text-white reveal-on-scroll">
    @if(!empty($bannerLink))
        <a href="{{ str_starts_with($bannerLink, 'http') ? $bannerLink : url($bannerLink) }}" class="block group relative w-full {{ $heightClass }} overflow-hidden cursor-pointer" style="{{ $customHeightStyle }}">
    @else
        <div class="relative w-full {{ $heightClass }} overflow-hidden" style="{{ $customHeightStyle }}">
    @endif

        {{-- A muted inline video takes priority; the image remains its instant poster/fallback. --}}
        @if(!empty($bannerVideoUrl))
            <video autoplay muted loop playsinline preload="metadata" poster="{{ $bannerImgUrl }}"
                class="absolute inset-0 w-full h-full object-cover grayscale contrast-125"
                style="object-position: {{ $bannerPosition }}; transform: scale({{ $bannerZoom }});">
                <source src="{{ $bannerVideoUrl }}">
            </video>
        @else
            <img 
                src="{{ $bannerImgUrl }}" 
                alt="{{ $bannerTitle ?: 'Promotional Banner' }}"
                class="absolute inset-0 w-full h-full grayscale contrast-125 transition-transform duration-[12000ms] ease-out group-hover:scale-105 will-change-transform"
                style="object-position: {{ $bannerPosition }}; object-fit: {{ $bannerFit }}; transform: scale({{ $bannerZoom }});"
            >
        @endif

        {{-- Dark Gradient Overlay for Readability --}}
        <div class="absolute inset-0 {{ $overlayClass }} transition-colors duration-500"></div>

        {{-- Overlay Content --}}
        <div class="relative z-10 w-full h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 flex flex-col justify-end pb-8 sm:pb-12 space-y-2 sm:space-y-3">
            <div class="inline-flex items-center gap-2 border border-white/40 px-2.5 py-0.5 bg-black/60 backdrop-blur-xs w-fit">
                <span class="w-1.5 h-1.5 bg-white"></span>
                <span class="font-editorial font-bold text-[9px] sm:text-[10px] uppercase tracking-[0.2em] text-white">
                    {{ $isArabicStore ? 'إصدار مميز' : 'Special release' }}
                </span>
            </div>

            @if(!empty($bannerTitle))
                <h2 class="font-editorial font-black text-2xl sm:text-4xl lg:text-5xl uppercase tracking-normal text-white leading-tight max-w-3xl drop-shadow-md">
                    {{ $bannerTitle }}
                </h2>
            @endif

            @if(!empty($bannerLink))
                <div class="pt-2">
                    <span class="inline-flex items-center gap-2 font-editorial font-bold text-xs uppercase tracking-[0.15em] text-white group-hover:underline underline-offset-4">
                        <span>{{ $isArabicStore ? 'اكتشف العرض ←' : 'Explore offer →' }}</span>
                        <span class="transition-transform group-hover:translate-x-1 duration-300">→</span>
                    </span>
                </div>
            @endif
        </div>

    @if(!empty($bannerLink))
        </a>
    @else
        </div>
    @endif
</section>
@endif
