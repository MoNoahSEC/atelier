@php
    // The storefront language is a single store setting managed in the admin panel.
    $storeLocale = $settings['storefront_lang'] ?? 'en';
    $isArabicStore = $storeLocale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $isArabicStore ? 'ar' : 'en' }}" dir="{{ $isArabicStore ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ATELIER — Luxury Accessories & Bespoke EDC')</title>
    <meta name="description" content="@yield('meta_description', 'Precision crafted luxury smart wallets, cardholders, and minimalist everyday carry in full-grain leather and titanium.')">

    <!-- PWA & Mobile Web App Meta (Circular Monogram Favicon) -->
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon.png') }}?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=3">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=3">
    <link rel="manifest" href="{{ asset('manifest.json') }}?v=3">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ATELIER">

    <!-- High-End Editorial Typography: Abril Fatface + Cinzel + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abril+Fatface&family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Monochrome Luxury Theme) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        black: '#000000',
                        white: '#FFFFFF',
                        'off-white': '#F5F5F0',
                        'warm-grey': '#E8E8E3',
                        'charcoal': '#121212',
                        'muted-border': '#E0E0DB'
                    },
                    fontFamily: {
                        /* display: ONLY for large hero/banner/section headlines — Abril Fatface (Didone/Bodoni) */
                        display: ['"Abril Fatface"', 'Georgia', 'serif'],
                        /* editorial: sub-labels, prices, badges — Cinzel */
                        editorial: ['Cinzel', 'Georgia', 'serif'],
                        /* sans: body, buttons, nav, UI — Inter */
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    },
                    borderRadius: {
                        none: '0px',
                        DEFAULT: '0px',
                        sm: '0px',
                        md: '0px',
                        lg: '0px',
                        xl: '0px',
                        '2xl': '0px',
                        '3xl': '0px',
                        full: '0px'
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js for Fluid Interactions & State -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Animation, Typography & Mobile Stylesheet -->
    <style>
        /* ── Softer surfaces make long shopping sessions more comfortable ── */
        *, *::before, *::after {
            border-radius: 8px !important;
            -webkit-tap-highlight-color: transparent;
            box-sizing: border-box;
        }

        /* ── PREVENT HORIZONTAL OVERFLOW ON MOBILE ──────────────────── */
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }

        body {
            background-color: #F8F7F3;
            color: #000000;
            font-family: 'Inter', system-ui, sans-serif;
            margin: 0;
            padding: 0;
        }

        [dir="rtl"] body { font-family: Tahoma, Arial, sans-serif; }
        [dir="rtl"] .font-editorial, [dir="rtl"] .font-display { font-family: Tahoma, Arial, sans-serif !important; letter-spacing: 0 !important; }

        /* ── DISPLAY FONT — Abril Fatface (Bodoni/Didot aesthetic) ─── */
        /* Apply ONLY to: hero headline, banner headline, section titles */
        .font-display {
            font-family: 'Abril Fatface', Georgia, serif !important;
            font-weight: 400; /* single-weight font — no bold needed */
            letter-spacing: -0.01em; /* slight negative tracking suits Didone at large sizes */
        }

        /* ── FLUID FONT SIZES (clamp — smooth scaling between screens) ─ */
        .text-fluid-hero  { font-size: clamp(2.1rem, 8.5vw, 5.5rem); line-height: 1.05; }
        .text-fluid-title { font-size: clamp(1.6rem, 5.5vw, 3.5rem); line-height: 1.1;  }
        .text-fluid-sub   { font-size: clamp(0.9rem, 2.5vw, 1.15rem); line-height: 1.6;  }
        .text-fluid-label { font-size: clamp(0.6rem, 1.4vw, 0.75rem); }

        /* ── Calm, tactile navigation ─────────────────────────────── */
        .site-header {
            background: rgba(250, 249, 246, 0.94);
            border-color: rgba(25, 25, 25, 0.10);
            box-shadow: 0 7px 24px rgba(24, 24, 24, 0.055);
            animation: header-arrival .55s cubic-bezier(.16,1,.3,1) both;
        }
        .site-header a, .site-header button {
            transition: color .2s ease, background-color .2s ease, border-color .2s ease,
                        box-shadow .2s ease, transform .2s cubic-bezier(.2,.8,.2,1);
        }
        .header-search-shell {
            border: 1px solid #d9d6cf;
            border-radius: 14px !important;
            box-shadow: 0 1px 2px rgba(0,0,0,.025);
            overflow: hidden;
        }
        .header-search-shell:focus-within {
            border-color: #74706a;
            box-shadow: 0 0 0 4px rgba(32, 30, 27, .08), 0 4px 14px rgba(0,0,0,.045);
        }
        .header-search-button, .header-bag, .header-language {
            border-radius: 11px !important;
        }
        .header-search-button {
            margin: 4px;
            padding: .56rem .95rem;
            background: #1d1d1b;
        }
        .header-search-button:hover { background: #3a3935; transform: translateY(-1px); }
        .header-bag {
            background: #1d1d1b;
            border-color: #1d1d1b;
            box-shadow: 0 2px 7px rgba(0,0,0,.14);
        }
        .header-bag:hover { background: #34332f; box-shadow: 0 5px 13px rgba(0,0,0,.16); transform: translateY(-1px); }
        .header-language {
            border-color: #dedbd4;
            background: rgba(255,255,255,.55);
        }
        .header-language:hover { background: #fff; border-color: #aaa59c; }
        /* ── MOBILE DOCK: a calm, glassy shortcut bar for one-handed shopping ── */
        .mobile-dock {
            background: rgba(25, 25, 23, .88);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 20px !important;
            box-shadow: 0 14px 38px rgba(0,0,0,.26), inset 0 1px 0 rgba(255,255,255,.10);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            animation: mobile-dock-in .55s cubic-bezier(.16,1,.3,1) both;
        }
        .mobile-dock-link {
            min-height: 54px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: rgba(255,255,255,.62);
            font: 700 9px/1 Inter, system-ui, sans-serif;
            letter-spacing: .04em;
            text-decoration: none;
            transition: color .2s ease, transform .2s cubic-bezier(.2,.8,.2,1), background-color .2s ease;
        }
        .mobile-dock-link svg { width: 21px; height: 21px; }
        .mobile-dock button.mobile-dock-link { width: 100%; border: 0; background: transparent; cursor: pointer; }
        .mobile-dock-link:active { transform: scale(.9); }
        .mobile-dock-link.is-active { color: #fff; }
        .mobile-dock-link.is-active:not(.mobile-dock-bag) { background: rgba(255,255,255,.12); border-radius: 12px !important; }
        .mobile-dock-bag { color: #fff; }
        .mobile-dock-bag-icon { position: relative; display: grid; place-items: center; width: 34px; height: 28px; margin-top: -9px; border-radius: 12px !important; background: #f6f4ee; color: #171716; box-shadow: 0 5px 14px rgba(0,0,0,.28); }
        .mobile-dock-bag-icon svg { width: 19px; height: 19px; }
        .mobile-dock-bag-icon b { position: absolute; top: -5px; right: -6px; min-width: 15px; height: 15px; padding: 0 3px; display: grid; place-items: center; border-radius: 999px !important; background: #d6a82d; color: #161616; font: 800 8px/1 Inter, sans-serif; }
        .smart-search-backdrop { background: rgba(16,16,15,.42); backdrop-filter: blur(9px); -webkit-backdrop-filter: blur(9px); }
        .smart-search-panel { box-shadow: 0 24px 70px rgba(0,0,0,.24); }
        @keyframes mobile-dock-in { from { opacity: 0; transform: translateY(18px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes header-arrival {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }

        /* ── SCROLL REVEAL ANIMATION ────────────────────────────────── */
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1),
                        transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }
        .reveal-on-scroll.is-revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── LUXURY BUTTON — Filled Black ───────────────────────────── */
        .btn-luxury {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 2rem;
            background-color: #000000;
            color: #FFFFFF;
            border: 1.5px solid #000000;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-luxury:hover {
            background-color: #FFFFFF;
            color: #000000;
            box-shadow: 0 14px 36px rgba(0,0,0,0.12);
            transform: translateY(-2px) scale(1.01);
        }
        .btn-luxury:active { transform: scale(0.97); }

        /* ── LUXURY BUTTON — Outline ─────────────────────────────────── */
        .btn-luxury-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 2rem;
            background-color: transparent;
            color: #000000;
            border: 1.5px solid #000000;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-luxury-outline:hover {
            background-color: #000000;
            color: #FFFFFF;
            transform: translateY(-2px) scale(1.01);
        }
        .btn-luxury-outline:active { transform: scale(0.97); }

        /* ── IMAGES ALWAYS CONTAINED ────────────────────────────────── */
        img { max-width: 100%; height: auto; display: block; }

        /* ── TOUCH TARGETS (min 44x44) ───────────────────────────────── */
        a, button, [role="button"] { min-height: 44px; }

        /* ── PRODUCT CARD HOVER ─────────────────────────────────────── */
        .product-card {
            transition: box-shadow 0.35s cubic-bezier(0.16,1,0.3,1),
                        transform 0.35s cubic-bezier(0.16,1,0.3,1);
        }
        .product-card:hover {
            box-shadow: 0 14px 28px rgba(0,0,0,.10);
            transform: translateY(-4px);
        }
        /* Product media always keeps the same frame; source photos may vary in crop or distance. */
        .product-media-frame {
            background: #fff;
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }
        .product-media-frame img {
            width: 100%; height: 100%; object-fit: contain; object-position: center;
            transition: opacity .22s ease, transform .45s cubic-bezier(.16,1,.3,1);
        }
        /* Touch feedback is deliberately short: it feels responsive without animating every pixel. */
        @media (max-width: 1023px) and (prefers-reduced-motion: no-preference) {
            main > * { animation: mobile-page-enter .38s cubic-bezier(.16,1,.3,1) both; }
            .reveal-on-scroll.is-revealed { animation: mobile-card-settle .42s cubic-bezier(.16,1,.3,1) both; }
            .product-media-frame { transform: translateZ(0); }
            .product-media-frame img { backface-visibility: hidden; }
            .reveal-on-scroll:active, .product-card:active, .mobile-dock-link:active, .btn-luxury:active { transform: scale(.985); }
        }
        @keyframes mobile-page-enter { from { opacity: .01; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes mobile-card-settle {
            from { opacity: .01; transform: translateY(12px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── ALPINE CLOAK ───────────────────────────────────────────── */
        [x-cloak] { display: none !important; }

        /* ── SCROLLBAR ──────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #F5F5F0; }
        ::-webkit-scrollbar-thumb { background: #000000; }

        /* ── SEARCH OVERLAY (Full & Dropdown) ─────────────────────── */
        .search-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(245,245,240,0.97);
            backdrop-filter: blur(16px);
            display: flex; align-items: flex-start; justify-content: center;
            padding-top: 15vh;
            opacity: 0; pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .search-overlay.is-open {
            opacity: 1; pointer-events: all;
        }
        .search-overlay .search-inner {
            width: 100%; max-width: 650px;
            padding: 0 1.5rem;
        }
        .search-overlay input[type="search"] {
            width: 100%;
            font-family: 'Cinzel', Georgia, serif;
            font-size: clamp(1.4rem, 4.5vw, 2.2rem);
            font-weight: 700;
            background: transparent;
            border: none;
            border-bottom: 2px solid #000;
            padding: 0.5rem 0 0.75rem;
            outline: none;
            color: #000;
            text-transform: uppercase;
        }
        .search-overlay input[type="search"]::placeholder { color: rgba(0,0,0,0.25); }
        .search-results-list { margin-top: 1.5rem; max-height: 55vh; overflow-y: auto; }
        .search-result-item {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.75rem 0; border-bottom: 1px solid rgba(0,0,0,0.1);
            text-decoration: none; color: #000;
            transition: opacity 0.2s;
        }
        .search-result-item:hover { opacity: 0.6; }
        .search-result-img {
            width: 52px; height: 52px; object-fit: cover;
            border: 1px solid rgba(0,0,0,0.15); flex-shrink: 0;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#F5F5F0] text-black antialiased flex flex-col min-h-screen">

    <!-- Master Header Partials -->
    @include('partials.header')

    <!-- Main Content Injection -->
    <main class="flex-grow pb-24 lg:pb-0">
        @yield('content')
    </main>

    <!-- Master Footer Partials -->
    @include('partials.footer')

    @php
        $bottomCartCount = \App\Http\Controllers\CartController::cartCount();
        $isCatalogPage = request()->is('collections') || request()->is('collections/*');
    @endphp
    <!-- Fast mobile navigation: keeps the key actions within thumb reach. -->
    <nav class="mobile-dock lg:hidden fixed inset-x-3 bottom-3 z-50 grid grid-cols-5 gap-1 px-2 py-2" aria-label="Mobile navigation">
        <a href="{{ route('home') }}" class="mobile-dock-link {{ request()->routeIs('home') ? 'is-active' : '' }}" aria-label="{{ $isArabicStore ? 'الرئيسية' : 'Home' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10Z" stroke-linejoin="round" stroke-width="1.8"/></svg>
            <span>{{ $isArabicStore ? 'الرئيسية' : 'Home' }}</span>
        </a>
        <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="mobile-dock-link {{ $isCatalogPage ? 'is-active' : '' }}" aria-label="{{ $isArabicStore ? 'التصنيفات' : 'Collections' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" stroke-width="1.8"/><path d="M4 10h16M9 5v14" stroke-width="1.8"/></svg>
            <span>{{ $isArabicStore ? 'التصنيفات' : 'Collections' }}</span>
        </a>
        <button type="button" class="mobile-dock-link" onclick="window.dispatchEvent(new CustomEvent('open-smart-search'))" aria-label="{{ $isArabicStore ? 'بحث' : 'Search' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="10.8" cy="10.8" r="5.8" stroke-width="1.8"/><path d="m16 16 4.3 4.3" stroke-linecap="round" stroke-width="1.8"/></svg>
            <span>{{ $isArabicStore ? 'بحث' : 'Search' }}</span>
        </button>
        <a href="{{ route('cart.index') }}" class="mobile-dock-link mobile-dock-bag {{ request()->routeIs('cart.*') ? 'is-active' : '' }}" aria-label="{{ $isArabicStore ? 'السلة' : 'Bag' }}">
            <span class="mobile-dock-bag-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Zm4 1V6a3 3 0 0 1 6 0v3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>
                @if($bottomCartCount > 0)<b>{{ $bottomCartCount }}</b>@endif
            </span>
            <span>{{ $isArabicStore ? 'السلة' : 'Bag' }}</span>
        </a>
        <a href="{{ route('account') }}" class="mobile-dock-link {{ request()->routeIs('account*') ? 'is-active' : '' }}" aria-label="{{ $isArabicStore ? 'حسابي' : 'Account' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="3.5" stroke-width="1.8"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke-linecap="round" stroke-width="1.8"/></svg>
            <span>{{ $isArabicStore ? 'حسابي' : 'Account' }}</span>
        </a>
    </nav>

    <!-- Search lives in a focused, full-screen sheet on phones instead of taking permanent vertical space. -->
    <div x-data="smartSearch()" @open-smart-search.window="show()" x-show="open" x-cloak class="lg:hidden fixed inset-0 z-[70] smart-search-backdrop p-3 sm:p-6" @keydown.escape.window="close()">
        <div x-show="open" x-transition class="smart-search-panel bg-[#f8f7f3] h-full rounded-2xl p-5 flex flex-col" @click.outside="close()">
            <div class="flex items-center justify-between gap-4 mb-5">
                <div><p class="font-editorial font-bold text-[10px] uppercase tracking-[.2em] text-black/45">{{ $isArabicStore ? 'بحث ذكي' : 'Smart search' }}</p><h2 class="font-editorial font-black text-xl text-black">{{ $isArabicStore ? 'ماذا تريد؟' : 'Find your piece' }}</h2></div>
                <button type="button" @click="close()" class="w-11 h-11 border border-black/10 bg-white text-xl" aria-label="Close search">×</button>
            </div>
            <form @submit.prevent="goToResults()" class="flex gap-2 mb-4">
                <input x-ref="searchInput" x-model="query" @input="queueSearch()" type="search" autocomplete="off" class="min-w-0 flex-1 rounded-xl border border-black/15 bg-white px-4 text-sm focus:outline-none focus:ring-4 focus:ring-black/10" placeholder="{{ $isArabicStore ? 'اكتب اسمًا أو وصفًا حتى لو غير دقيق' : 'Try a name, material, or a rough description' }}">
                <button type="submit" class="min-w-[52px] bg-black text-white rounded-xl" aria-label="Search">→</button>
            </form>
            <p class="text-[11px] text-black/50 mb-4">{{ $isArabicStore ? 'يفهم الكلمات الناقصة وأخطاء الكتابة البسيطة.' : 'Handles missing spaces and small spelling mistakes.' }}</p>
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" @click="useSuggestion('{{ $isArabicStore ? 'جراب' : 'case' }}')" class="min-h-0 rounded-full border border-black/10 bg-white px-3 py-1.5 text-[10px] text-black/60">{{ $isArabicStore ? 'جرابات' : 'Cases' }}</button>
                <button type="button" @click="useSuggestion('{{ $isArabicStore ? 'محفظة' : 'wallet' }}')" class="min-h-0 rounded-full border border-black/10 bg-white px-3 py-1.5 text-[10px] text-black/60">{{ $isArabicStore ? 'محافظ' : 'Wallets' }}</button>
                <button type="button" @click="useSuggestion('{{ $isArabicStore ? 'اسود' : 'black' }}')" class="min-h-0 rounded-full border border-black/10 bg-white px-3 py-1.5 text-[10px] text-black/60">{{ $isArabicStore ? 'الأسود' : 'Black' }}</button>
            </div>
            <div class="flex-1 overflow-y-auto -mx-1 px-1">
                <template x-if="loading"><p class="py-8 text-center text-xs text-black/45">{{ $isArabicStore ? 'نبحث لك…' : 'Looking for it…' }}</p></template>
                <template x-if="!loading && query.trim().length >= 2 && results.length === 0"><p class="py-8 text-center text-xs text-black/45">{{ $isArabicStore ? 'لم نجد تطابقًا قريبًا بعد.' : 'No close match yet.' }}</p></template>
                <template x-for="item in results" :key="item.id"><a :href="item.url" class="flex items-center gap-3 bg-white border border-black/10 rounded-xl p-2.5 mb-2 transition-transform active:scale-[.98]"><img :src="item.image" :alt="item.title" class="w-14 h-14 rounded-lg object-cover bg-black/5"><span class="min-w-0 flex-1"><b class="block text-xs text-black truncate" x-text="item.title"></b><small class="block mt-1 text-[11px] text-black/55" x-text="item.price"></small></span><span class="text-black/45">→</span></a></template>
            </div>
        </div>
    </div>

    <!-- Global Scroll Observer for Smooth Scroll-Triggered Animations -->
    <script>
        function smartSearch() {
            return {
                open: false, query: '', results: [], loading: false, timer: null, controller: null, requestNumber: 0, cache: {},
                show() { this.open = true; this.$nextTick(() => this.$refs.searchInput.focus()); },
                close() { this.open = false; },
                queueSearch() {
                    clearTimeout(this.timer);
                    if (this.query.trim().length < 2) { this.results = []; this.loading = false; return; }
                    this.timer = setTimeout(() => this.search(), 220);
                },
                useSuggestion(value) { this.query = value; this.queueSearch(); this.$nextTick(() => this.$refs.searchInput.focus()); },
                async search() {
                    const query = this.query.trim();
                    const requestNumber = ++this.requestNumber;
                    if (this.cache[query]) { this.results = this.cache[query]; return; }
                    if (this.controller) this.controller.abort();
                    this.controller = new AbortController();
                    this.loading = true;
                    try {
                        const response = await fetch(`/collections/all?format=json&q=${encodeURIComponent(query)}`, { signal: this.controller.signal, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        const data = await response.json();
                        if (requestNumber === this.requestNumber) {
                            this.results = Array.isArray(data.products) ? data.products.slice(0, 8) : [];
                            this.cache[query] = this.results;
                        }
                    } catch (error) { if (error.name !== 'AbortError' && requestNumber === this.requestNumber) this.results = []; }
                    finally { if (requestNumber === this.requestNumber) this.loading = false; }
                },
                goToResults() { if (this.query.trim()) window.location.href = `/collections/all?q=${encodeURIComponent(this.query.trim())}`; }
            };
        }
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.15
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.reveal-on-scroll').forEach((el) => {
                observer.observe(el);
            });
        });
    </script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
    <script>
        // One tiny heartbeat each 45 seconds lets the admin see live activity without polling visitors heavily.
        (() => {
            const ping = () => fetch('{{ route('presence.ping') }}', {
                method: 'POST', credentials: 'same-origin', keepalive: true,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
            }).catch(() => {});
            setTimeout(ping, 4000);
            setInterval(ping, 45000);
        })();
    </script>
    @stack('scripts')
</body>
</html>
