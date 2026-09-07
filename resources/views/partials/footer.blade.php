<!-- Master Monochrome Footer -->
<footer class="bg-black text-white border-t border-black">
    
    <!-- Newsletter Strip -->
    <div class="border-b border-white/10 py-12 px-4 sm:px-6 lg:px-12">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-1">
                <h3 class="font-editorial font-black uppercase text-xl sm:text-2xl text-white tracking-normal">
                    Join The {{ $settings['storeName'] ?? 'ATELIER' }} Circle
                </h3>
                <p class="font-sans text-xs text-white/60">
                    Receive confidential releases, private archive drops, and 10% off your inaugural order.
                </p>
            </div>
            
            <form action="#" method="POST" class="flex w-full md:w-auto max-w-md gap-0" onsubmit="event.preventDefault(); alert('Subscribed successfully to ATELIER Circle.');">
                <input 
                    type="email" 
                    placeholder="Enter your email address..."
                    required
                    class="bg-white/10 border border-white/30 text-white placeholder-white/40 text-xs px-4 py-3.5 flex-1 focus:outline-none focus:border-white focus:bg-white/20 transition-all font-sans min-h-[44px]"
                >
                <button type="submit" class="bg-white text-black font-editorial font-black uppercase text-xs tracking-widest px-6 py-3.5 hover:bg-[#F5F5F0] transition-colors shrink-0 min-h-[44px] cursor-pointer">
                    Subscribe →
                </button>
            </form>
        </div>
    </div>

    <!-- 4-Column Navigation Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12">
        
        <!-- Brand Column -->
        <div class="space-y-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('images/atelier-circle-logo.png') }}" alt="{{ $settings['store_name'] ?? 'ATELIER' }}" class="h-10 w-10 rounded-full object-cover border border-white/20 group-hover:scale-105 transition-transform shrink-0">
                <span class="font-editorial font-black uppercase text-2xl tracking-normal text-white block">
                    {{ $settings['store_name'] ?? ($settings['storeName'] ?? 'ATELIER') }}
                </span>
            </a>
            <p class="font-sans text-xs text-white/60 leading-relaxed">
                {{ $settings['footer_brand_tagline'] ?? 'Precision-engineered everyday accessories, luxury leather architecture, and titanium EDC crafted for modern motion.' }}
            </p>
            <div class="text-[10px] font-editorial font-bold uppercase tracking-widest text-white/40 pt-2">
                STUDIO EGYPT · 2026 | ORIGIN: CAIRO
            </div>
        </div>

        <!-- Collections -->
        <div class="space-y-3">
            <h4 class="font-editorial font-bold uppercase text-xs tracking-[0.2em] text-white border-b border-white/20 pb-2">
                Archive Collections
            </h4>
            <ul class="space-y-2 text-xs font-sans text-white/70">
                <li><a href="{{ route('collections.show', ['slug' => 'all']) }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center font-bold">All Archive Pieces</a></li>
                @php $footerCols = \App\Models\Collection::where('status', 'active')->orderBy('sort_order')->take(6)->get(); @endphp
                @foreach($footerCols as $fCol)
                    <li><a href="{{ route('collections.show', ['slug' => $fCol->slug]) }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center">{{ $fCol->title }}</a></li>
                @endforeach
            </ul>
        </div>

        <!-- Concierge & Care -->
        <div class="space-y-3">
            <h4 class="font-editorial font-bold uppercase text-xs tracking-[0.2em] text-white border-b border-white/20 pb-2">
                Client Concierge
            </h4>
            <ul class="space-y-2 text-xs font-sans text-white/70">
                <li><a href="{{ route('track.order') }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center">Track Active Shipment</a></li>
                <li><a href="{{ route('account') }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center">Customer Profile</a></li>
                <li><a href="{{ route('pages.show', ['slug' => 'shipping']) }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center">Shipping Policy (3–5 Days)</a></li>
                <li><a href="{{ route('pages.show', ['slug' => 'returns']) }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center">Exchange &amp; Guarantee</a></li>
                <li><a href="{{ route('pages.show', ['slug' => 'faq']) }}" class="hover:text-white transition-colors py-1 inline-block min-h-[32px] flex items-center">Frequently Answered</a></li>
                <li>
                    <a href="mailto:{{ $settings['store_email'] ?? 'hello@atelier.eg' }}" class="hover:text-white transition-colors py-1 inline-flex items-center gap-1.5 min-h-[32px]">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                        Email Us Directly
                    </a>
                </li>
            </ul>
        </div>

        <!-- Trust & Payment -->
        <div class="space-y-4">
            <h4 class="font-editorial font-bold uppercase text-xs tracking-[0.2em] text-white border-b border-white/20 pb-2">
                Verified Transactions
            </h4>
            <div class="space-y-2 text-xs font-sans text-white/70">
                <p>Cash on Delivery available nationwide in Egypt.</p>
                <p>256-bit encrypted checkout via Paymob gateway.</p>
                <p>Accepting Visa, Mastercard, Meeza &amp; Valu installments.</p>
            </div>
            <div class="flex items-center gap-2 pt-2 text-sm">
                <span class="border border-white/20 px-2 py-1 text-[10px] font-mono">VISA</span>
                <span class="border border-white/20 px-2 py-1 text-[10px] font-mono">MASTERCARD</span>
                <span class="border border-white/20 px-2 py-1 text-[10px] font-mono">MEEZA</span>
                <span class="border border-white/20 px-2 py-1 text-[10px] font-mono">VALU</span>
                <span class="border border-white/20 px-2 py-1 text-[10px] font-mono">COD</span>
            </div>
        </div>

    </div>

    <!-- Copyright & Disclaimer -->
    <div class="border-t border-white/10 py-6 px-4 sm:px-6 lg:px-12 text-[10px] font-sans text-white/50">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
            <div>
                © {{ date('Y') }} {{ $settings['storeName'] ?? 'ATELIER' }}. All rights reserved. Crafted in Cairo, Egypt.
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('pages.show', ['slug' => 'privacy']) }}" class="hover:text-white transition-colors">Privacy Notice</a>
                <span>·</span>
                <a href="{{ route('pages.show', ['slug' => 'terms']) }}" class="hover:text-white transition-colors">Terms of Service</a>
                <span>·</span>
                <a href="{{ route('pages.show', ['slug' => 'shipping']) }}" class="hover:text-white transition-colors">Delivery Terms</a>
            </div>
        </div>
    </div>

</footer>
