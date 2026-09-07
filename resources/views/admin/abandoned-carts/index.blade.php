@extends('layouts.admin')

@section('title', 'Abandoned Carts')

@section('content')
<div class="max-w-6xl space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">CONVERSION RECOVERY</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Abandoned Carts</h1>
            <p class="text-xs text-gray-500 mt-0.5">Track customers who started checkout but did not complete payment within 2 hours</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="bg-white border-2 border-black px-4 py-2 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] text-center">
                <span class="text-[9px] font-mono font-bold uppercase text-gray-400 block">Pending Recovery</span>
                <span class="text-xl font-black text-black font-mono">{{ $pendingFollowUp }}</span>
            </div>
            <div class="bg-white border-2 border-black px-4 py-2 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] text-center">
                <span class="text-[9px] font-mono font-bold uppercase text-gray-400 block">Total Recorded</span>
                <span class="text-xl font-black text-black font-mono">{{ $totalAbandoned }}</span>
            </div>
        </div>
    </div>

    <!-- Filters & Tabs -->
    <div class="flex items-center gap-2 border-b-2 border-black pb-2 text-xs font-bold uppercase">
        <a 
            href="{{ route('admin.abandoned-carts.index') }}" 
            class="px-3 py-1.5 border {{ !request()->filled('status') ? 'bg-black text-white border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]' : 'bg-white text-gray-700 border-gray-300 hover:border-black' }}"
        >
            Pending Follow-Up ({{ $pendingFollowUp }})
        </a>
        <a 
            href="{{ route('admin.abandoned-carts.index', ['status' => 'followed_up']) }}" 
            class="px-3 py-1.5 border {{ request('status') === 'followed_up' ? 'bg-black text-white border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]' : 'bg-white text-gray-700 border-gray-300 hover:border-black' }}"
        >
            Followed Up History
        </a>
    </div>

    <!-- Carts Table -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-black bg-gray-100 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="p-3">Customer / Session</th>
                        <th class="p-3">Item in Cart</th>
                        <th class="p-3">Value (Est.)</th>
                        <th class="p-3">Abandoned At</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Recovery Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-medium">
                    @forelse($carts as $cart)
                        @php
                            $user = $cart->user;
                            $product = $cart->product;
                            $cartData = $cart->cart_data_json ?? [];
                            $productTitle = $product?->title ?? ($cartData['product_title'] ?? 'Archived Piece');
                            $priceMinor = $product?->retail_price_minor ?? ($cartData['price_minor'] ?? 0);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3">
                                @if($user)
                                    <span class="font-bold text-black block">{{ $user->name }}</span>
                                    <span class="text-[10px] text-gray-500 font-mono">{{ $user->email }}</span>
                                @else
                                    <span class="font-bold text-gray-700 block">Guest Visitor</span>
                                    <span class="text-[10px] text-gray-400 font-mono truncate max-w-[150px] inline-block">{{ $cart->session_id }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="font-bold text-black">{{ $productTitle }}</span>
                            </td>
                            <td class="p-3 font-mono font-bold text-black">
                                {{ number_format($priceMinor / 100, 0) }} EGP
                            </td>
                            <td class="p-3 text-gray-600 font-mono text-[10px]">
                                {{ $cart->abandoned_at?->diffForHumans() ?? 'Unknown' }}
                                <span class="block text-gray-400 text-[9px]">{{ $cart->abandoned_at?->format('M d, H:i') }}</span>
                            </td>
                            <td class="p-3">
                                @if($cart->followed_up_at)
                                    <span class="bg-green-100 text-green-800 border border-green-800 text-[9px] font-bold uppercase px-2 py-0.5">
                                        Followed Up
                                    </span>
                                    @if($cart->follow_up_note)
                                        <span class="block text-[9px] text-gray-500 mt-0.5">{{ $cart->follow_up_note }}</span>
                                    @endif
                                @else
                                    <span class="bg-amber-100 text-amber-800 border border-amber-800 text-[9px] font-bold uppercase px-2 py-0.5">
                                        Pending Action
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @if(!$cart->followed_up_at)
                                    <form action="{{ route('admin.abandoned-carts.follow-up', $cart->id) }}" method="POST" class="inline-flex items-center gap-1">
                                        @csrf
                                        <input type="text" name="note" placeholder="Note (e.g. Sent WhatsApp coupon)" class="border border-black p-1 text-[10px] focus:outline-none w-44">
                                        <button type="submit" class="bg-black text-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider hover:bg-gray-800">
                                            Mark Done ✓
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-[10px] font-mono">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400 text-xs">
                                No abandoned carts in this category. All checkouts are converting smoothly.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($carts->hasPages())
            <div class="p-4 border-t-2 border-black bg-gray-50">
                {{ $carts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
