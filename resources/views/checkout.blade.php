@extends('layouts.app')

@section('title', 'Checkout — ' . ($settings['storeName'] ?? 'ATELIER'))
@section('meta_description', 'Complete your ATELIER luxury order. Secure checkout with saved addresses and GPS delivery pin.')

@section('content')

{{-- ── ORDER SUMMARY RIBBON ─────────────────────────────────────────────── --}}
@if(isset($cartItems) && count($cartItems))
<div class="bg-black text-white text-xs font-editorial font-bold uppercase tracking-[0.18em] py-2.5 px-6 flex items-center justify-between">
    <span>{{ count($cartItems) }} {{ count($cartItems) === 1 ? 'قطعة' : 'قطع' }} في طلبك</span>
    <span>{{ $settings['storeName'] ?? 'ATELIER' }} — Secure Checkout</span>
</div>
@endif

<div class="bg-[#F5F5F0] min-h-screen"
    x-data="{
        selectedAddressId: '{{ $defaultAddress?->id ?? ($savedAddresses->isNotEmpty() ? $savedAddresses->first()->id : 'new') }}',
        useNewAddress: {{ $savedAddresses->isEmpty() ? 'true' : 'false' }},
        payMethod: 'cod',
    }"
>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-16">

        {{-- ── PAGE HEADER ─────────────────────────────────── --}}
        <div class="mb-10">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="font-editorial text-[10px] font-bold uppercase tracking-[0.28em] text-black/40 mb-1.5">
                        إتمام الطلب
                    </p>
                    <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl uppercase tracking-tight text-black leading-none">
                        Checkout
                    </h1>
                </div>
                <div class="flex items-center gap-3 pt-1 shrink-0">
                    <div class="w-5 h-px bg-black/30"></div>
                    @auth
                        <span class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black/50">
                            {{ auth()->user()->name }}
                        </span>
                    @else
                        <a href="{{ route('account') }}" class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black hover:underline">
                            تسجيل الدخول للعناوين المحفوظة ←
                        </a>
                    @endauth
                </div>
            </div>
            <div class="h-px bg-black"></div>
        </div>

        {{-- ── ERROR BANNER ─────────────────────────────────── --}}
        @if($errors->any())
        <div class="bg-red-50 border-2 border-red-800 p-4 mb-8 flex items-start gap-3">
            <span class="text-red-800 text-base shrink-0">⚠</span>
            <ul class="text-red-800 text-xs font-sans space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- ── TWO-COLUMN LAYOUT ─────────────────────────────── --}}
        <form action="{{ route('checkout.place') }}" method="POST" id="checkoutForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12 items-start">

            {{-- ════════════════════════════════════════════════════
                 LEFT COLUMN — Delivery & Payment (3 cols)
            ════════════════════════════════════════════════════ --}}
            <div class="lg:col-span-3 space-y-8">

                {{-- ── STEP 1: DELIVERY DESTINATION ──────────── --}}
                <div class="bg-white border-2 border-black shadow-[6px_6px_0_0_rgba(0,0,0,1)]">

                    {{-- Step header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b-2 border-black bg-black">
                        <div class="flex items-center gap-3">
                            <span class="font-editorial font-black text-xs text-white bg-white text-black px-2 py-0.5"
                                  style="background:#fff; color:#000;">01</span>
                            <span class="font-editorial font-bold text-[11px] uppercase tracking-[0.2em] text-white">
                                عنوان التوصيل
                            </span>
                        </div>
                        <span class="text-[9px] font-mono text-white/60 uppercase tracking-widest">Delivery Address</span>
                    </div>

                    <div class="p-6 space-y-6">

                        {{-- Saved addresses grid --}}
                        @auth
                        @if($savedAddresses->isNotEmpty())
                        <div class="space-y-3">
                            <p class="text-[10px] font-editorial font-bold uppercase tracking-[0.18em] text-black/50">
                                العناوين المحفوظة
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($savedAddresses as $addr)
                                <label
                                    class="relative flex flex-col border-2 cursor-pointer transition-all duration-150 p-4 group"
                                    :class="selectedAddressId == '{{ $addr->id }}' && !useNewAddress
                                        ? 'border-black bg-black text-white shadow-[4px_4px_0_0_rgba(0,0,0,1)]'
                                        : 'border-black/20 bg-white hover:border-black'"
                                    @click="selectedAddressId = '{{ $addr->id }}'; useNewAddress = false;"
                                >
                                    <input type="radio" name="saved_address_id" value="{{ $addr->id }}"
                                        :checked="selectedAddressId == '{{ $addr->id }}' && !useNewAddress"
                                        class="sr-only">

                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-editorial font-black text-[9px] uppercase tracking-widest px-2 py-0.5"
                                            :class="selectedAddressId == '{{ $addr->id }}' && !useNewAddress ? 'bg-white text-black' : 'bg-black text-white'">
                                            {{ $addr->label ?: 'عنوان' }}
                                        </span>
                                        @if($addr->is_default)
                                        <span class="text-[8px] font-editorial font-bold uppercase tracking-widest border px-1.5 py-0.5"
                                            :class="selectedAddressId == '{{ $addr->id }}' && !useNewAddress ? 'border-white text-white' : 'border-black text-black'">
                                            افتراضي
                                        </span>
                                        @endif
                                    </div>

                                    <p class="font-editorial font-bold text-sm mb-0.5">{{ $addr->full_name }}</p>
                                    <p class="text-xs font-sans opacity-70">{{ $addr->street_address }}</p>
                                    <p class="text-xs font-sans opacity-70">{{ $addr->city }}, Egypt</p>
                                    <p class="text-[10px] font-mono mt-2 opacity-60">{{ $addr->phone }}</p>

                                    @if($addr->latitude && $addr->longitude)
                                    <div class="mt-2.5 inline-flex items-center gap-1.5 text-[9px] font-mono px-2 py-1 border"
                                        :class="selectedAddressId == '{{ $addr->id }}' && !useNewAddress ? 'border-white/30 bg-white/10' : 'border-black/20 bg-[#F5F5F0]'">
                                        <span>📍</span>
                                        <span class="font-bold">{{ number_format($addr->latitude,4) }}, {{ number_format($addr->longitude,4) }}</span>
                                    </div>
                                    @endif

                                    {{-- Selected checkmark --}}
                                    <div class="absolute top-3 right-3 w-4 h-4 border-2 flex items-center justify-center transition-all"
                                        :class="selectedAddressId == '{{ $addr->id }}' && !useNewAddress
                                            ? 'border-white bg-white'
                                            : 'border-black/30 bg-transparent'">
                                        <svg x-show="selectedAddressId == '{{ $addr->id }}' && !useNewAddress"
                                            class="w-2.5 h-2.5 text-black" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="square" stroke-linejoin="miter" d="M2 6l3 3 5-5"/>
                                        </svg>
                                    </div>
                                </label>
                                @endforeach

                                {{-- New address option --}}
                                <label
                                    class="relative flex flex-col items-center justify-center border-2 cursor-pointer transition-all duration-150 p-5 min-h-[100px] text-center"
                                    :class="useNewAddress ? 'border-black bg-black text-white shadow-[4px_4px_0_0_rgba(0,0,0,1)]' : 'border-dashed border-black/30 bg-white hover:border-black/60'"
                                    @click="useNewAddress = true; selectedAddressId = 'new'; $nextTick(() => window.dispatchEvent(new CustomEvent('map-refresh-checkout-map')));"
                                >
                                    <span class="text-2xl mb-1.5" :class="useNewAddress ? 'opacity-70' : 'opacity-40'">＋</span>
                                    <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.15em] block">
                                        عنوان توصيل جديد
                                    </span>
                                    <span class="text-[10px] font-sans mt-1 opacity-60">تثبيت موقع جديد على الخريطة</span>
                                </label>
                            </div>
                        </div>
                        @endif
                        @endauth

                        {{-- ── NEW ADDRESS FORM ──────────── --}}
                        <div x-show="useNewAddress || {{ $savedAddresses->isEmpty() ? 'true' : 'false' }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="space-y-5">

                            @auth
                            @if($savedAddresses->isNotEmpty())
                            <div class="h-px bg-black/10"></div>
                            @endif
                            @endauth

                            {{-- ── SMART MAP ─── --}}
                            <div>
                                @include('partials.location-map', [
                                    'mapId'         => 'checkout-map',
                                    'latInputId'    => 'checkout-lat',
                                    'lngInputId'    => 'checkout-lng',
                                    'cityInputId'   => 'checkout-city',
                                    'streetInputId' => 'checkout-street',
                                    'disabled'      => false,
                                ])
                            </div>

                            {{-- Customer details --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                <div>
                                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-[0.18em] text-black mb-1.5">
                                        الاسم الكامل <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="full_name"
                                        :required="useNewAddress || {{ $savedAddresses->isEmpty() ? 'true' : 'false' }}"
                                        :disabled="!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}"
                                        placeholder="مثال: محمد أحمد"
                                        value="{{ auth()->user()?->name }}"
                                        class="w-full border-2 border-black p-3 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none focus:shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-shadow disabled:opacity-40 font-sans"
                                    >
                                </div>
                                <div>
                                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-[0.18em] text-black mb-1.5">
                                        رقم الهاتف (WhatsApp) <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        :required="useNewAddress || {{ $savedAddresses->isEmpty() ? 'true' : 'false' }}"
                                        :disabled="!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}"
                                        placeholder="010XXXXXXXX"
                                        class="w-full border-2 border-black p-3 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none focus:shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-shadow disabled:opacity-40 font-mono"
                                        dir="ltr"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block font-editorial font-bold text-[10px] uppercase tracking-[0.18em] text-black mb-1.5">
                                    المحافظة / المدينة <span class="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="city"
                                    id="checkout-city"
                                    :required="useNewAddress || {{ $savedAddresses->isEmpty() ? 'true' : 'false' }}"
                                    :disabled="!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}"
                                    placeholder="القاهرة / الجيزة / الإسكندرية"
                                    class="w-full border-2 border-black p-3 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none focus:shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-shadow disabled:opacity-40 font-sans"
                                >
                            </div>

                            <div>
                                <label class="block font-editorial font-bold text-[10px] uppercase tracking-[0.18em] text-black mb-1.5">
                                    العنوان بالتفصيل <span class="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="street_address"
                                    id="checkout-street"
                                    :required="useNewAddress || {{ $savedAddresses->isEmpty() ? 'true' : 'false' }}"
                                    :disabled="!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}"
                                    placeholder="رقم المبنى، اسم الشارع، الطابق، رقم الشقة"
                                    class="w-full border-2 border-black p-3 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none focus:shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-shadow disabled:opacity-40 font-sans"
                                >
                            </div>

                            @auth
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-2 border-t border-black/10">
                                <label class="flex items-center gap-2.5 cursor-pointer"
                                    :class="(!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}) ? 'opacity-30 pointer-events-none' : ''">
                                    <input type="checkbox" name="save_to_address_book" value="1" id="chk_save_book"
                                        checked
                                        :disabled="!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}"
                                        class="w-4 h-4 accent-black shrink-0">
                                    <span class="font-editorial text-[10px] uppercase tracking-wider text-black">
                                        حفظ العنوان في دفتر العناوين
                                    </span>
                                </label>
                                <input type="text" name="address_label"
                                    placeholder="اسم العنوان (مثال: البيت، العمل)"
                                    :disabled="!useNewAddress && {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }}"
                                    class="border border-black p-2 text-[11px] bg-white focus:outline-none w-44 disabled:opacity-30 font-sans">
                            </div>
                            @endauth
                        </div>

                    </div>
                </div>

                {{-- ── STEP 2: PAYMENT ────────────────────────── --}}
                <div class="bg-white border-2 border-black shadow-[6px_6px_0_0_rgba(0,0,0,1)]">

                    <div class="flex items-center justify-between px-6 py-4 border-b-2 border-black bg-black">
                        <div class="flex items-center gap-3">
                            <span class="font-editorial font-black text-xs bg-white text-black px-2 py-0.5">02</span>
                            <span class="font-editorial font-bold text-[11px] uppercase tracking-[0.2em] text-white">
                                طريقة الدفع
                            </span>
                        </div>
                        <span class="text-[9px] font-mono text-white/60 uppercase tracking-widest">Payment</span>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                            <label class="relative flex items-start gap-4 border-2 p-4 cursor-pointer transition-all"
                                :class="payMethod === 'cod' ? 'border-black bg-black text-white shadow-[4px_4px_0_0_rgba(0,0,0,1)]' : 'border-black/20 hover:border-black bg-white'"
                                @click="payMethod = 'cod'">
                                <input type="radio" name="payment_method" value="cod"
                                    x-model="payMethod" class="sr-only">
                                <span class="text-2xl" :class="payMethod === 'cod' ? 'opacity-80' : 'opacity-50'">💵</span>
                                <div>
                                    <span class="font-editorial font-bold text-xs uppercase tracking-[0.15em] block mb-0.5">
                                        الدفع عند الاستلام
                                    </span>
                                    <span class="text-[10px] font-sans opacity-60">فحص القطعة قبل الدفع</span>
                                </div>
                                <div class="absolute top-3 right-3 w-4 h-4 border-2 flex items-center justify-center"
                                    :class="payMethod === 'cod' ? 'border-white bg-white' : 'border-black/30'">
                                    <svg x-show="payMethod === 'cod'" class="w-2.5 h-2.5 text-black" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="square" d="M2 6l3 3 5-5"/>
                                    </svg>
                                </div>
                            </label>

                            <label class="relative flex items-start gap-4 border-2 p-4 cursor-pointer transition-all"
                                :class="payMethod === 'paymob' ? 'border-black bg-black text-white shadow-[4px_4px_0_0_rgba(0,0,0,1)]' : 'border-black/20 hover:border-black bg-white'"
                                @click="payMethod = 'paymob'">
                                <input type="radio" name="payment_method" value="paymob"
                                    x-model="payMethod" class="sr-only">
                                <span class="text-2xl" :class="payMethod === 'paymob' ? 'opacity-80' : 'opacity-50'">💳</span>
                                <div>
                                    <span class="font-editorial font-bold text-xs uppercase tracking-[0.15em] block mb-0.5">
                                        Paymob / فيزا / ValU
                                    </span>
                                    <span class="text-[10px] font-sans opacity-60">دفع إلكتروني آمن</span>
                                </div>
                                <div class="absolute top-3 right-3 w-4 h-4 border-2 flex items-center justify-center"
                                    :class="payMethod === 'paymob' ? 'border-white bg-white' : 'border-black/30'">
                                    <svg x-show="payMethod === 'paymob'" class="w-2.5 h-2.5 text-black" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="square" d="M2 6l3 3 5-5"/>
                                    </svg>
                                </div>
                            </label>

                        </div>
                    </div>
                </div>

                {{-- Survey Widget --}}
                <div>
                    @include('partials.survey-widget', ['targetPage' => 'checkout'])
                </div>

            </div>

            {{-- ════════════════════════════════════════════════════
                 RIGHT COLUMN — Order Summary (2 cols)
            ════════════════════════════════════════════════════ --}}
            <div class="lg:col-span-2">
                <div class="bg-white border-2 border-black shadow-[6px_6px_0_0_rgba(0,0,0,1)] sticky top-6">

                    <div class="flex items-center justify-between px-5 py-4 border-b-2 border-black">
                        <span class="font-editorial font-bold text-[11px] uppercase tracking-[0.2em] text-black">
                            ملخص الطلب
                        </span>
                        <span class="text-[9px] font-mono text-black/40 uppercase tracking-widest">Order Summary</span>
                    </div>

                    <div class="p-5">

                    {{-- Cart Items --}}
                        @if(isset($cartItems) && count($cartItems))
                        <div class="space-y-4 mb-5">
                            @foreach($cartItems as $item)
                            <div class="flex items-start gap-3">
                                @if(isset($item['image']))
                                <div class="w-14 h-14 border border-black/10 overflow-hidden shrink-0 bg-[#F5F5F0]">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}"
                                        class="w-full h-full object-contain" decoding="async">
                                </div>
                                @else
                                <div class="w-14 h-14 border border-black/10 bg-[#F5F5F0] flex items-center justify-center shrink-0">
                                    <span class="text-black/20 text-xl">◆</span>
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="font-editorial font-bold text-xs uppercase tracking-wider leading-tight">
                                        {{ $item['name'] ?? 'ATELIER Piece' }}
                                    </p>
                                    @if(!empty($item['variant']))
                                    <p class="text-[10px] font-sans text-black/60 mt-1 inline-flex items-center gap-1">
                                        @if(!empty($item['color_hex']))<i class="w-2.5 h-2.5 rounded-full border border-black/20" style="background: {{ $item['color_hex'] }}"></i>@endif
                                        اللون/الاختيار: {{ $item['variant'] }}
                                    </p>
                                    @endif
                                    <p class="text-[10px] font-mono text-black/50 mt-0.5">× {{ $item['qty'] ?? 1 }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-editorial font-bold text-xs text-black">
                                        {{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1)) }}
                                        <span class="text-[9px] font-normal text-black/50">EGP</span>
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        {{-- Elegant placeholder when cart is session-based --}}
                        <div class="flex items-center gap-3 py-3 mb-4 border-b border-black/10">
                            <div class="w-10 h-10 bg-[#F5F5F0] border border-black/10 flex items-center justify-center shrink-0">
                                <span class="text-black/30 text-sm">◆</span>
                            </div>
                            <div>
                                <p class="font-editorial font-bold text-xs uppercase tracking-wider text-black">قطع ATELIER الفاخرة</p>
                                <p class="text-[10px] font-sans text-black/40 mt-0.5">المنتجات المختارة</p>
                            </div>
                        </div>
                        @endif

                        {{-- Totals --}}
                        <div class="border-t border-black/10 pt-4 space-y-2.5">
                            @if(isset($subtotal))
                            <div class="flex justify-between text-xs font-sans text-black/60">
                                <span>المجموع الفرعي</span>
                                <span class="font-mono">{{ number_format($subtotal) }} EGP</span>
                            </div>
                            @endif
                            @if(isset($shippingCost))
                            <div class="flex justify-between text-xs font-sans text-black/60">
                                <span>الشحن</span>
                                <span class="font-mono">{{ $shippingCost == 0 ? 'مجاني' : number_format($shippingCost) . ' EGP' }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-center border-t-2 border-black pt-3 mt-1">
                                <span class="font-editorial font-black text-sm uppercase tracking-wider">الإجمالي</span>
                                <span class="font-display text-xl text-black">
                                    {{ number_format(($total ?? $subtotal ?? 0)) }}
                                    <span class="font-editorial font-bold text-sm">EGP</span>
                                </span>
                            </div>
                        </div>

                        {{-- Submit CTA --}}
                        <button type="submit"
                            class="w-full mt-6 bg-black text-white font-editorial font-black text-xs uppercase tracking-[0.22em] py-4 hover:bg-gray-900 transition-colors shadow-[4px_4px_0_0_rgba(0,0,0,0.2)] active:translate-y-0.5 active:shadow-[2px_2px_0_0_rgba(0,0,0,0.2)] focus:outline-none"
                        >
                            تأكيد وإرسال الطلب →
                        </button>

                        {{-- Back link --}}
                        <div class="mt-4 text-center">
                            <a href="{{ route('home') }}"
                                class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black/40 hover:text-black transition-colors">
                                ← العودة للمتجر
                            </a>
                        </div>

                        {{-- Trust signals --}}
                        <div class="mt-5 pt-4 border-t border-black/10 flex items-center justify-center gap-5">
                            <div class="flex items-center gap-1.5 text-[9px] font-editorial font-bold uppercase tracking-wider text-black/40">
                                <span>🔒</span> <span>آمن ومشفر</span>
                            </div>
                            <div class="w-px h-3 bg-black/10"></div>
                            <div class="flex items-center gap-1.5 text-[9px] font-editorial font-bold uppercase tracking-wider text-black/40">
                                <span>📦</span> <span>تغليف فاخر</span>
                            </div>
                            <div class="w-px h-3 bg-black/10"></div>
                            <div class="flex items-center gap-1.5 text-[9px] font-editorial font-bold uppercase tracking-wider text-black/40">
                                <span>🚚</span> <span>توصيل سريع</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
{{-- Any page-level checkout scripts if needed --}}
@endpush
