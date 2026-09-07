@extends('layouts.app')

@section('title', 'Order #' . $order->order_number . ' — ' . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-16 lg:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="border-2 border-black bg-white p-6 sm:p-10 shadow-[12px_12px_0px_0px_rgba(0,0,0,1)]">
            
            <div class="border-b-2 border-black pb-6 mb-8 flex flex-col sm:flex-row justify-between sm:items-end gap-4">
                <div>
                    <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black/60 block mb-1">
                        CONFIDENTIAL ORDER RECEIPT
                    </span>
                    <h1 class="font-editorial font-black text-2xl sm:text-3xl uppercase tracking-normal text-black">
                        Order #{{ $order->order_number }}
                    </h1>
                    <span class="text-xs text-black/60 font-sans block mt-1">Placed on {{ $order->created_at->format('M d, Y h:i A') }}</span>
                </div>
                <div>
                    <span class="px-3 py-1 text-xs font-editorial font-bold uppercase tracking-wider {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ str_replace('_', ' ', $order->payment_status) }}
                    </span>
                </div>
            </div>

            <!-- Items -->
            <div class="space-y-4 mb-8">
                <h3 class="font-editorial font-bold text-sm uppercase tracking-wider text-black border-b border-black/20 pb-2">
                    Order Items ({{ $order->items->count() }})
                </h3>
                <div class="divide-y divide-black/10">
                    @foreach($order->items as $item)
                        <div class="py-3 flex justify-between items-center text-xs font-sans">
                            <div>
                                <span class="font-semibold text-black">{{ $item->product_title }}</span>
                                <span class="text-black/60 block">Qty: {{ $item->quantity }}</span>
                            </div>
                            <span class="font-bold text-black">{{ number_format($item->total_price_minor / 100, 2) }} {{ $order->currency }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Total Summary -->
            <div class="border-t-2 border-black pt-4 mb-8 space-y-2 text-xs font-sans">
                <div class="flex justify-between text-black/70">
                    <span>Subtotal</span>
                    <span>{{ number_format($order->subtotal_minor / 100, 2) }} {{ $order->currency }}</span>
                </div>
                @if($order->cod_surcharge_minor > 0)
                    <div class="flex justify-between text-black/70">
                        <span>Cash On Delivery Surcharge</span>
                        <span>{{ number_format($order->cod_surcharge_minor / 100, 2) }} {{ $order->currency }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm font-bold text-black border-t border-black/10 pt-2">
                    <span>Total Amount</span>
                    <span>{{ number_format($order->total_amount_minor / 100, 2) }} {{ $order->currency }}</span>
                </div>
            </div>

            <!-- Delivery Coordinates -->
            @if($order->metadata_json && isset($order->metadata_json['shipping_address']))
                @php $sAddr = $order->metadata_json['shipping_address']; @endphp
                <div class="bg-gray-50 border border-black/10 p-4 mb-8 text-xs font-sans">
                    <span class="font-editorial font-bold uppercase text-[10px] text-black/60 block mb-1">Delivery Destination</span>
                    <p class="font-semibold text-black">{{ $sAddr['name'] ?? $order->customer_email }}</p>
                    <p class="text-black/80">{{ $sAddr['street'] ?? '' }}, {{ $sAddr['city'] ?? '' }}</p>
                    <p class="text-black/60">Phone: {{ $sAddr['phone'] ?? '' }}</p>
                </div>
            @endif

            <div class="pt-4 border-t border-black/10 flex justify-between items-center">
                <a href="{{ route('account') }}" class="text-xs font-editorial font-bold uppercase tracking-wider text-black underline">
                    ← Return to Account Dashboard
                </a>
                <a href="{{ route('home') }}" class="btn-luxury px-6 py-3 text-xs tracking-wider">
                    Continue Shopping →
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
