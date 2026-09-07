@extends('layouts.admin')

@section('title', 'Analytics & Traffic Intelligence')

@section('content')
<div class="space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">REAL-TIME INTELLIGENCE</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Analytics & Traffic</h1>
            <p class="text-xs text-gray-500 mt-0.5">Visitor intelligence, product views, and customer growth trends</p>
        </div>
        <!-- Date Range Filter -->
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold uppercase text-gray-500">Range:</span>
            <div class="flex gap-1">
                <a href="{{ route('admin.analytics.index', ['range' => '7']) }}" class="px-2.5 py-1 text-[10px] font-bold uppercase border {{ $range === '7' ? 'bg-black text-white border-black' : 'bg-white text-black border-gray-300 hover:border-black' }}">
                    7 Days
                </a>
                <a href="{{ route('admin.analytics.index', ['range' => '30']) }}" class="px-2.5 py-1 text-[10px] font-bold uppercase border {{ $range === '30' ? 'bg-black text-white border-black' : 'bg-white text-black border-gray-300 hover:border-black' }}">
                    30 Days
                </a>
                <a href="{{ route('admin.analytics.index', ['range' => '365']) }}" class="px-2.5 py-1 text-[10px] font-bold uppercase border {{ $range === '365' ? 'bg-black text-white border-black' : 'bg-white text-black border-gray-300 hover:border-black' }}">
                    All Time
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Views Today</div>
            <div class="text-3xl font-black font-mono text-black">{{ number_format($viewsToday) }}</div>
        </div>
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Uniques Today</div>
            <div class="text-3xl font-black font-mono text-black">{{ number_format($visitorsToday) }}</div>
        </div>
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Views This Month</div>
            <div class="text-3xl font-black font-mono text-black">{{ number_format($viewsMonth) }}</div>
            <div class="text-[10px] text-gray-500 mt-1">{{ number_format($visitorsMonth) }} unique visitors</div>
        </div>
        <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">All-Time Views</div>
            <div class="text-3xl font-black font-mono text-black">{{ number_format($viewsTotal) }}</div>
            <div class="text-[10px] text-gray-500 mt-1">{{ number_format($visitorsTotal) }} unique visitors total</div>
        </div>
    </div>

    <!-- Page Type Breakdown -->
    @if($pageTypeBreakdown->count() > 0)
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2 mb-4">Traffic Breakdown by Page Type</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach(['homepage', 'collection', 'product', 'checkout'] as $type)
                    @php $typeData = $pageTypeBreakdown->get($type); @endphp
                    <div class="border border-black p-4 text-center bg-gray-50">
                        <div class="text-xl font-black font-mono text-black">{{ $typeData ? number_format($typeData->count) : 0 }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mt-1">
                            {{ ucfirst($type) }} {{ $type === 'homepage' ? '🏠' : ($type === 'product' ? '🛍' : ($type === 'collection' ? '📁' : '🛒')) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Top Viewed Products -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
        <div class="border-b-2 border-black pb-3 mb-4">
            <h2 class="text-xl font-black uppercase tracking-tight text-black">Most Viewed Products</h2>
            <p class="text-xs text-gray-500 mt-0.5">Ranked by page view count for selected period</p>
        </div>

        @if($topProducts->count() > 0)
            <div class="space-y-3">
                @foreach($topProducts as $index => $item)
                    <div class="flex items-center gap-4 p-3 border border-black bg-gray-50 hover:bg-white transition-colors">
                        <!-- Rank -->
                        <div class="w-8 h-8 border-2 border-black flex items-center justify-center shrink-0 font-black text-sm {{ $index < 3 ? 'bg-black text-white' : 'bg-white' }}">
                            {{ $index + 1 }}
                        </div>

                        <!-- Image -->
                        @if($item->image_url)
                            <div class="w-12 h-12 border border-black bg-gray-100 overflow-hidden shrink-0">
                                @php $src = str_starts_with($item->image_url, 'http') ? $item->image_url : url($item->image_url); @endphp
                                <img src="{{ $src }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                            </div>
                        @endif

                        <!-- Product Info -->
                        <div class="flex-1 min-w-0">
                            <div class="font-black text-sm uppercase text-black truncate">{{ $item->title }}</div>
                            <div class="text-[10px] text-gray-500 font-mono">SKU: {{ $item->sku }} · {{ number_format($item->retail_price, 0) }} EGP · Stock: {{ $item->inventory }}</div>
                        </div>

                        <!-- Views data -->
                        <div class="text-right shrink-0">
                            <div class="font-black font-mono text-lg text-black">{{ number_format($item->views_count) }}</div>
                            <div class="text-[10px] text-gray-500">views</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ number_format($item->unique_visitors) }} uniq.</div>
                        </div>

                        <!-- Edit Link -->
                        <a href="{{ route('admin.products.edit', $item->product_id) }}" class="shrink-0 border border-black bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase hover:bg-black hover:text-white transition-colors">
                            Edit →
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-10 text-center text-gray-400 border border-dashed border-gray-300">
                <span class="block text-3xl mb-2">📈</span>
                <p class="text-xs font-bold uppercase">Analytics data is accumulating...</p>
                <p class="text-[10px] text-gray-400 mt-1">Visit the storefront to generate initial tracking data.</p>
            </div>
        @endif
    </div>

    <!-- Customer Signups Trend -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
        <div class="border-b-2 border-black pb-3 mb-4">
            <h2 class="text-xl font-black uppercase tracking-tight text-black">New Customer Signups — Last 14 Days</h2>
        </div>

        @if($signupsTrend->count() > 0)
            @php $maxSignups = $signupsTrend->max('count') ?: 1; @endphp
            <div class="flex items-end gap-1 h-24">
                @foreach($signupsTrend as $day)
                    @php $barH = max(4, (int) round(($day->count / $maxSignups) * 96)); @endphp
                    <div class="flex flex-col items-center flex-1 gap-1" title="{{ $day->date }}: {{ $day->count }} signups">
                        <span class="text-[8px] font-mono text-gray-600">{{ $day->count }}</span>
                        <div class="w-full bg-black" style="height: {{ $barH }}px;"></div>
                        <span class="text-[8px] font-mono text-gray-500 rotate-90 mt-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-400 py-6 text-center">No signup data in the last 14 days.</p>
        @endif
    </div>

</div>
@endsection
