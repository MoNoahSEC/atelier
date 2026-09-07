<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Atelier Administrative Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f0; }
        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid #000;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        [x-cloak] { display: none !important; }
        .ql-toolbar.ql-snow { border: 2px solid #000 !important; background: #fafafa; }
        .ql-container.ql-snow { border: 2px solid #000 !important; border-top: 0 !important; background: #fff; font-size: 13px; min-height: 140px; }
    </style>
</head>
<body class="min-h-screen bg-[#F5F5F0] text-black antialiased flex flex-col" x-data="{ sidebarOpen: false }">

    <!-- Top Navigation Bar -->
    <header 
        x-data="{
            searchQuery: '',
            searchResults: { products: [], orders: [], customers: [] },
            searchOpen: false,
            searchLoading: false,
            searchTimeout: null,
            init() {
                window.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        this.$refs.globalSearchInput.focus();
                        this.searchOpen = true;
                    }
                    if (e.key === 'Escape') {
                        this.searchOpen = false;
                    }
                });
            },
            onSearchInput() {
                clearTimeout(this.searchTimeout);
                if (this.searchQuery.trim().length < 2) {
                    this.searchResults = { products: [], orders: [], customers: [] };
                    this.searchOpen = false;
                    return;
                }
                this.searchLoading = true;
                this.searchOpen = true;
                this.searchTimeout = setTimeout(() => {
                    fetch(`/admin/search?q=${encodeURIComponent(this.searchQuery.trim())}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.searchResults = data;
                        this.searchLoading = false;
                    })
                    .catch(() => { this.searchLoading = false; });
                }, 280);
            }
        }"
        class="bg-white border-b-2 border-black sticky top-0 z-40 px-4 sm:px-8 py-3 flex items-center justify-between shadow-sm gap-4"
    >
        <!-- Left: Logo & Role -->
        <div class="flex items-center gap-3 shrink-0">
            <!-- Mobile Menu Toggle -->
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 border border-black text-black hover:bg-black hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center gap-2.5">
                <span class="bg-black text-white px-2 py-0.5 text-[10px] font-mono font-bold tracking-widest uppercase">
                    {{ strtoupper(auth()->user()->admin_role ?? 'ADMIN') }}
                </span>
                <a href="{{ route('admin.dashboard') }}" class="font-black text-lg tracking-tight uppercase hover:opacity-80 transition-opacity hidden sm:inline">
                    {{ \App\Models\Setting::get('store_name', 'ATELIER') }} Studio
                </a>
            </div>
        </div>

        <!-- Center: Global Quick Search (Ctrl+K) -->
        <div class="flex-1 max-w-lg relative" @click.away="searchOpen = false">
            <div class="relative flex items-center">
                <span class="absolute left-3 text-gray-400 text-xs">🔍</span>
                <input 
                    x-ref="globalSearchInput"
                    type="text" 
                    x-model="searchQuery" 
                    @input="onSearchInput()"
                    @focus="if(searchQuery.length >= 2) searchOpen = true"
                    placeholder="Search products, orders, customers... (Ctrl+K)" 
                    class="w-full border-2 border-black pl-8 pr-16 py-1.5 text-xs bg-[#FBFBFA] focus:bg-white focus:outline-none placeholder-gray-400 font-medium"
                >
                <kbd class="absolute right-2.5 hidden sm:inline-block border border-gray-300 bg-gray-100 text-gray-500 text-[10px] px-1.5 py-0.5 font-mono rounded">Ctrl+K</kbd>
            </div>

            <!-- Search Dropdown Results -->
            <div 
                x-show="searchOpen && (searchResults.products.length > 0 || searchResults.orders.length > 0 || searchResults.customers.length > 0 || searchLoading)" 
                x-cloak 
                class="absolute left-0 right-0 top-full mt-1.5 bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] max-h-96 overflow-y-auto z-50 divide-y divide-gray-100"
            >
                <div x-show="searchLoading" class="p-4 text-center text-xs text-gray-400 font-mono">
                    Searching catalog & records...
                </div>

                <!-- Products Group -->
                <template x-if="searchResults.products && searchResults.products.length > 0">
                    <div class="p-2">
                        <span class="block text-[9px] font-mono font-bold uppercase tracking-widest text-gray-400 px-2 py-1">🛍 Products</span>
                        <template x-for="p in searchResults.products" :key="'p-' + p.id">
                            <a :href="p.url" class="flex items-center justify-between p-2 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-2.5 truncate">
                                    <template x-if="p.img">
                                        <img :src="p.img" alt="" class="w-8 h-8 object-cover border border-black shrink-0">
                                    </template>
                                    <div class="truncate">
                                        <span class="block text-xs font-bold text-black truncate" x-text="p.label"></span>
                                        <span class="block text-[10px] text-gray-500 font-mono truncate" x-text="p.sub"></span>
                                    </div>
                                </div>
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 border shrink-0 ml-2" :class="p.badge === 'active' ? 'border-green-600 text-green-700 bg-green-50' : 'border-gray-400 text-gray-600'" x-text="p.badge"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <!-- Orders Group -->
                <template x-if="searchResults.orders && searchResults.orders.length > 0">
                    <div class="p-2">
                        <span class="block text-[9px] font-mono font-bold uppercase tracking-widest text-gray-400 px-2 py-1">🚚 Orders</span>
                        <template x-for="o in searchResults.orders" :key="'o-' + o.id">
                            <a :href="o.url" class="flex items-center justify-between p-2 hover:bg-gray-50 transition-colors">
                                <div>
                                    <span class="block text-xs font-bold text-black" x-text="o.label"></span>
                                    <span class="block text-[10px] text-gray-500 font-mono" x-text="o.sub"></span>
                                </div>
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 border border-black bg-gray-100 shrink-0 ml-2" x-text="o.badge"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <!-- Customers Group -->
                <template x-if="searchResults.customers && searchResults.customers.length > 0">
                    <div class="p-2">
                        <span class="block text-[9px] font-mono font-bold uppercase tracking-widest text-gray-400 px-2 py-1">👥 Customers</span>
                        <template x-for="c in searchResults.customers" :key="'c-' + c.id">
                            <a :href="c.url" class="flex items-center justify-between p-2 hover:bg-gray-50 transition-colors">
                                <div>
                                    <span class="block text-xs font-bold text-black" x-text="c.label"></span>
                                    <span class="block text-[10px] text-gray-500 font-mono" x-text="c.sub"></span>
                                </div>
                                <span class="text-[9px] font-mono text-gray-400">Profile →</span>
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Right: Indicators & Logout -->
        <div class="flex items-center gap-3 sm:gap-4 shrink-0">
            <!-- Maintenance Mode status indicator -->
            @php $isMaint = in_array((string)\App\Models\Setting::get('maintenance_mode', '0'), ['1', 'true', 'on'], true); @endphp
            @if($isMaint)
                <span class="bg-amber-400 text-black border border-black text-[9px] font-bold uppercase px-2 py-1 hidden md:flex items-center gap-1 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                    <span class="animate-ping w-1.5 h-1.5 bg-black rounded-full"></span>
                    <span>Maintenance Active</span>
                </span>
            @endif

            <!-- Unread notifications icon -->
            @php $globalUnread = \App\Models\AdminNotification::where('is_read', false)->count(); @endphp
            <a href="{{ route('admin.notifications.index') }}" class="relative p-2 text-gray-700 hover:text-black hover:bg-gray-100 transition-colors border border-transparent hover:border-black" title="System Notifications">
                <span class="text-base">🔔</span>
                @if($globalUnread > 0)
                    <span class="absolute top-1 right-1 bg-red-600 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center font-mono">
                        {{ $globalUnread }}
                    </span>
                @endif
            </a>

            <!-- View Storefront -->
            <a href="{{ route('home') }}" target="_blank" class="hidden md:inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-black border border-black px-3 py-1.5 bg-white hover:bg-gray-50 transition-colors">
                <span>Storefront</span>
                <span>↗</span>
            </a>

            <!-- Admin Profile & Logout -->
            <div class="flex items-center gap-2 pl-2 border-l-2 border-black/10">
                <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-black text-white hover:bg-red-600 border border-black px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition-colors" title="Sign out securely">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- App Body: Sidebar + Main Content -->
    <div class="flex-1 flex min-h-[calc(100vh-61px)]">
        
        <!-- Sidebar Navigation -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:sticky top-[61px] left-0 h-[calc(100vh-61px)] w-64 bg-white border-r-2 border-black z-30 flex flex-col justify-between transition-transform duration-200 ease-in-out overflow-y-auto"
        >
            <div class="p-4 space-y-6">
                <!-- Group 1: Core Commerce -->
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2 px-2">Store Overview</span>
                    <nav class="space-y-1">
                        <a 
                            href="{{ route('admin.dashboard') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.dashboard') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">📊</span>
                            <span>Dashboard</span>
                        </a>
                        <a 
                            href="{{ route('admin.analytics.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.analytics.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">📈</span>
                            <span>Analytics & Traffic</span>
                        </a>
                    </nav>
                </div>

                <!-- Group 2: Catalog & Stock -->
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2 px-2">Catalog & Stock</span>
                    <nav class="space-y-1">
                        <a 
                            href="{{ route('admin.products.index') }}" 
                            class="flex items-center justify-between px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.products.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-sm">🛍</span>
                                <span>Products</span>
                            </div>
                            <span class="text-[10px] font-mono opacity-70">{{ \App\Models\Product::count() }}</span>
                        </a>
                        <a 
                            href="{{ route('admin.inventory.index') }}" 
                            class="flex items-center justify-between px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.inventory.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-sm">📦</span>
                                <span>Inventory & Stock</span>
                            </div>
                        </a>
                        <a 
                            href="{{ route('admin.collections.index') }}" 
                            class="flex items-center justify-between px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.collections.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-sm">📁</span>
                                <span>Collections</span>
                            </div>
                            <span class="text-[10px] font-mono opacity-70">{{ \App\Models\Collection::count() }}</span>
                        </a>
                        <a 
                            href="{{ route('admin.coupons.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.coupons.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">🎟</span>
                            <span>Coupons & Offers</span>
                        </a>
                    </nav>
                </div>

                <!-- Group 3: Sales & Customers -->
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2 px-2">Sales & Operations</span>
                    <nav class="space-y-1">
                        <a 
                            href="{{ route('admin.orders.index') }}" 
                            class="flex items-center justify-between px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.orders.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-sm">🚚</span>
                                <span>Orders</span>
                            </div>
                            @php $pendingCnt = \App\Models\Order::where('shipping_status', 'pending')->count(); @endphp
                            @if($pendingCnt > 0)
                                <span class="bg-amber-400 text-black text-[9px] px-1.5 py-0.2 font-bold font-mono">{{ $pendingCnt }} new</span>
                            @endif
                        </a>
                        <a 
                            href="{{ route('admin.abandoned-carts.index') }}" 
                            class="flex items-center justify-between px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.abandoned-carts.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-sm">🛒</span>
                                <span>Abandoned Carts</span>
                            </div>
                            @php $abandonedCnt = \App\Models\AbandonedCart::whereNull('followed_up_at')->count(); @endphp
                            @if($abandonedCnt > 0)
                                <span class="bg-red-500 text-white text-[9px] px-1.5 py-0.2 font-bold font-mono">{{ $abandonedCnt }}</span>
                            @endif
                        </a>
                        <a 
                            href="{{ route('admin.customers.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.customers.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">👥</span>
                            <span>Customers</span>
                        </a>
                        <a 
                            href="{{ route('admin.reviews.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.reviews.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">⭐</span>
                            <span>Product Reviews</span>
                        </a>
                    </nav>
                </div>

                <!-- Group 4: Storefront & Configuration -->
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2 px-2">Store Configuration</span>
                    <nav class="space-y-1">
                        <a 
                            href="{{ route('admin.telegram.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.telegram.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">⚡</span>
                            <span>Telegram Alerts</span>
                        </a>
                        <a 
                            href="{{ route('admin.whatsapp.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.whatsapp.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">💬</span>
                            <span>WhatsApp Client</span>
                        </a>
                        <a 
                            href="{{ route('admin.surveys.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.surveys.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">📊</span>
                            <span>Surveys & Polls</span>
                        </a>
                        <a 
                            href="{{ route('admin.content.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.content.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">📝</span>
                            <span>Content & Banners</span>
                        </a>
                        <a 
                            href="{{ route('admin.shipping.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.shipping.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">🚚</span>
                            <span>Shipping & Tax</span>
                        </a>
                        @if(auth()->user()->isSuperAdmin())
                            <a 
                                href="{{ route('admin.team.index') }}" 
                                class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.team.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                            >
                                <span class="text-sm">🔑</span>
                                <span>Team & Roles</span>
                            </a>
                        @endif
                        <a 
                            href="{{ route('admin.notifications.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.notifications.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">🔔</span>
                            <span>Notifications</span>
                        </a>
                        <a 
                            href="{{ route('admin.audit-log.index') }}" 
                            class="flex items-center gap-3 px-3 py-2 text-xs font-bold uppercase tracking-wider border {{ request()->routeIs('admin.audit-log.*') ? 'bg-black text-white border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]' : 'text-gray-800 border-transparent hover:border-black hover:bg-gray-50' }} transition-all"
                        >
                            <span class="text-sm">🛡️</span>
                            <span>Audit Log</span>
                        </a>
                    </nav>
                </div>

                <!-- Quick Editor fallback -->
                <div class="pt-2 border-t border-gray-200">
                    <a 
                        href="{{ route('admin.quick-edit') }}" 
                        class="flex items-center gap-2 px-2 py-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 hover:text-black transition-colors"
                    >
                        <span>⚡</span>
                        <span>Single-Page Quick Editor</span>
                    </a>
                </div>
            </div>

            <!-- Footer note & Live Store Link -->
            <div class="p-4 bg-gray-50 border-t border-black space-y-2">
                <a 
                    href="{{ route('home') }}" 
                    target="_blank" 
                    class="w-full flex items-center justify-center gap-2 bg-black text-white hover:bg-gray-800 py-2.5 px-3 text-xs font-bold uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-transform active:translate-y-0.5"
                >
                    <span>🛍️</span>
                    <span>View Live Storefront ↗</span>
                </a>
                <div class="text-[10px] font-mono text-gray-500 text-center">
                    <span>ROLE: {{ strtoupper(auth()->user()->admin_role ?? 'ADMIN') }}</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 sm:p-8 lg:p-10 max-w-7xl w-full mx-auto">
            
            <!-- Global Flash Messages -->
            @if(session('success'))
                <div class="bg-black text-white p-4 mb-6 text-xs font-bold uppercase tracking-wider border-l-4 border-green-500 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-green-400 font-bold">✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-600 text-white p-4 mb-6 text-xs font-bold uppercase tracking-wider border-l-4 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span>⚠️</span>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 text-red-900 border-2 border-red-600 p-4 mb-6 text-xs font-bold shadow-[4px_4px_0px_0px_rgba(220,38,38,0.3)]">
                    <p class="mb-1 uppercase tracking-wider">Please fix the following issues:</p>
                    <ul class="list-disc list-inside space-y-0.5 font-normal">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Background overlay for mobile sidebar -->
    <div 
        x-show="sidebarOpen" 
        @click="sidebarOpen = false"
        x-cloak
        class="fixed inset-0 bg-black/50 z-20 lg:hidden"
    ></div>

    @stack('scripts')
</body>
</html>
