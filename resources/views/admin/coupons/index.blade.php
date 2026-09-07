@extends('layouts.admin')

@section('title', 'Promotional Coupons')

@section('content')
<div class="space-y-8">

    <!-- Header -->
    <div class="border-b-2 border-black pb-4">
        <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">PROMOTIONS & DISCOUNTS</span>
        <h1 class="text-3xl font-black uppercase tracking-tight text-black">Coupon Codes</h1>
        <p class="text-xs text-gray-500 mt-0.5">{{ $coupons->total() }} coupon codes registered</p>
    </div>

    <!-- Create Coupon Form -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-5">
        <h2 class="text-lg font-black uppercase tracking-tight text-black border-b-2 border-black pb-2">+ Create New Coupon</h2>

        <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Coupon Code *</label>
                    <input type="text" name="code" required placeholder="e.g. ATELIER20" class="w-full border-2 border-black p-2.5 text-sm font-mono font-bold uppercase focus:outline-none">
                    <span class="text-[9px] text-gray-400">Auto-uppercased. Must be unique.</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Discount Type *</label>
                    <select name="type" class="w-full border-2 border-black p-2.5 text-xs bg-white focus:outline-none font-bold">
                        <option value="percentage">% Percentage Off</option>
                        <option value="fixed">Fixed Amount (EGP)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Discount Value *</label>
                    <input type="number" step="0.01" name="value" required placeholder="e.g. 20 (for 20% or 200 EGP)" class="w-full border-2 border-black p-2.5 text-sm font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Min Order Amount (EGP)</label>
                    <input type="number" step="0.01" name="min_order_amount" placeholder="e.g. 500 (leave blank for no minimum)" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Max Discount Cap (EGP)</label>
                    <input type="number" step="0.01" name="max_discount" placeholder="e.g. 200 (max discount on % type)" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Usage Limit</label>
                    <input type="number" name="max_uses" placeholder="e.g. 100 (blank = unlimited)" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expires_at" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <input type="checkbox" name="is_active" value="1" checked id="coupon_active" class="w-4 h-4 accent-black">
                    <label for="coupon_active" class="text-xs font-bold uppercase">Activate immediately</label>
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full bg-black text-white py-2.5 px-6 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                        Create Coupon →
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        @if($coupons->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="p-3">Code</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Value</th>
                            <th class="p-3">Min Order</th>
                            <th class="p-3">Usage</th>
                            <th class="p-3">Expires</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @foreach($coupons as $coupon)
                            <tr class="hover:bg-gray-50 transition-colors {{ !$coupon->isValid() ? 'opacity-60' : '' }}">
                                <td class="p-3 font-mono font-black text-black tracking-widest text-sm">
                                    {{ $coupon->code }}
                                </td>
                                <td class="p-3">
                                    <span class="text-[10px] font-bold uppercase border px-1.5 py-0.5 {{ $coupon->type === 'percentage' ? 'bg-blue-100 text-blue-800 border-blue-400' : 'bg-purple-100 text-purple-800 border-purple-400' }}">
                                        {{ $coupon->type === 'percentage' ? '%' : 'EGP' }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono font-black">
                                    @if($coupon->type === 'percentage')
                                        {{ $coupon->value }}% off
                                    @else
                                        {{ number_format($coupon->value / 100, 0) }} EGP off
                                    @endif
                                </td>
                                <td class="p-3 font-mono">
                                    {{ $coupon->min_order_amount_minor > 0 ? number_format($coupon->min_order_amount_minor / 100, 0) . ' EGP' : '—' }}
                                </td>
                                <td class="p-3 font-mono">
                                    <span class="font-black">{{ $coupon->used_count }}</span>
                                    <span class="text-gray-400">/ {{ $coupon->max_uses ?? '∞' }}</span>
                                </td>
                                <td class="p-3 font-mono text-gray-600">
                                    @if($coupon->expires_at)
                                        <span class="{{ $coupon->expires_at->isPast() ? 'text-red-600 font-bold' : '' }}">
                                            {{ $coupon->expires_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <span class="text-[10px] font-bold uppercase border px-1.5 py-0.5 {{ $coupon->is_active && $coupon->isValid() ? 'bg-green-100 text-green-800 border-green-600' : 'bg-gray-100 text-gray-700 border-gray-400' }}">
                                        {{ $coupon->is_active && $coupon->isValid() ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1">
                                    <form action="{{ route('admin.coupons.toggle', $coupon->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="border border-black px-2 py-1 text-[10px] font-bold uppercase hover:bg-black hover:text-white transition-colors">
                                            {{ $coupon->is_active ? 'Pause' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.coupons.delete', $coupon->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete coupon {{ addslashes($coupon->code) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="border border-red-600 text-red-600 px-2 py-1 text-[10px] font-bold uppercase hover:bg-red-50">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t-2 border-black bg-gray-50">
                {{ $coupons->links() }}
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <span class="text-4xl block mb-2">🎟</span>
                <h3 class="font-bold text-sm uppercase">No coupon codes created yet</h3>
            </div>
        @endif
    </div>

</div>
@endsection
