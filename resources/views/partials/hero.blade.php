{{-- Minimal Luxury Hero — Clean, Restrained, Editorial --}}
@php
    $isArabicStore = ($settings['storefront_lang'] ?? 'en') === 'ar';
@endphp
<section class="w-full bg-[#F5F5F0] border-b border-black/15 py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-8 lg:px-14">

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">

            {{-- Left: Wordmark + Tagline --}}
            <div class="space-y-2">
                <p class="font-editorial font-bold text-[9px] sm:text-[10px] uppercase tracking-[0.35em] text-black/50 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-black"></span>
                    <span>{{ $isArabicStore ? 'إكسسوارات مصنوعة بعناية للاستخدام اليومي' : ($settings['homeHeroBadge'] ?? 'Handcrafted accessories for everyday use') }}</span>
                </p>
                <h1 class="font-display uppercase text-black leading-none"
                    style="font-size: clamp(1.8rem, 5vw, 3.4rem); letter-spacing: -0.01em;">
                    {{ $isArabicStore ? 'اكتشف القطعة المناسبة لك' : ($settings['homeHeroTitle'] ?? 'Find the piece that fits your day') }}
                </h1>
                <p class="font-sans text-xs text-black/60 leading-relaxed max-w-xl">
                    {{ $isArabicStore ? 'خامات متينة، حماية للبطاقات، وشحن لجميع المحافظات داخل مصر.' : ($settings['homeHeroSubtitle'] ?? 'Durable materials, card protection, and delivery across Egypt.') }}
                </p>
            </div>

            {{-- Right: Quick Action Buttons --}}
            <div class="flex items-center gap-3 shrink-0 pb-1">
                <a href="{{ route('collections.show', ['slug' => 'all']) }}"
                   class="font-editorial font-bold text-xs uppercase tracking-wider border-2 border-black bg-white px-4 py-2.5 text-black hover:bg-black hover:text-white transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                    {{ $isArabicStore ? 'تصفح كل المنتجات ←' : 'Browse all products →' }}
                </a>
                @if(isset($featuredProduct))
                <a href="{{ route('products.show', ['slug' => $featuredProduct->slug]) }}"
                   class="font-editorial font-black text-xs uppercase tracking-wider bg-black text-white px-4 py-2.5 hover:bg-neutral-800 transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0.3)]">
                    {{ $isArabicStore ? 'شاهد المنتج المميز ←' : 'See featured product →' }}
                </a>
                @endif
            </div>

        </div>

    </div>
</section>
