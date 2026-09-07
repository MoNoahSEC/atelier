@extends('layouts.app')

@section('title', ($pageTitle ?? 'Client Care') . ' — ' . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <div class="border-2 border-black bg-white p-8 sm:p-14 shadow-[12px_12px_0px_0px_rgba(0,0,0,1)] space-y-6">
            <div class="border-b border-black pb-6">
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black/60 block mb-1">
                    CLIENT CARE & PROTOCOLS
                </span>
                <h1 class="font-editorial font-black text-3xl sm:text-4xl uppercase tracking-normal text-black">
                    {{ $pageTitle ?? 'Information' }}
                </h1>
            </div>

            <div class="prose max-w-none font-sans text-xs sm:text-sm text-black/80 leading-relaxed space-y-4">
                {!! $pageContent ?? '<p>For any concierge inquiries, reach us at support@atelier.eg or via official WhatsApp concierge.</p>' !!}
            </div>

            <div class="pt-6 border-t border-black/10">
                <a href="{{ route('home') }}" class="btn-luxury inline-block px-6 py-3.5 text-xs">
                    ← Return to Homepage
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
