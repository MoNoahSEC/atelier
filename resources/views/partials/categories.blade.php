{{-- Category Rail — Minimal Horizontal Filter Bar --}}
@php
    $displayCollections = \App\Models\Collection::where('status', 'active')
        ->withCount('products')
        ->orderBy('sort_order')
        ->take(8)
        ->get();
@endphp

@if($displayCollections->isNotEmpty())
<section class="bg-white border-b border-black/10 overflow-x-auto scrollbar-none">
    <div class="max-w-7xl mx-auto px-4 sm:px-8 lg:px-14">
        <div class="flex items-stretch divide-x divide-black/10" style="min-width: max-content;">

            <a href="{{ route('collections.show', ['slug' => 'all']) }}"
               class="flex items-center gap-2 px-5 py-3.5 hover:bg-black hover:text-white transition-colors group shrink-0 {{ request()->is('collections/all') || request()->routeIs('home') ? 'bg-black/5 font-bold' : '' }}">
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.2em] text-current">All Pieces</span>
                <span class="font-mono text-[9px] text-current opacity-60">({{ \App\Models\Product::active()->count() }})</span>
            </a>

            @foreach($displayCollections as $cat)
            <a href="{{ route('collections.show', ['slug' => $cat->slug]) }}"
               class="flex items-center gap-2 px-5 py-3.5 hover:bg-black hover:text-white transition-colors group shrink-0">
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.2em] text-current truncate max-w-[140px]">
                    {{ $cat->title }}
                </span>
                <span class="font-mono text-[9px] text-current opacity-60">({{ $cat->products_count }})</span>
            </a>
            @endforeach

        </div>
    </div>
</section>
@endif
