@extends('layouts.admin')

@section('title', 'Product Reviews Moderation')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">REVIEW MODERATION</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Product Reviews</h1>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 border-b-2 border-black pb-3">
        <a href="{{ route('admin.reviews.index') }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ !request('status') ? 'bg-black text-white border-black' : 'bg-white border-gray-300 hover:border-black' }}">
            All ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ request('status') === 'pending' ? 'bg-black text-white border-black' : 'bg-amber-50 text-amber-800 border-amber-300 hover:border-black' }}">
            Pending ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ request('status') === 'approved' ? 'bg-black text-white border-black' : 'bg-green-50 text-green-800 border-green-300 hover:border-black' }}">
            Approved ({{ $counts['approved'] }})
        </a>
    </div>

    <!-- Reviews List -->
    @if($reviews->count() > 0)
        <div class="space-y-3">
            @foreach($reviews as $review)
                <div class="bg-white border-2 border-black p-5 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex flex-col sm:flex-row gap-4 justify-between">

                    <!-- Review Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap mb-2">
                            <!-- Stars -->
                            <div class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-sm {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-300' }}">★</span>
                                @endfor
                            </div>

                            <!-- Status badge -->
                            @if($review->is_approved)
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.2 bg-green-100 text-green-800 border border-green-600">Published</span>
                            @else
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.2 bg-amber-100 text-amber-800 border border-amber-600">Pending Approval</span>
                            @endif

                            @if($review->is_verified_purchase)
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.2 bg-blue-100 text-blue-800 border border-blue-600">✓ Verified Purchase</span>
                            @endif
                        </div>

                        <!-- Author & Product -->
                        <div class="flex items-center gap-3 mb-2">
                            <span class="font-black text-sm text-black">{{ $review->reviewer_name ?? 'Anonymous' }}</span>
                            <span class="text-[10px] text-gray-500">·</span>
                            @if($review->product)
                                <a href="{{ route('admin.products.edit', $review->product->id) }}" class="text-[10px] font-bold text-gray-600 hover:text-black uppercase underline">
                                    {{ $review->product->title }}
                                </a>
                            @endif
                            <span class="text-[10px] text-gray-400 font-mono">{{ $review->created_at->format('M d, Y') }}</span>
                        </div>

                        @if($review->title)
                            <h3 class="font-bold text-sm text-black mb-1">{{ $review->title }}</h3>
                        @endif
                        <p class="text-xs text-gray-700 leading-relaxed">{{ $review->body ?? $review->content ?? 'No review text provided.' }}</p>
                    </div>

                    <!-- Moderation Actions -->
                    <div class="flex sm:flex-col gap-2 shrink-0 items-start sm:items-end justify-start sm:justify-start">
                        @if(!$review->is_approved)
                            <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-3 py-1.5 text-[10px] font-bold uppercase hover:bg-green-700 w-full text-right">
                                    ✓ Approve
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="border border-gray-400 text-gray-700 px-3 py-1.5 text-[10px] font-bold uppercase hover:bg-gray-100 w-full text-right">
                                    Hide
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete this review?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="border border-red-600 text-red-600 px-3 py-1.5 text-[10px] font-bold uppercase hover:bg-red-50 w-full text-right">
                                🗑 Delete
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $reviews->links() }}
        </div>
    @else
        <div class="py-16 text-center text-gray-400 bg-white border-2 border-black">
            <span class="text-4xl block mb-2">⭐</span>
            <h3 class="font-bold text-sm uppercase tracking-wider">No reviews in this filter</h3>
        </div>
    @endif

</div>
@endsection
