@extends('layouts.admin')

@section('title', 'Shipping & Tax Settings')

@section('content')
<div class="max-w-5xl space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">FULFILLMENT & COMPLIANCE</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Shipping & Tax Rules</h1>
            <p class="text-xs text-gray-500 mt-0.5">Define Egyptian delivery zones, flat rates, express delivery estimates, and checkout tax rules</p>
        </div>
    </div>

    <!-- 1. Global Shipping & Tax Configuration -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
        <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
            1. Global Delivery & Tax Rules
        </h2>

        <form action="{{ route('admin.shipping.settings') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Standard Fallback Shipping Rate (EGP) *</label>
                    <input type="number" step="0.01" name="default_shipping_rate" required value="{{ $settings['default_shipping_rate'] ?? '75' }}" class="w-full border-2 border-black p-2.5 text-xs font-mono font-bold focus:outline-none">
                    <span class="text-[9px] text-gray-400">Used when no zone matches</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Free Shipping Threshold (EGP)</label>
                    <input type="number" step="0.01" name="free_shipping_threshold" value="{{ $settings['free_shipping_threshold'] ?? '2500' }}" placeholder="e.g. 2500 (blank = disabled)" class="w-full border-2 border-black p-2.5 text-xs font-mono font-bold focus:outline-none">
                    <span class="text-[9px] text-green-700">Orders above this amount get 0 EGP shipping</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Tax / VAT Rate (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" name="tax_percentage" value="{{ $settings['tax_percentage'] ?? '0' }}" min="0" max="100" class="w-24 border-2 border-black p-2.5 text-xs font-mono font-bold focus:outline-none">
                        <label class="flex items-center gap-1.5 text-xs font-bold cursor-pointer">
                            <input type="checkbox" name="tax_enabled" value="1" {{ ($settings['tax_enabled'] ?? '0') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-black">
                            <span>Enable Tax</span>
                        </label>
                    </div>
                    <span class="text-[9px] text-gray-400">Optional: 14% VAT if applicable</span>
                </div>
            </div>

            <button type="submit" class="bg-black text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                Save Global Rules →
            </button>
        </form>
    </div>

    <!-- 2. Shipping Zones Management -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-6">
        <div class="border-b-2 border-black pb-3">
            <h2 class="text-lg font-black uppercase tracking-tight text-black">Egyptian Delivery Zones</h2>
            <p class="text-xs text-gray-500 mt-0.5">Rates are automatically calculated at checkout according to the customer's governorate.</p>
        </div>

        <!-- Zones List -->
        <div class="space-y-4">
            @foreach($zones as $zone)
                <div class="border-2 border-black p-4 bg-gray-50 flex flex-col sm:flex-row justify-between gap-4" x-data="{ editOpen: false }">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="font-black text-sm uppercase text-black">{{ $zone->name }}</span>
                            <span class="text-[9px] font-mono font-bold bg-white border border-black px-2 py-0.5">
                                {{ number_format($zone->rate_minor / 100, 0) }} EGP
                            </span>
                            <span class="text-[9px] font-bold uppercase bg-green-100 text-green-800 border border-green-600 px-1.5 py-0.2">
                                {{ $zone->estimated_days }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-600 mt-2">
                            <span class="font-bold text-gray-800">Governorates:</span> 
                            {{ implode(', ', $zone->governorates_json ?? []) }}
                        </div>
                    </div>

                    <div class="flex sm:flex-col items-end justify-between gap-2 shrink-0">
                        <button @click="editOpen = !editOpen" class="bg-black text-white px-3 py-1.5 text-[10px] font-bold uppercase hover:bg-gray-800">
                            ✏ Edit
                        </button>
                        <form action="{{ route('admin.shipping.zones.delete', $zone->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete shipping zone {{ addslashes($zone->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-[10px] font-bold uppercase">
                                🗑 Delete
                            </button>
                        </form>
                    </div>

                    <!-- Slide-down Edit Form -->
                    <div x-show="editOpen" x-cloak class="w-full mt-4 pt-4 border-t-2 border-black space-y-3">
                        <form action="{{ route('admin.shipping.zones.update', $zone->id) }}" method="POST" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Zone Name</label>
                                    <input type="text" name="name" value="{{ $zone->name }}" required class="w-full border border-black p-2 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Delivery Rate (EGP)</label>
                                    <input type="number" step="0.01" name="rate" value="{{ $zone->rate_minor / 100 }}" required class="w-full border border-black p-2 text-xs font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Estimated Delivery Time</label>
                                    <input type="text" name="estimated_days" value="{{ $zone->estimated_days }}" required class="w-full border border-black p-2 text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Governorates / Cities (Comma-separated)</label>
                                <textarea name="governorates" rows="2" required class="w-full border border-black p-2 text-xs font-mono">{{ implode(', ', $zone->governorates_json ?? []) }}</textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="submit" class="bg-black text-white px-4 py-1.5 text-xs font-bold uppercase hover:bg-gray-800">
                                    Save Changes
                                </button>
                                <button type="button" @click="editOpen = false" class="border border-black px-3 py-1.5 text-xs font-bold uppercase">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Create New Zone -->
        <div class="pt-6 border-t-2 border-black">
            <h3 class="text-sm font-black uppercase text-black mb-3">+ Add New Shipping Zone</h3>
            <form action="{{ route('admin.shipping.zones.store') }}" method="POST" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Zone Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Red Sea & Sinai" class="w-full border-2 border-black p-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Rate (EGP) *</label>
                        <input type="number" step="0.01" name="rate" required placeholder="e.g. 95" class="w-full border-2 border-black p-2 text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Estimated Dispatch *</label>
                        <input type="text" name="estimated_days" required placeholder="e.g. 2-3 Business Days" class="w-full border-2 border-black p-2 text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-[9px] font-bold uppercase text-gray-700 mb-0.5">Governorates (Comma-separated) *</label>
                    <textarea name="governorates" rows="2" required placeholder="e.g. Hurghada, Sharm El Sheikh, Dahab, El Gouna" class="w-full border-2 border-black p-2 text-xs"></textarea>
                </div>
                <button type="submit" class="bg-black text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                    Create Shipping Zone →
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
