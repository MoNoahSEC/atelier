@extends('layouts.admin')

@section('title', 'Inventory & Stock Oversight')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">STOCK ARCHITECTURE & AUDIT</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Inventory Oversight</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ $totalInventoryUnits }} total physical units available across catalog</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="border-2 border-black bg-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-100 transition-colors">
                ← Manage Products
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <!-- Low Stock Alert -->
        <div class="bg-amber-50 border-2 border-amber-600 p-5 shadow-[4px_4px_0px_0px_rgba(217,119,6,0.5)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-amber-900 mb-1">Low Stock Alerts</div>
            <div class="text-3xl font-black font-mono text-amber-900">{{ $lowStockCount }}</div>
            <div class="text-[11px] text-amber-800 mt-1">
                Items $\le$ threshold (<a href="#threshold-card" class="underline font-bold">{{ $globalThreshold }} units</a>)
            </div>
        </div>

        <!-- Out of Stock -->
        <div class="bg-red-50 border-2 border-red-600 p-5 shadow-[4px_4px_0px_0px_rgba(220,38,38,0.5)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-red-900 mb-1">Out of Stock</div>
            <div class="text-3xl font-black font-mono text-red-900">{{ $outOfStockCount }}</div>
            <div class="text-[11px] text-red-800 mt-1">Require immediate restocking</div>
        </div>

        <!-- Total Stock Value -->
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Total Warehouse Stock</div>
            <div class="text-3xl font-black font-mono text-black">{{ number_format($totalInventoryUnits) }} <span class="text-xs font-sans font-bold text-gray-500">units</span></div>
            <div class="text-[11px] text-gray-500 mt-1">Across active catalog pieces</div>
        </div>

    </div>

    <!-- Threshold Configuration Card -->
    <div id="threshold-card" class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
        <form action="{{ route('admin.inventory.update-threshold') }}" method="POST" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            @csrf
            <div>
                <h3 class="text-sm font-black uppercase text-black">Global Low-Stock Alert Threshold</h3>
                <p class="text-xs text-gray-500 mt-0.5">Trigger dashboard alerts and notifications when any product drops to or below this stock count.</p>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold font-mono">Threshold:</label>
                <input type="number" name="low_stock_threshold" value="{{ $globalThreshold }}" min="1" max="100" class="w-20 border-2 border-black p-2 text-xs font-mono font-bold">
                <button type="submit" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-800 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                    Save
                </button>
            </div>
        </form>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.inventory.index') }}" class="px-3 py-1.5 text-xs font-bold uppercase border {{ !request('filter') ? 'bg-black text-white border-black' : 'bg-white text-gray-700 border-gray-300 hover:border-black' }}">
                All ({{ \App\Models\Product::count() }})
            </a>
            <a href="{{ route('admin.inventory.index', ['filter' => 'low']) }}" class="px-3 py-1.5 text-xs font-bold uppercase border {{ request('filter') === 'low' ? 'bg-black text-white border-black' : 'bg-amber-50 text-amber-800 border-amber-300 hover:border-black' }}">
                ⚠️ Low Stock ({{ $lowStockCount }})
            </a>
            <a href="{{ route('admin.inventory.index', ['filter' => 'out']) }}" class="px-3 py-1.5 text-xs font-bold uppercase border {{ request('filter') === 'out' ? 'bg-black text-white border-black' : 'bg-red-50 text-red-800 border-red-300 hover:border-black' }}">
                ❌ Out of Stock ({{ $outOfStockCount }})
            </a>
        </div>

        <!-- Search -->
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex items-center gap-2">
            @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter SKU or Title..." class="border-2 border-black p-1.5 text-xs focus:outline-none w-48 sm:w-64">
            <button type="submit" class="bg-black text-white px-3 py-1.5 text-xs font-bold uppercase">Search</button>
        </form>
    </div>

    <!-- Inventory Table with Inline Stock Editor -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="p-3 w-16">Image</th>
                        <th class="p-3">Product Title & SKU</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Current Stock</th>
                        <th class="p-3">30d Velocity & Suggestion</th>
                        <th class="p-3">Custom Threshold</th>
                        <th class="p-3 text-right">Quick Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-medium">
                    @foreach($products as $product)
                        @php
                            $img = $product->image_url ?: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=300';
                            $imgUrl = str_starts_with($img, 'http') ? $img : url($img);
                            $isLow = $product->isLowStock();
                            $sold30d = $salesVelocity[$product->id] ?? 0;
                            $suggestedReorder = max(10, (int) round($sold30d * 1.2));
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors {{ $isLow ? 'bg-amber-50/40' : '' }}">
                            <!-- Thumbnail -->
                            <td class="p-3">
                                <div class="w-12 h-12 border border-black bg-gray-100 overflow-hidden">
                                    <img src="{{ $imgUrl }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                                </div>
                            </td>

                            <!-- Title & SKU -->
                            <td class="p-3">
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="font-black text-sm uppercase text-black hover:underline block truncate max-w-xs">
                                    {{ $product->title }}
                                </a>
                                <span class="text-[10px] font-mono text-gray-500 font-bold">SKU: {{ $product->sku }}</span>
                            </td>

                            <!-- Status -->
                            <td class="p-3">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 border {{ $product->status === 'active' ? 'bg-green-100 text-green-800 border-green-600' : 'bg-gray-100 text-gray-700 border-gray-400' }}">
                                    {{ $product->status }}
                                </span>
                            </td>

                            <!-- Current Stock -->
                            <td class="p-3">
                                @if($product->inventory <= 0)
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-black uppercase bg-red-600 text-white font-mono">
                                        0 (OUT OF STOCK)
                                    </span>
                                @elseif($isLow)
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-black uppercase bg-amber-400 text-black border border-amber-600 font-mono">
                                        ⚠️ {{ $product->inventory }} (LOW STOCK)
                                    </span>
                                @else
                                    <span class="font-mono font-bold text-green-800 text-sm">
                                        {{ $product->inventory }} units
                                    </span>
                                @endif
                            </td>

                            <!-- 30d Velocity & Smart Suggestion -->
                            <td class="p-3">
                                <div class="text-xs font-mono">
                                    <span class="font-bold text-black">{{ $sold30d }}</span>
                                    <span class="text-[10px] text-gray-500">sold in 30d</span>
                                </div>
                                @if($isLow || $product->inventory <= 10)
                                    <span class="inline-block mt-1 bg-black text-white text-[9px] font-bold uppercase px-1.5 py-0.2 font-mono">
                                        💡 Reorder ~{{ $suggestedReorder }} units
                                    </span>
                                @endif
                            </td>

                            <!-- Custom Threshold -->
                            <td class="p-3 font-mono text-gray-600 text-xs">
                                {{ $product->low_stock_threshold ? $product->low_stock_threshold . ' (custom)' : $globalThreshold . ' (default)' }}
                            </td>

                            <!-- Quick Update Form -->
                            <td class="p-3 text-right">
                                <form action="{{ route('admin.inventory.quick-update', $product->id) }}" method="POST" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    <input type="number" name="inventory" value="{{ $product->inventory }}" min="0" class="w-20 border-2 border-black p-1 text-xs font-mono font-bold text-center">
                                    <button type="submit" class="bg-black text-white px-2.5 py-1 text-[10px] font-bold uppercase hover:bg-gray-800 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t-2 border-black bg-gray-50">
            {{ $products->links() }}
        </div>
    </div>

</div>
@endsection
