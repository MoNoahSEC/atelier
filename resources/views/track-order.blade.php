@extends('layouts.app')

@section('title', 'Track Order — ' . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-16 lg:py-24">
    <div class="max-w-xl mx-auto px-4 sm:px-6">
        <div class="border-2 border-black bg-white p-8 sm:p-12 shadow-[12px_12px_0px_0px_rgba(0,0,0,1)]">
            <div class="border-b border-black pb-6 mb-8 text-center">
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black/60 block mb-1">
                    CONCIERGE DISPATCH
                </span>
                <h1 class="font-editorial font-black text-2xl sm:text-3xl uppercase tracking-normal text-black">
                    Track Active Shipment
                </h1>
                <p class="font-sans text-xs text-black/70 mt-2">
                    Enter your Order Number or phone number to check live courier dispatch status.
                </p>
            </div>

            <form action="#" method="GET" class="space-y-4" onsubmit="event.preventDefault(); alert('Order located: In preparation for courier pickup. Usually delivers from 3 to 5 business days (Maximum 4 days from order date).');">
                <div>
                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">
                        Order Number (e.g., #ORD-1001)
                    </label>
                    <input type="text" required placeholder="ORD-XXXX" class="w-full border border-black p-3.5 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none uppercase font-editorial font-bold">
                </div>
                <div>
                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">
                        Phone Number
                    </label>
                    <input type="tel" required placeholder="010XXXXXXXX" class="w-full border border-black p-3.5 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none">
                </div>

                <button type="submit" class="btn-luxury w-full py-4 text-center text-xs tracking-[0.2em] mt-2 block">
                    Locate Shipment →
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
