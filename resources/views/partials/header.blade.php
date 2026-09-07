<!-- Announcement Top Bar (Synced with Admin Live Settings) -->
@php
    $navCollections = \App\Models\Collection::where('status', 'active')->orderBy('sort_order')->take(4)->get();
    $currentSlug = request()->route('slug') ?? '';
    // Store language is controlled by the administrator, not a per-visitor URL/session.
    $isAr = ($settings['storefront_lang'] ?? 'en') === 'ar';

    if (!function_exists('parseShortNavTitle')) {
        function parseShortNavTitle($title) {
            $t = trim($title);
            if (stripos($t, 'cardholder') !== false) return 'Cardholders';
            if (stripos($t, 'bifold') !== false) return 'Bifolds';
            if (stripos($t, 'money clip') !== false) return 'Money Clips';
            if (stripos($t, 'passport') !== false || stripos($t, 'travel') !== false) return 'Wallets';
            if (stripos($t, 'beanbag') !== false) return 'Beanbags';
            
            $words = explode(' ', $t);
            return count($words) > 1 && strlen($words[0]) <= 3 ? $words[0] . ' ' . $words[1] : $words[0];
        }
    }
@endphp

@if(Auth::check() && Auth::user()->isAdmin())
    <!-- Admin Quick-Jump Bar for Logged-In Administrators -->
    <div class="hidden md:flex bg-black text-amber-400 border-b border-amber-400/30 text-[10px] font-mono h-8 px-4 sm:px-8 items-center justify-between z-50 relative">
        <div class="flex items-center gap-2 truncate">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse shrink-0"></span>
            <span class="text-white/90 text-[11px] font-medium truncate">Admin: {{ Auth::user()->name }}</span>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center bg-amber-400 text-black px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider hover:bg-white transition-all shadow-sm">
                Admin Suite →
            </a>
            <a href="{{ route('admin.products.create') }}" class="text-white/80 hover:text-amber-400 text-[10px] uppercase font-mono px-1 py-0.5 transition-colors">
                + Product
            </a>
            <a href="{{ route('admin.content.index') }}" class="text-white/80 hover:text-amber-400 text-[10px] uppercase font-mono px-1 py-0.5 transition-colors">
                ✎ Edit Banner
            </a>
        </div>
    </div>
@endif

