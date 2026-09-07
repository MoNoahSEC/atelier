@extends('layouts.admin')

@section('title', 'Executive Dashboard')

@section('content')
<div class="space-y-8">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">EXECUTIVE OVERVIEW</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Store Dashboard</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.create') }}" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-transform active:translate-y-0.5">
                + Add Product
            </a>
            <a href="{{ route('admin.content.index') }}" class="border-2 border-black bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-gray-100 transition-colors">
                ⚙ Site Settings
            </a>
        </div>
    </div>

    <!-- Live presence: small, useful and privacy-conscious. Refreshes with the dashboard. -->
    <div class="rounded-2xl border border-emerald-300 bg-emerald-50/70 p-4 sm:p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <div><p class="text-[10px] font-bold uppercase tracking-[.16em] text-emerald-800">Live right now</p><h2 class="text-lg font-black text-black">{{ $onlineVisitors }} visitor{{ $onlineVisitors === 1 ? '' : 's' }} browsing the store</h2></div>
            </div>
            <span class="text-[10px] text-emerald-800/75">Active in the last 90 seconds · refresh this page for the latest list</span>
        </div>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-xl bg-white/80 border border-emerald-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-black/50">Admins online ({{ $onlineAdmins->count() }})</p>
                <p class="mt-1 text-xs font-semibold text-black">{{ $onlineAdmins->pluck('display_name')->filter()->implode(' · ') ?: 'No admin is active' }}</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-emerald-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-black/50">Signed-in customers ({{ $onlineCustomers->count() }})</p>
                <p class="mt-1 text-xs font-semibold text-black">{{ $onlineCustomers->pluck('display_name')->filter()->implode(' · ') ?: 'Anonymous visitors only' }}</p>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Revenue Today -->
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-widest">Revenue Today</span>
                <span class="text-lg">💵</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black tracking-tight text-black font-mono">
                {{ number_format($revenueToday / 100, 0) }} <span class="text-xs font-sans font-bold">EGP</span>
            </div>
            <div class="text-[11px] text-gray-500 mt-2 font-medium">
                This Month: <strong class="text-black font-mono">{{ number_format($revenueMonth / 100, 0) }} EGP</strong>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-widest">Orders</span>
                <span class="text-lg">📦</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black tracking-tight text-black font-mono">
                {{ $totalOrders }}
            </div>
            <div class="text-[11px] text-gray-500 mt-2 font-medium">
                <span class="inline-block w-2 h-2 rounded-full {{ $pendingOrders > 0 ? 'bg-amber-500 animate-pulse' : 'bg-green-500' }} mr-1"></span>
                <strong>{{ $pendingOrders }}</strong> pending action
            </div>
        </div>

        <!-- Active Inventory -->
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-widest">Products</span>
                <span class="text-lg">🛍</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black tracking-tight text-black font-mono">
                {{ $totalProducts }}
            </div>
            <div class="text-[11px] text-gray-500 mt-2 font-medium">
                @if($lowStockCount > 0)
                    <span class="text-red-600 font-bold">⚠️ {{ $lowStockCount }} low stock alerts</span>
                @else
                    <span class="text-green-600 font-bold">✓ Inventory healthy</span>
                @endif
            </div>
        </div>

        <!-- Visitors & Traffic -->
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-widest">Today's Traffic</span>
                <span class="text-lg">📈</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black tracking-tight text-black font-mono">
                {{ $visitorsToday }} <span class="text-xs font-sans font-bold text-gray-500">uniques</span>
            </div>
            <div class="text-[11px] text-gray-500 mt-2 font-medium">
                <strong>{{ $viewsToday }}</strong> total page views today
            </div>
        </div>

    </div>

    <!-- ─── Interactive Sales Charts (Chart.js) ────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 30-Day Revenue Line Chart (2 Cols) -->
        <div class="lg:col-span-2 bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <div>
                    <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">FINANCIAL TRAJECTORY</span>
                    <h2 class="text-base font-black uppercase tracking-tight text-black">Revenue Trend (Last 30 Days)</h2>
                </div>
                <span class="text-xs font-mono font-bold text-gray-600">Daily EGP</span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Best-Selling Products Bar Chart (1 Col) -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">DEMAND VELOCITY</span>
                <h2 class="text-base font-black uppercase tracking-tight text-black">Top Selling Pieces</h2>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="bestsellersChart"></canvas>
            </div>
        </div>

    </div>

    <!-- Low Stock Alert Box (Conditional) -->
    @if($lowStockProducts->count() > 0)
        <div class="bg-amber-50 border-2 border-amber-600 p-5 shadow-[4px_4px_0px_0px_rgba(217,119,6,0.5)]">
            <div class="flex items-center justify-between mb-3 border-b border-amber-300 pb-2">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⚠️</span>
                    <h3 class="font-black text-sm uppercase tracking-wider text-amber-900">Low Stock Alert ({{ $lowStockProducts->count() }} items ≤ 5 units)</h3>
                </div>
                <a href="{{ route('admin.inventory.index') }}" class="text-xs font-bold uppercase underline text-amber-900 hover:text-black">Inventory Manager →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($lowStockProducts->take(6) as $lowP)
                    <div class="flex items-center justify-between bg-white border border-amber-400 p-2.5 text-xs">
                        <div class="truncate mr-2">
                            <span class="font-bold text-black block truncate">{{ $lowP->title }}</span>
                            <span class="text-[10px] font-mono text-gray-500">SKU: {{ $lowP->sku }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-block font-mono font-bold px-2 py-0.5 {{ $lowP->inventory === 0 ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-900 border border-amber-400' }}">
                                {{ $lowP->inventory }} left
                            </span>
                            <a href="{{ route('admin.products.edit', $lowP->id) }}" class="block text-[10px] text-black underline font-bold mt-1">Edit</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent Orders Section -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
        <div class="flex items-center justify-between border-b-2 border-black pb-4 mb-4">
            <div>
                <h2 class="text-xl font-black uppercase tracking-tight text-black">Recent Orders</h2>
                <p class="text-xs text-gray-500 mt-0.5">Latest transactions and dispatch updates</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="border border-black px-3 py-1.5 text-xs font-bold uppercase tracking-wider hover:bg-black hover:text-white transition-colors">
                View All Orders →
            </a>
        </div>

        @if($recentOrders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="p-3">Order #</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Items</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @foreach($recentOrders as $order)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-mono font-bold text-black">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="hover:underline">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-black">{{ $order->customer_name ?: 'Guest Connoisseur' }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono">{{ $order->customer_email }}</div>
                                </td>
                                <td class="p-3 text-gray-600">
                                    {{ $order->created_at->format('M d, Y') }}
                                    <span class="block text-[10px] text-gray-400 font-mono">{{ $order->created_at->format('H:i') }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold">{{ $order->items->sum('quantity') }}</span> item(s)
                                </td>
                                <td class="p-3 font-mono font-black text-sm text-black">
                                    {{ number_format($order->total_price_minor / 100, 0) }} EGP
                                </td>
                                <td class="p-3">
                                    @php
                                        $badgeClass = match($order->status) {
                                            'delivered'  => 'bg-green-100 text-green-800 border-green-600',
                                            'shipped'    => 'bg-blue-100 text-blue-800 border-blue-600',
                                            'processing' => 'bg-purple-100 text-purple-800 border-purple-600',
                                            'cancelled'  => 'bg-red-100 text-red-800 border-red-600',
                                            default      => 'bg-amber-100 text-amber-800 border-amber-600',
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="border border-black px-2.5 py-1 text-[10px] font-bold uppercase hover:bg-black hover:text-white transition-colors">
                                        Inspect →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-12 text-center text-gray-500 border border-dashed border-gray-300">
                <span class="text-3xl block mb-2">📦</span>
                <p class="font-bold text-sm uppercase tracking-wider">No orders recorded yet</p>
                <p class="text-xs text-gray-400 mt-1">Live customer orders will appear here automatically.</p>
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Revenue 30-Day Line Chart
    const ctxRevenue = document.getElementById('revenueChart');
    if (ctxRevenue) {
        new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Revenue (EGP)',
                    data: {!! json_encode($chartRevenue) !!},
                    borderColor: '#000000',
                    backgroundColor: 'rgba(0, 0, 0, 0.05)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.2,
                    pointBackgroundColor: '#000000',
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        grid: { color: '#e5e5e5' },
                        ticks: {
                            font: { size: 10, family: 'monospace' },
                            callback: function(val) { return val.toLocaleString() + ' EGP'; }
                        }
                    }
                }
            }
        });
    }

    // 2. Best Sellers Horizontal Bar Chart
    const ctxBestsellers = document.getElementById('bestsellersChart');
    if (ctxBestsellers) {
        new Chart(ctxBestsellers, {
            type: 'bar',
            data: {
                labels: {!! json_encode($bestsellerLabels) !!},
                datasets: [{
                    label: 'Units Sold',
                    data: {!! json_encode($bestsellerUnits) !!},
                    backgroundColor: '#111827',
                    borderRadius: 2,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: '#e5e5e5' },
                        ticks: { font: { size: 10, family: 'monospace' }, precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 10 },
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                return label.length > 18 ? label.substr(0, 18) + '...' : label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
