@extends('layouts.app')

@section('title', 'Client Account — ' . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
@php $isArAccount = ($settings['storefront_lang'] ?? 'en') === 'ar'; @endphp
<div class="bg-[#F5F5F0] min-h-screen py-6 sm:py-10 lg:py-16" x-data="{ activeTab: 'orders', showAddAddress: false, editingAddressId: null }">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        
        @if(session('success'))
            <div class="bg-black text-white p-4 text-center text-xs font-editorial uppercase tracking-wider mb-8">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 p-4 text-xs mb-8">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @auth
            <!-- Logged In Dashboard -->
            <div class="border border-black/10 bg-white/85 backdrop-blur-xl p-4 sm:p-8 shadow-[0_18px_48px_rgba(0,0,0,.10)]">
                <!-- Header -->
                <div class="border-b border-black/10 pb-5 mb-5 flex flex-col sm:flex-row justify-between sm:items-end gap-4">
                    <div>
                        <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.2em] text-black/50 block mb-1">
                            {{ $isArAccount ? 'حسابك' : 'Your account' }}
                        </span>
                        <h1 class="font-editorial font-black text-xl sm:text-3xl uppercase tracking-normal text-black">
                            {{ auth()->user()->name }}
                        </h1>
                        <p class="text-xs text-black/55 mt-1">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ url('/admin/quick-edit') }}" class="text-xs font-editorial font-bold uppercase tracking-wider bg-black text-white px-3 py-1.5 hover:bg-neutral-800">
                                Admin Suite →
                            </a>
                        @endif
                        <form action="{{ route('client.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs font-editorial font-bold uppercase tracking-wider border-b border-black pb-0.5 hover:text-black/60">
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="grid grid-cols-3 gap-1 p-1.5 bg-black/[.04] border border-black/10 rounded-xl mb-6">
                    <button 
                        @click="activeTab = 'orders'" 
                        :class="activeTab === 'orders' ? 'bg-white shadow-sm text-black' : 'text-black/55 hover:text-black'"
                        class="min-h-[46px] px-2 text-[10px] sm:text-xs font-editorial font-bold uppercase tracking-wide rounded-lg transition-colors"
                    >
                        {{ $isArAccount ? 'الطلبات' : 'Orders' }} ({{ $orders->count() }})
                    </button>
                    <button 
                        @click="activeTab = 'addresses'" 
                        :class="activeTab === 'addresses' ? 'bg-white shadow-sm text-black' : 'text-black/55 hover:text-black'"
                        class="min-h-[46px] px-2 text-[10px] sm:text-xs font-editorial font-bold uppercase tracking-wide rounded-lg transition-colors"
                    >
                        {{ $isArAccount ? 'العناوين' : 'Addresses' }} ({{ $addresses->count() }})
                    </button>
                    <button 
                        @click="activeTab = 'details'" 
                        :class="activeTab === 'details' ? 'bg-white shadow-sm text-black' : 'text-black/55 hover:text-black'"
                        class="min-h-[46px] px-2 text-[10px] sm:text-xs font-editorial font-bold uppercase tracking-wide rounded-lg transition-colors"
                    >
                        {{ $isArAccount ? 'الملف الشخصي' : 'Profile' }}
                    </button>
                </div>

                <!-- Tab 1: Order History -->
                <div x-show="activeTab === 'orders'" class="space-y-6">
                    @if($orders->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($orders as $order)
                                <div class="border-2 border-black/10 p-5 hover:border-black transition-colors bg-[#FAFAFA]">
                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center pb-4 border-b border-black/10 gap-2">
                                        <div>
                                            <span class="font-editorial font-bold text-sm text-black">Order #{{ $order->order_number }}</span>
                                            <span class="text-xs text-black/60 block">{{ $order->created_at->format('M d, Y — h:i A') }}</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="px-2.5 py-0.5 text-[10px] font-editorial font-bold uppercase tracking-wider {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ str_replace('_', ' ', $order->payment_status) }}
                                            </span>
                                            <span class="font-editorial font-bold text-sm text-black">
                                                {{ number_format($order->total_amount_minor / 100, 2) }} {{ $order->currency }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($order->items->isNotEmpty())
                                        <div class="pt-3 space-y-2">
                                            @foreach($order->items as $item)
                                                <div class="flex items-center justify-between gap-3 text-xs text-black/80 font-sans">
                                                    <span class="flex items-center gap-2 min-w-0">
                                                        @if(!empty($item->metadata_json['image_url']))<img src="{{ $item->metadata_json['image_url'] }}" alt="" class="w-9 h-9 object-contain bg-white border border-black/10 shrink-0" loading="lazy" decoding="async">@endif
                                                        <span class="min-w-0"><b class="block truncate">{{ $item->quantity }}x {{ $item->product_title }}</b>@if(!empty($item->metadata_json['variant_title']))<small class="mt-0.5 inline-flex items-center gap-1 text-black/55">@if(!empty($item->metadata_json['color_hex']))<i class="w-2 h-2 rounded-full border border-black/20" style="background: {{ $item->metadata_json['color_hex'] }}"></i>@endif{{ $item->metadata_json['variant_title'] }}</small>@endif</span>
                                                    </span>
                                                    <span>{{ number_format($item->total_price_minor / 100, 2) }} {{ $order->currency }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 bg-neutral-50 border border-dashed border-black/20">
                            <p class="font-editorial text-sm uppercase tracking-wider text-black/60 mb-4">You have no recent orders in the archive.</p>
                            <a href="{{ route('home') }}" class="btn-luxury inline-block px-6 py-3 text-xs tracking-wider">
                                Explore Boutique →
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Tab 2: My Addresses (Address Book) -->
                <div x-cloak x-show="activeTab === 'addresses'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="font-editorial font-bold text-base uppercase">Saved Delivery Addresses</h2>
                            <p class="text-xs text-black/60">Manage your shipping destinations for fast one-click checkout.</p>
                        </div>
                        <button 
                            @click="showAddAddress = !showAddAddress; if(showAddAddress) { setTimeout(() => window.dispatchEvent(new CustomEvent('map-refresh-account-map')), 150); }" 
                            class="bg-black text-white px-4 py-2 text-xs font-editorial font-bold uppercase tracking-wider hover:bg-neutral-800"
                        >
                            <span x-show="!showAddAddress">+ Add New Address</span>
                            <span x-show="showAddAddress">Cancel</span>
                        </button>
                    </div>

                    <!-- Add Address Form -->
                    <div x-show="showAddAddress" class="border-2 border-black p-6 bg-[#FAFAFA] transition-all">
                        <h3 class="font-editorial font-bold text-sm uppercase tracking-wider mb-4">Add New Delivery Address</h3>
                        <form action="{{ route('addresses.store') }}" method="POST" class="space-y-4">
                            @csrf

                            <!-- Map Picker -->
                            <div class="pb-2">
                                @include('partials.location-map', [
                                    'mapId'         => 'account-map',
                                    'latInputId'    => 'account-lat',
                                    'lngInputId'    => 'account-lng',
                                    'cityInputId'   => 'account-city',
                                    'streetInputId' => 'account-street',
                                ])
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">Address Label</label>
                                    <input type="text" name="label" required placeholder="e.g. Home, Office, Villa" class="w-full border border-black p-3 text-xs bg-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">Full Recipient Name</label>
                                    <input type="text" name="full_name" required value="{{ auth()->user()->name }}" placeholder="Recipient Name" class="w-full border border-black p-3 text-xs bg-white focus:outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">Phone Number (WhatsApp)</label>
                                    <input type="tel" name="phone" required placeholder="010XXXXXXXX" class="w-full border border-black p-3 text-xs bg-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">Governorate / City</label>
                                    <input type="text" name="city" id="account-city" required placeholder="Cairo / Giza / Alexandria" class="w-full border border-black p-3 text-xs bg-white focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">Detailed Street Address</label>
                                <input type="text" name="street_address" id="account-street" required placeholder="Building name/no, Street, Floor, Apt" class="w-full border border-black p-3 text-xs bg-white focus:outline-none">
                            </div>
                            <div class="flex items-center gap-2 pt-2">
                                <input type="checkbox" name="is_default" value="1" id="add_is_default" class="w-4 h-4 accent-black">
                                <label for="add_is_default" class="font-editorial text-xs uppercase tracking-wider text-black cursor-pointer">
                                    Set as default delivery address
                                </label>
                            </div>
                            <button type="submit" class="btn-luxury px-6 py-3 text-xs tracking-wider">
                                Save Address →
                            </button>
                        </form>
                    </div>

                    <!-- Address List -->
                    @if($addresses->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($addresses as $address)
                                <div class="border-2 {{ $address->is_default ? 'border-black bg-white' : 'border-black/20 bg-[#FAFAFA]' }} p-5 relative">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-editorial font-bold text-xs uppercase tracking-wider bg-black text-white px-2 py-0.5">
                                                {{ $address->label }}
                                            </span>
                                            @if($address->is_default)
                                                <span class="border border-black text-black text-[9px] font-editorial font-bold uppercase tracking-widest px-1.5 py-0.5">
                                                    DEFAULT
                                                </span>
                                            @endif
                                        </div>

                                        @if($address->latitude && $address->longitude)
                                            <a 
                                                href="https://maps.google.com/?q={{ $address->latitude }},{{ $address->longitude }}" 
                                                target="_blank" 
                                                class="text-[9px] font-mono font-bold uppercase border border-black/30 bg-white hover:bg-black hover:text-white px-2 py-0.5 flex items-center gap-1 transition-colors"
                                                title="View pinned GPS location on Google Maps"
                                            >
                                                <span>📍 GPS: {{ number_format($address->latitude, 3) }}, {{ number_format($address->longitude, 3) }} ↗</span>
                                            </a>
                                        @endif
                                    </div>

                                    <p class="font-editorial font-bold text-sm text-black mb-1">{{ $address->full_name }}</p>
                                    <p class="text-xs text-black/80 font-sans mb-1">{{ $address->street_address }}</p>
                                    <p class="text-xs text-black/80 font-sans mb-2">{{ $address->city }}, Egypt</p>
                                    <p class="text-xs text-black/60 font-sans mb-4">Phone: {{ $address->phone }}</p>

                                    <div class="flex items-center justify-between border-t border-black/10 pt-3 text-xs font-editorial font-bold uppercase tracking-wider">
                                        <div class="flex items-center gap-3">
                                            <button 
                                                type="button" 
                                                @click="editingAddressId = editingAddressId === {{ $address->id }} ? null : {{ $address->id }}"
                                                class="text-black hover:underline"
                                            >
                                                Edit
                                            </button>
                                            
                                            <form action="{{ route('addresses.destroy', $address->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this saved address?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>

                                        @if(!$address->is_default)
                                            <form action="{{ route('addresses.set-default', $address->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-black/60 hover:text-black hover:underline">
                                                    Make Default
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Inline Edit Form -->
                                    <div x-show="editingAddressId === {{ $address->id }}" class="mt-4 pt-4 border-t border-black/20">
                                        <h4 class="font-editorial font-bold text-xs uppercase mb-3">Edit Address ({{ $address->label }})</h4>
                                        <form action="{{ route('addresses.update', $address->id) }}" method="POST" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="block text-[9px] font-editorial uppercase tracking-wider text-black/70 mb-0.5">Label</label>
                                                <input type="text" name="label" value="{{ $address->label }}" required class="w-full border border-black p-2 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-editorial uppercase tracking-wider text-black/70 mb-0.5">Full Name</label>
                                                <input type="text" name="full_name" value="{{ $address->full_name }}" required class="w-full border border-black p-2 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-editorial uppercase tracking-wider text-black/70 mb-0.5">Phone Number</label>
                                                <input type="tel" name="phone" value="{{ $address->phone }}" required class="w-full border border-black p-2 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-editorial uppercase tracking-wider text-black/70 mb-0.5">Governorate / City</label>
                                                <input type="text" name="city" value="{{ $address->city }}" required class="w-full border border-black p-2 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-editorial uppercase tracking-wider text-black/70 mb-0.5">Street Address</label>
                                                <input type="text" name="street_address" value="{{ $address->street_address }}" required class="w-full border border-black p-2 text-xs bg-white">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" name="is_default" value="1" id="edit_def_{{ $address->id }}" {{ $address->is_default ? 'checked' : '' }} class="w-3.5 h-3.5 accent-black">
                                                <label for="edit_def_{{ $address->id }}" class="text-[10px] font-editorial uppercase">Set as default</label>
                                            </div>
                                            <div class="flex gap-2 pt-1">
                                                <button type="submit" class="bg-black text-white px-4 py-2 text-[10px] font-editorial font-bold uppercase tracking-wider">Save Changes</button>
                                                <button type="button" @click="editingAddressId = null" class="border border-black px-4 py-2 text-[10px] font-editorial uppercase">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 bg-neutral-50 border border-dashed border-black/20">
                            <p class="font-editorial text-sm uppercase tracking-wider text-black/60 mb-4">No saved addresses yet.</p>
                            <button @click="showAddAddress = true" class="btn-luxury inline-block px-6 py-3 text-xs tracking-wider">
                                Add Your First Delivery Address →
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Tab 3: Account Details & Security -->
                <div x-cloak x-show="activeTab === 'details'" class="space-y-6">
                    <div class="border border-black/10 p-6 bg-[#FAFAFA] space-y-4">
                        <h2 class="font-editorial font-bold text-base uppercase border-b border-black/10 pb-3">Client Profile</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-sans">
                            <div>
                                <span class="font-editorial font-bold uppercase text-[10px] text-black/60 block mb-1">Full Name</span>
                                <span class="text-sm font-semibold text-black">{{ auth()->user()->name }}</span>
                            </div>
                            <div>
                                <span class="font-editorial font-bold uppercase text-[10px] text-black/60 block mb-1">Email Address</span>
                                <span class="text-sm font-semibold text-black">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-black/10 p-6 bg-[#FAFAFA] space-y-4">
                        <h2 class="font-editorial font-bold text-base uppercase border-b border-black/10 pb-3">Account Security</h2>
                        <div class="text-xs space-y-2">
                            <p class="text-black/80 font-sans">
                                <strong>Password last changed:</strong> 
                                {{ auth()->user()->password_changed_at ? auth()->user()->password_changed_at->format('M d, Y h:i A') : 'Initial Account Setup' }}
                            </p>
                            <p class="text-black/60 text-[11px] font-sans">
                                To update your account password, click below to request a secure verification link sent to your registered email.
                            </p>
                        </div>
                        <form action="{{ route('password.email') }}" method="POST" class="inline-block">
                            @csrf
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                            <button type="submit" class="border-2 border-black bg-white px-5 py-2.5 text-xs font-editorial font-bold uppercase tracking-wider hover:bg-black hover:text-white transition-colors">
                                Send Password Reset Email →
                            </button>
                        </form>
                    </div>
                    </div>
                </div>

        @else
            @php
                $portalBgUrl = $settings['account_portal_background_url'] ?? '';
                $portalOverlay = $settings['account_portal_overlay'] ?? 'medium';
            @endphp

            <!-- Guest: Minimal Luxury Portal -->
            <div class="relative py-8 sm:py-16">
                @if(!empty($portalBgUrl))
                    <div class="fixed inset-0 bg-cover bg-center z-0 pointer-events-none" style="background-image: url('{{ $portalBgUrl }}');"></div>
                    <div class="fixed inset-0 z-0 pointer-events-none {{ 
                        $portalOverlay === 'none' ? 'bg-transparent' : (
                        $portalOverlay === 'subtle' ? 'bg-black/35' : (
                        $portalOverlay === 'dark' ? 'bg-black/80' : 
                        'bg-gradient-to-t from-black/90 via-black/60 to-black/40'
                    )) }}"></div>
                @endif

                <div class="relative z-10 max-w-sm mx-auto">
                    <div class="border-2 border-black {{ !empty($portalBgUrl) ? 'bg-white/95 backdrop-blur-md' : 'bg-white' }} px-10 py-12 shadow-[12px_12px_0px_0px_rgba(0,0,0,1)] text-center">

                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 border border-black/30 px-3 py-1 mb-8">
                            <span class="w-1 h-1 bg-black rounded-full"></span>
                            <span class="font-editorial text-[9px] uppercase tracking-[0.3em] text-black/60">Client Access</span>
                        </div>

                        <!-- Title -->
                        <h1 class="font-display text-3xl uppercase tracking-tight text-black font-normal mb-2">
                            {{ $settings['storeName'] ?? 'ATELIER' }}
                        </h1>
                        <p class="text-[11px] text-black/40 tracking-wide mb-8">Sign in to your account</p>

                        <!-- Google Button — Clean White -->
                        <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 border border-black/20 bg-white hover:bg-gray-50 transition-colors py-3.5 px-5 shadow-sm hover:shadow-md">
                            <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                            </svg>
                            <span class="text-[11px] font-medium text-gray-700 tracking-wide">Continue with Google</span>
                        </a>

                        <!-- Footer -->
                        <div class="mt-8 pt-6 border-t border-black/8 flex items-center justify-between">
                            <a href="{{ url('/pages/terms') }}" class="text-[10px] text-black/30 hover:text-black/60 transition-colors">Terms</a>
                            <a href="{{ url('/admin/login') }}" class="text-[10px] font-mono text-black/25 hover:text-black/50 transition-colors uppercase tracking-wider">Staff →</a>
                            <a href="{{ url('/pages/privacy') }}" class="text-[10px] text-black/30 hover:text-black/60 transition-colors">Privacy</a>
                        </div>
                    </div>
                </div>
            </div>
        @endauth
    </div>
</div>
@endsection