{{-- Dynamic Animated Ticker Bar --}}
<div class="hidden md:block bg-black text-white border-b border-white/10 overflow-hidden" style="height: 32px;">
    <style>
        @keyframes atl-ticker {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .atl-ticker-track {
            display: flex;
            width: max-content;
            animation: atl-ticker 52s linear infinite;
            will-change: transform;
        }
        .atl-ticker-track:hover { animation-play-state: paused; }
        .atl-ticker-item {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            padding: 0 2rem;
            font-family: 'Cinzel', Georgia, serif;
            font-weight: 700;
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            line-height: 32px;
            color: rgba(255,255,255,0.78);
        }
        .atl-ticker-sep {
            display: inline-block;
            width: 4px;
            height: 4px;
            background: rgba(255,255,255,0.35);
            margin: 0 1.5rem;
            transform: rotate(45deg);
            vertical-align: middle;
            flex-shrink: 0;
        }
    </style>
    <div class="atl-ticker-track" id="atl-ticker">
        @php
        $tickerItems = [
            'SPECIAL SERVICE & PREMIUM PRODUCTS CONDUCTED ACROSS EGYPT',
            'FULL-GRAIN ITALIAN TUSCAN LEATHER CRAFTSMANSHIP',
            'DELIVERY FROM 3 TO 5 BUSINESS DAYS — DOOR-TO-DOOR EXPRESS',
            'CASH ON DELIVERY · PAYMOB ENCRYPTED GATEWAY · VISA · VALU',
            '100% AUTHENTIC LUXURY ACCESSORIES · ORIGIN: CAIRO, EGYPT',
            'SECURE PACKAGING — EVERY ORDER HAND-WRAPPED IN ATELIER BOX',
            'TRACK YOUR ORDER IN REAL-TIME — DIRECT SMS & WHATSAPP UPDATES',
        ];
        @endphp
        @foreach($tickerItems as $t)
        <span class="atl-ticker-item">{{ $t }}<span class="atl-ticker-sep"></span></span>
        @endforeach
        {{-- Duplicate for seamless loop --}}
        @foreach($tickerItems as $t)
        <span class="atl-ticker-item">{{ $t }}<span class="atl-ticker-sep"></span></span>
        @endforeach
    </div>
</div>

<!-- Main Sticky Luxury Navigation Header with Amazon-Style Prominent Search -->
<header 
    x-data="headerSearchComponent()"
    class="site-header sticky top-0 z-40 backdrop-blur-xl border-b transition-all duration-300"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">
        <!-- Main Top Row -->
        <div class="flex items-center justify-between h-16 sm:h-20 gap-4">
            
            <!-- Left: Mobile Menu Button & Brand Wordmark -->
            <div class="flex items-center gap-3 shrink-0">
                <!-- Mobile Menu Toggle Button -->
                <button 
                    type="button" 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden p-2 text-black/80 hover:text-black hover:bg-black/5 active:scale-95 flex items-center justify-center min-h-[44px] min-w-[44px]"
                    aria-label="Toggle navigation drawer"
                >
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6 stroke-[2.4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6 stroke-[2.4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Brand Wordmark & Tagline -->
                <a href="{{ route('home') }}" class="group flex flex-col items-start sm:items-center">
                    <span class="font-editorial font-black tracking-normal text-2xl sm:text-3xl text-black uppercase leading-none transition-transform group-hover:scale-[1.02]">
                        {{ $settings['store_name'] ?? ($settings['storeName'] ?? 'ATELIER') }}
                    </span>
                    <span class="font-editorial font-bold text-[8px] sm:text-[9px] tracking-[0.35em] text-black/55 uppercase mt-0.5 whitespace-nowrap">
                        STUDIO EGYPT · 2026
                    </span>
                </a>
            </div>

            <!-- Center: Prominent Amazon-Style Live Search Bar (Desktop) -->
            <div class="hidden md:flex flex-1 max-w-xl mx-4 relative">
                <form action="{{ route('collections.show', ['slug' => 'all']) }}" method="GET" class="w-full relative" @submit="submitSearch">
                    <div class="header-search-shell flex items-center bg-white transition-all">
                        <div class="pl-3 pr-2 text-black/50 flex items-center">
                            <svg class="w-4 h-4 stroke-[2.4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="q" 
                            x-model="searchQuery" 
                            @input.debounce.250ms="performLiveSearch()"
                            @focus="searchFocused = true"
                            @click.away="searchFocused = false"
                            placeholder="{{ $isAr ? 'ابحث عن محافظ، حوامل بطاقات، وإكسسوارات...' : 'Search wallets, cardholders, and accessories...' }}" 
                            class="w-full py-2.5 px-2 text-xs font-sans text-black placeholder:text-black/40 focus:outline-none bg-transparent"
                            autocomplete="off"
                        >
                        <button type="submit" class="header-search-button text-white font-editorial font-bold text-[10px] uppercase tracking-wider shrink-0">
                            {{ $isAr ? 'بحث' : 'Search' }}
                        </button>
                    </div>

                    <!-- Live Dropdown Results -->
                    <div 
                        x-show="searchFocused && liveResults.length > 0" 
                        x-transition 
                        x-cloak
                        class="absolute left-0 right-0 top-full mt-1.5 bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] z-50 max-h-80 overflow-y-auto divide-y divide-black/10"
                    >
                        <template x-for="item in liveResults" :key="item.id">
                            <a :href="item.url" class="flex items-center gap-3 p-3 hover:bg-[#F5F5F0] transition-colors">
                                <img :src="item.image" :alt="item.title" class="w-12 h-12 object-cover border border-black/15 shrink-0 bg-[#F5F5F0]">
                                <div class="flex-1 min-w-0">
                                    <p class="font-editorial font-bold text-xs uppercase tracking-normal text-black truncate" x-text="item.title"></p>
                                    <p class="font-sans font-semibold text-[11px] text-black/70 mt-0.5" x-text="item.price"></p>
                                </div>
                                <span class="text-xs font-editorial text-black/40">View →</span>
                            </a>
                        </template>
                        <div class="p-2 bg-[#FAFAFA] text-center border-t border-black/10">
                            <a :href="'/collections/all?q=' + encodeURIComponent(searchQuery)" class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black hover:underline">
                                View all matching results →
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right: Nav Links + Account + Bag (Clean & Complete) -->
            <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                <!-- Desktop Nav Links -->
                <nav class="hidden xl:flex items-center gap-5">
                    @php
                        $isHome = request()->routeIs('home');
                        $isAllCatalog = request()->is('collections/all') || request()->is('collections');
                        $isTrack = request()->routeIs('track.order');
                        $navLinkClass = 'relative font-editorial font-bold text-[11px] uppercase tracking-[0.14em] transition-colors py-1 whitespace-nowrap group';
                    @endphp

                    <a href="{{ route('home') }}" class="{{ $navLinkClass }} {{ $isHome ? 'text-black' : 'text-black/60 hover:text-black' }}">
                        <span>{{ $isAr ? 'الرئيسية' : 'Home' }}</span>
                        <span class="absolute bottom-0 left-0 w-full h-[2px] bg-black transition-transform duration-300 {{ $isHome ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"></span>
                    </a>

                    <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="{{ $navLinkClass }} {{ $isAllCatalog ? 'text-black' : 'text-black/60 hover:text-black' }}">
                        <span>{{ $isAr ? 'المنتجات' : 'Catalog' }}</span>
                        <span class="absolute bottom-0 left-0 w-full h-[2px] bg-black transition-transform duration-300 {{ $isAllCatalog ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"></span>
                    </a>

                    <a href="{{ route('track.order') }}" class="{{ $navLinkClass }} {{ $isTrack ? 'text-black' : 'text-black/60 hover:text-black' }}">
                        <span>{{ $isAr ? 'تتبع الطلب' : 'Track' }}</span>
                        <span class="absolute bottom-0 left-0 w-full h-[2px] bg-black transition-transform duration-300 {{ $isTrack ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"></span>
                    </a>
                </nav>

                <!-- Client Account -->
                <a 
                    href="{{ route('account') }}" 
                    class="hidden lg:flex p-2 items-center gap-1.5 text-[11px] font-editorial font-bold uppercase tracking-wider text-black hover:opacity-75 transition-opacity shrink-0"
                    title="{{ $isAr ? 'حسابي' : 'Client Account' }}"
                >
                    <svg class="w-4 h-4 text-black stroke-[2.4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="hidden sm:inline">{{ $isAr ? 'حسابي' : 'Account' }}</span>
                </a>

                <!-- Luxury Shopping Bag Button -->
                @php $cartCount = \App\Http\Controllers\CartController::cartCount(); @endphp
                <a 
                    href="{{ route('cart.index') }}" 
                    class="header-bag group relative hidden lg:flex items-center gap-2 text-white px-3 sm:px-4 py-2 text-[11px] font-editorial font-bold uppercase tracking-wider transition-all duration-200 border active:scale-[.98] cursor-pointer shrink-0"
                    title="{{ $isAr ? 'السلة وإتمام الطلب' : 'Shopping Bag & Checkout' }}"
                >
                    <svg class="w-4 h-4 text-white stroke-[2.4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span class="tracking-widest">{{ $isAr ? 'السلة' : 'BAG' }}</span>
                    <span class="bg-white text-black text-[10px] font-mono font-black px-1.5 py-0.5 border border-black group-hover:bg-amber-300 transition-colors leading-none">
                        {{ $cartCount }}
                    </span>
                </a>
            </div>

        </div>

    </div>

    <!-- Mobile Slide-Down Drawer (Clean & Simple) -->
    <div 
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4"
        x-cloak
        class="lg:hidden bg-[#F5F5F0] border-b-2 border-black w-full px-6 py-8 space-y-6 shadow-xl max-h-[80vh] overflow-y-auto"
    >
        <div class="flex flex-col space-y-3">
            <a href="{{ route('home') }}" class="font-editorial font-black text-lg uppercase tracking-normal {{ request()->routeIs('home') ? 'text-black border-l-4 border-black pl-3' : 'text-black/70 hover:text-black pl-4' }} py-2">
                {{ $isAr ? 'الرئيسية' : 'Home' }}
            </a>
            <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="font-editorial font-black text-lg uppercase tracking-normal {{ request()->is('collections/all') ? 'text-black border-l-4 border-black pl-3' : 'text-black/70 hover:text-black pl-4' }} py-2">
                {{ $isAr ? 'كل المنتجات' : 'All products' }}
            </a>
            
            @php $allMobileCols = \App\Models\Collection::where('status', 'active')->orderBy('sort_order')->get(); @endphp
            @foreach($allMobileCols as $mCol)
                @php $isMActive = request()->is('collections/' . $mCol->slug); @endphp
                <a href="{{ route('collections.show', ['slug' => $mCol->slug]) }}" class="font-editorial font-black text-lg uppercase tracking-normal {{ $isMActive ? 'text-black border-l-4 border-black pl-3' : 'text-black/70 hover:text-black pl-4' }} py-2">
                    {{ $mCol->title }}
                </a>
            @endforeach

            <a href="{{ route('track.order') }}" class="font-editorial font-black text-lg uppercase tracking-normal {{ request()->routeIs('track.order') ? 'text-black border-l-4 border-black pl-3' : 'text-black/70 hover:text-black pl-4' }} py-2">
                {{ $isAr ? 'تتبع الطلب' : 'Track order' }}
            </a>

            @if(Auth::check() && Auth::user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="font-editorial font-black text-lg uppercase tracking-normal text-amber-700 hover:text-black pl-4 py-2 border-t border-black/20 mt-2">
                    ⚡ Admin Dashboard
                </a>
            @endif
        </div>

        <div class="pt-6 border-t border-black/10 flex flex-col gap-3">
            <a href="{{ route('account') }}" class="w-full text-center py-3.5 border border-black font-editorial font-bold text-xs uppercase tracking-wider text-black hover:bg-black hover:text-white transition-all min-h-[44px] flex items-center justify-center">
                {{ $isAr ? 'حسابي' : 'My account' }}
            </a>
            <a href="{{ route('cart.index') }}" class="w-full text-center py-3.5 bg-black text-white font-editorial font-bold text-xs uppercase tracking-wider border border-black hover:bg-white hover:text-black transition-all min-h-[44px] flex items-center justify-center">
                {{ $isAr ? 'السلة وإتمام الطلب ←' : 'View cart & checkout →' }}
            </a>
        </div>
    </div>
</header>

<script>
function headerSearchComponent() {
    return {
        mobileMenuOpen: false,
        searchQuery: '',
        searchFocused: false,
        liveResults: [],
        async performLiveSearch() {
            if (this.searchQuery.trim().length < 2) {
                this.liveResults = [];
                return;
            }
            try {
                const res = await fetch(`/collections/all?q=${encodeURIComponent(this.searchQuery.trim())}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.liveResults = data && data.products ? data.products.slice(0, 6) : [];
            } catch (e) {
                this.liveResults = [];
            }
        },
        submitSearch(e) {
            if (!this.searchQuery.trim()) {
                e.preventDefault();
            }
        }
    }
}
</script>
