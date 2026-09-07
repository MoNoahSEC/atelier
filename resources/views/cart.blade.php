@extends('layouts.app')

@php
    $isArCart = ($settings['storefront_lang'] ?? 'en') === 'ar';
@endphp

@section('title', ($isArCart ? 'سلة المشتريات — ' : 'Shopping Cart — ') . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-10 lg:py-16">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-10">
        <p class="font-editorial text-[10px] font-bold uppercase tracking-[0.28em] text-black/40 mb-1.5">
            {{ $isArCart ? 'سلة التسوق' : 'Shopping Bag' }}
        </p>
        <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl uppercase tracking-tight text-black leading-none">
            {{ $isArCart ? 'سلة المشتريات' : 'Your Cart' }}
        </h1>
        <div class="h-px bg-black mt-4"></div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-2 border-green-800 p-3 mb-6 text-xs font-sans text-green-800 flex items-center gap-2">
        <span>✓</span> <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border-2 border-red-800 p-3 mb-6 text-xs font-sans text-red-800 flex items-center gap-2">
        <span>⚠</span> <span>{{ session('error') }}</span>
    </div>
    @endif

    @if(empty($cartItems))
    {{-- Empty Cart --}}
    <div class="bg-white border-2 border-black p-16 text-center shadow-[6px_6px_0_0_rgba(0,0,0,1)]">
        <div class="text-5xl mb-4 opacity-20">◆</div>
        <h2 class="font-editorial font-black text-xl uppercase tracking-wider text-black mb-2">
            {{ $isArCart ? 'سلة المشتريات فارغة' : 'Your bag is empty' }}
        </h2>
        <p class="font-sans text-xs text-black/50 mb-6">
            {{ $isArCart ? 'تصفح تشكيلتنا المميزة واختر القطع المناسبة لإضافتها للسلة.' : 'Browse our exclusive collection and add pieces to your bag.' }}
        </p>
        <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="btn-luxury inline-block px-8 py-4 text-xs">
            {{ $isArCart ? 'استكشف المنتجات ←' : 'Explore All Pieces →' }}
        </a>
    </div>

    @else
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">

        {{-- Cart Items (3 cols) --}}
        <div class="lg:col-span-3 space-y-3">
            @foreach($cartItems as $key => $item)
            <div class="bg-white border-2 border-black shadow-[3px_3px_0_0_rgba(0,0,0,1)] flex items-start gap-4 p-4">
                {{-- Image --}}
                <div class="w-20 h-20 sm:w-24 sm:h-24 border border-black/10 bg-[#F5F5F0] shrink-0 overflow-hidden">
                    @if(!empty($item['image']))
                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-contain" loading="eager" decoding="async">
                    @else
                    <div class="w-full h-full flex items-center justify-center"><span class="text-black/20 text-2xl">◆</span></div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <h3 class="font-editorial font-bold text-sm uppercase tracking-wider leading-tight text-black truncate">
                        {{ $item['name'] }}
                    </h3>
                    @if(!empty($item['variant']))
                    <p class="text-[10px] font-sans text-black/60 mt-1 inline-flex items-center gap-1.5 rounded-full bg-black/5 px-2 py-1">
                        @if(!empty($item['color_hex']))<i class="w-2.5 h-2.5 rounded-full border border-black/20" style="background: {{ $item['color_hex'] }}"></i>@endif
                        {{ $isArCart ? 'اللون/الاختيار:' : 'Option:' }} {{ $item['variant'] }}
                    </p>
                    @endif
                    <p class="font-editorial font-bold text-sm text-black mt-1">
                        {{ number_format($item['price']) }}
                        <span class="text-[10px] font-normal text-black/50">{{ $isArCart ? 'ج.م' : 'EGP' }}</span>
                    </p>

                    {{-- Qty Controls --}}
                    <div class="flex items-center gap-2 mt-3">
                        <form method="POST" action="{{ route('cart.update') }}" class="flex items-center gap-0 border-2 border-black">
                            @csrf
                            <input type="hidden" name="key" value="{{ $key }}">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit" class="w-8 h-8 flex items-center justify-center font-bold text-sm hover:bg-black hover:text-white transition-colors">−</button>
                        </form>
                        <span class="font-mono font-bold text-sm text-black w-8 text-center">{{ $item['qty'] }}</span>
                        <form method="POST" action="{{ route('cart.update') }}" class="flex items-center gap-0 border-2 border-black">
                            @csrf
                            <input type="hidden" name="key" value="{{ $key }}">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit" class="w-8 h-8 flex items-center justify-center font-bold text-sm hover:bg-black hover:text-white transition-colors">+</button>
                        </form>
                    </div>
                </div>

                {{-- Subtotal + Remove --}}
                <div class="shrink-0 flex flex-col items-end justify-between h-full">
                    <form method="POST" action="{{ route('cart.remove') }}">
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}">
                        <button type="submit" class="text-black/30 hover:text-black transition-colors text-xs font-bold uppercase tracking-wider">✕</button>
                    </form>
                    <div class="text-right mt-4">
                        <p class="font-editorial font-black text-sm text-black">
                            {{ number_format($item['price'] * $item['qty']) }}
                            <span class="text-[10px] font-normal">{{ $isArCart ? 'ج.م' : 'EGP' }}</span>
                        </p>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Clear Cart --}}
            <div class="flex justify-between items-center pt-2">
                <a href="{{ route('collections.show', ['slug' => 'all']) }}" class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black/50 hover:text-black transition-colors">
                    {{ $isArCart ? '← متابعة التسوق' : '← Continue Shopping' }}
                </a>
                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf
                    <button type="submit" onclick="return confirm('{{ $isArCart ? 'هل أنت متأكد من تفريغ السلة؟' : 'Clear all items from cart?' }}')" class="text-[10px] font-editorial font-bold uppercase tracking-wider text-red-700/60 hover:text-red-700 transition-colors">
                        {{ $isArCart ? 'تفريغ السلة' : 'Clear Cart' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Order Summary (2 cols) --}}
        <div class="lg:col-span-2">
            <div class="bg-white border-2 border-black shadow-[6px_6px_0_0_rgba(0,0,0,1)] sticky top-6">
                <div class="px-5 py-4 border-b-2 border-black">
                    <span class="font-editorial font-bold text-[11px] uppercase tracking-[0.2em] text-black">
                        {{ $isArCart ? 'ملخص الطلب' : 'Order Summary' }}
                    </span>
                </div>
                <div class="p-5 space-y-3">
                    {{-- Items breakdown --}}
                    @foreach($cartItems as $item)
                    <div class="flex justify-between text-[11px] font-sans text-black/60">
                        <span class="truncate pr-2">{{ $item['name'] }} × {{ $item['qty'] }}</span>
                        <span class="font-mono shrink-0">{{ number_format($item['price'] * $item['qty']) }}</span>
                    </div>
                    @endforeach

                    <div class="border-t border-black/10 pt-3 space-y-2">
                        <div class="flex justify-between text-xs font-sans text-black/60">
                            <span>{{ $isArCart ? 'المجموع الفرعي' : 'Subtotal' }}</span>
                            <span class="font-mono">{{ number_format($subtotal) }} {{ $isArCart ? 'ج.م' : 'EGP' }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-sans text-black/50">
                            <span>{{ $isArCart ? 'الشحن' : 'Shipping' }}</span>
                            <span class="text-[10px]">{{ $isArCart ? 'يُحسب عند إتمام الطلب' : 'Calculated at checkout' }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center border-t-2 border-black pt-3">
                        <span class="font-editorial font-black text-sm uppercase tracking-wider">{{ $isArCart ? 'الإجمالي التقديري' : 'Estimated Total' }}</span>
                        <span class="font-display text-xl text-black">{{ number_format($subtotal) }} <span class="font-editorial font-bold text-sm">{{ $isArCart ? 'ج.م' : 'EGP' }}</span></span>
                    </div>

                    <a href="{{ route('checkout') }}" class="block w-full bg-black text-white text-center font-editorial font-black text-xs uppercase tracking-[0.22em] py-4 hover:bg-gray-900 transition-colors mt-2 shadow-[4px_4px_0_0_rgba(0,0,0,0.2)]">
                        {{ $isArCart ? 'إتمام الطلب ←' : 'Go to Checkout →' }}
                    </a>

                    <div class="flex items-center justify-center gap-4 mt-3 pt-3 border-t border-black/10">
                        <span class="text-[9px] font-editorial uppercase tracking-wider text-black/30 flex items-center gap-1">🔒 {{ $isArCart ? 'دفع آمن' : 'Secure' }}</span>
                        <span class="text-[9px] font-editorial uppercase tracking-wider text-black/30 flex items-center gap-1">📦 {{ $isArCart ? 'تغليف فاخر' : 'Hand-Wrapped' }}</span>
                        <span class="text-[9px] font-editorial uppercase tracking-wider text-black/30 flex items-center gap-1">🚚 {{ $isArCart ? '3–5 أيام' : '3–5 Days' }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @endif

</div>
</div>
@endsection
