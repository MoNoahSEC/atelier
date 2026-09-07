@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)

@section('content')
<div class="max-w-5xl space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-black mb-1 block">
                ← Back to All Orders
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black uppercase tracking-tight text-black font-mono">Order #{{ $order->order_number }}</h1>
                @php
                    $badgeClass = match($order->status) {
                        'delivered'  => 'bg-green-100 text-green-800 border-green-600',
                        'shipped'    => 'bg-blue-100 text-blue-800 border-blue-600',
                        'processing' => 'bg-purple-100 text-purple-800 border-purple-600',
                        'cancelled'  => 'bg-red-100 text-red-800 border-red-600',
                        default      => 'bg-amber-100 text-amber-800 border-amber-600',
                    };
                @endphp
                <span class="text-xs font-bold uppercase px-2.5 py-0.5 border {{ $badgeClass }}">
                    {{ $order->status }}
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Placed on {{ $order->created_at->format('l, F d, Y \a\t H:i') }}</p>
        </div>
    </div>

    <!-- Status Update Action Card -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
        <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2 mb-4">
            Order Fulfillment Status
        </h2>
        <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Fulfillment Status</label>
                <select name="status" class="w-full border-2 border-black p-2.5 text-xs bg-white font-bold focus:outline-none">
                    <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>⏳ Pending (Awaiting Dispatch)</option>
                    <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>⚙️ Processing (In Packing)</option>
                    <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>🚚 Shipped (Out for Delivery)</option>
                    <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>✅ Delivered (Completed)</option>
                    <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>❌ Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Payment Status</label>
                <select name="payment_status" class="w-full border-2 border-black p-2.5 text-xs bg-white font-bold focus:outline-none">
                    <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Pending (COD / Unpaid)</option>
                    <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid (Collected / Confirmed)</option>
                    <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-black text-white py-2.5 px-4 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                    Update Order Status →
                </button>
            </div>
        </form>
    </div>

    <!-- Order Items & Customer Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Line Items (2 Cols) -->
        <div class="lg:col-span-2 bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
                Ordered Items ({{ $order->items->count() }})
            </h2>

            <div class="divide-y divide-gray-200">
                @foreach($order->items as $item)
                    @php
                        $p = $item->product;
                        $img = $p ? $p->image_url : null;
                        $imgUrl = $img ? (str_starts_with($img, 'http') ? $img : url($img)) : 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=200';
                    @endphp
                    <div class="py-4 flex items-center gap-4">
                        <div class="w-16 h-16 border border-black bg-gray-100 overflow-hidden shrink-0">
                            <img src="{{ $imgUrl }}" alt="{{ $item->product_title }}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-black text-sm uppercase tracking-tight text-black truncate">
                                {{ $item->product_title }}
                            </h3>
                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                SKU: {{ $item->sku }}
                                @if($item->variant_title) · Color/Variant: <strong>{{ $item->variant_title }}</strong> @endif
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                Quantity: <strong class="text-black font-mono">{{ $item->quantity }}</strong> × {{ number_format($item->unit_price_minor / 100, 0) }} EGP
                            </div>
                        </div>
                        <div class="text-right font-mono font-black text-sm text-black">
                            {{ number_format(($item->unit_price_minor * $item->quantity) / 100, 0) }} EGP
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Totals Summary Box -->
            <div class="border-t-2 border-black pt-4 space-y-2 text-xs font-mono">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>{{ number_format($order->subtotal_minor / 100, 0) }} EGP</span>
                </div>
                @if($order->discount_minor > 0)
                    <div class="flex justify-between text-green-700 font-bold">
                        <span>Discount Promo</span>
                        <span>-{{ number_format($order->discount_minor / 100, 0) }} EGP</span>
                    </div>
                @endif
                <div class="flex justify-between text-gray-600">
                    <span>Shipping</span>
                    <span>{{ number_format($order->shipping_minor / 100, 0) }} EGP</span>
                </div>
                @if($order->cod_surcharge_minor > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>Cash on Delivery Surcharge</span>
                        <span>+{{ number_format($order->cod_surcharge_minor / 100, 0) }} EGP</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-black text-black border-t border-black pt-2">
                    <span class="font-sans uppercase">Grand Total</span>
                    <span>{{ number_format($order->total_price_minor / 100, 0) }} EGP</span>
                </div>
            </div>
        </div>

        <!-- Customer & Shipping Card (1 Col) -->
        <div class="space-y-6">
            
            <!-- Customer Details -->
            <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-3">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
                    Customer Info
                </h2>
                <div>
                    <span class="block text-sm font-black uppercase text-black">{{ $order->customer_name ?: 'Guest Connoisseur' }}</span>
                    <span class="block text-xs text-gray-600 font-mono mt-0.5">{{ $order->customer_email }}</span>
                    @if($order->customer_phone)
                        <span class="block text-xs font-bold text-black font-mono mt-1">📞 {{ $order->customer_phone }}</span>
                        
                        @php
                            $waService = app(\App\Services\WhatsAppNotificationService::class);
                            $waDirectUrl = $waService->generateWhatsAppDirectUrl(
                                $order->customer_phone, 
                                "مرحباً {$order->customer_name} 👑\nبخصوص طلبك رقم #{$order->order_number} من ATELIER Studio Egypt..."
                            );
                        @endphp
                        <div class="pt-2">
                            <a 
                                href="{{ $waDirectUrl }}" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white font-bold text-xs uppercase px-3 py-1.5 border border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-colors"
                            >
                                <span>💬</span>
                                <span>مراسلة واتساب فورية</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-3">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
                    Delivery Address
                </h2>
                @php 
                    $addr = $order->shipping_address_json ?? []; 
                    $metaShipping = $order->metadata_json['shipping_address'] ?? [];
                    $lat = $metaShipping['latitude'] ?? ($addr['latitude'] ?? null);
                    $lng = $metaShipping['longitude'] ?? ($addr['longitude'] ?? null);
                @endphp
                <div class="text-xs space-y-1 text-gray-800">
                    <p class="font-bold text-black">{{ $addr['full_name'] ?? ($metaShipping['name'] ?? ($order->customer_name ?? '')) }}</p>
                    <p>{{ $addr['street_address'] ?? ($metaShipping['street'] ?? ($order->shipping_address ?? 'Not specified')) }}</p>
                    @if(isset($addr['apartment'])) <p>Apt/Suite: {{ $addr['apartment'] }}</p> @endif
                    <p>{{ $addr['city'] ?? ($metaShipping['city'] ?? '') }} {{ isset($addr['state']) ? '· ' . $addr['state'] : (isset($metaShipping['state']) ? '· ' . $metaShipping['state'] : '') }}</p>
                    @if(isset($addr['phone']) || isset($metaShipping['phone'])) 
                        <p class="font-mono text-gray-600">Phone: {{ $addr['phone'] ?? $metaShipping['phone'] }}</p> 
                    @endif

                    @if($lat && $lng)
                        <div class="pt-3">
                            <a 
                                href="https://maps.google.com/?q={{ $lat }},{{ $lng }}" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 text-xs font-mono font-bold bg-black text-white px-3 py-1.5 hover:bg-gray-800 transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                            >
                                <span>📍 فتح موقع العميل على الخريطة ({{ number_format($lat, 4) }}, {{ number_format($lng, 4) }}) ↗</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Details -->
            <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-3">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
                    Payment Architecture
                </h2>
                <div class="text-xs space-y-1">
                    <p><span class="text-gray-500">Method:</span> <strong class="uppercase font-mono">{{ $order->payment_method ?? 'COD' }}</strong></p>
                    <p><span class="text-gray-500">Status:</span> <strong class="capitalize">{{ $order->payment_status ?? 'pending' }}</strong></p>
                    <p><span class="text-gray-500">Currency:</span> <strong class="font-mono">{{ $order->currency ?? 'EGP' }}</strong></p>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
