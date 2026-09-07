@extends('layouts.admin')

@section('title', 'Site Content & Branding Control')

@section('content')
<div class="max-w-5xl space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">VISUAL IDENTITY & CONTENT SYSTEM</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Site Content & Branding</h1>
            <p class="text-xs text-gray-500 mt-0.5">Every change here goes live on the store immediately — no deployment needed.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" class="border border-black bg-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-100">
            ↗ Preview Storefront
        </a>
    </div>

    <!-- ─── 0. System Maintenance Mode Control ───────────────────────── -->
    <div class="bg-amber-50 border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-lg">🛡️</span>
                    <h2 class="text-base font-black uppercase tracking-tight text-black">Storefront Maintenance Mode</h2>
                </div>
                <p class="text-xs text-gray-700 mt-0.5">
                    When enabled, visitors are shown the "Scheduled Archive Maintenance" page. Logged-in administrators can still browse and test the store normally.
                </p>
            </div>
            <div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" {{ in_array((string)($settings['maintenance_mode'] ?? '0'), ['1', 'true', 'on'], true) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-black"></div>
                    <span class="ml-3 text-xs font-black uppercase text-black">Enable Maintenance</span>
                </label>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.content.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        <!-- ─── 1. Store Identity ─────────────────────────────────────── -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b-2 border-black pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">SECTION 1</span>
                <h2 class="text-lg font-black uppercase tracking-tight text-black">Store Identity & Branding</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Store Name *</label>
                    <input type="text" name="store_name" value="{{ $settings['store_name'] ?? $settings['storeName'] ?? 'ATELIER' }}" class="w-full border-2 border-black p-2.5 text-sm font-black uppercase tracking-widest focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Store Tagline</label>
                    <input type="text" name="store_tagline" value="{{ $settings['store_tagline'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}" placeholder="concierge@yourstore.com" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Support Phone</label>
                    <input type="text" name="support_phone" value="{{ $settings['support_phone'] ?? '' }}" placeholder="+20 100 000 0000" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Store Logo</label>
                    @if(!empty($settings['store_logo']))
                        <img src="{{ url($settings['store_logo']) }}" alt="Current Store Logo" class="h-12 mb-2 object-contain border border-black p-1">
                    @endif
                    <input type="file" name="store_logo_file" accept="image/*" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                    <span class="text-[9px] text-gray-400">Transparent PNG or SVG recommended</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Browser Favicon</label>
                    @if(!empty($settings['store_favicon']))
                        <img src="{{ url($settings['store_favicon']) }}" alt="Favicon" class="h-8 w-8 mb-2 object-contain border border-black p-1">
                    @endif
                    <input type="file" name="store_favicon_file" accept="image/*" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                    <span class="text-[9px] text-gray-400">32×32 or 64×64 .ico/.png</span>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Free Shipping Threshold (EGP)</label>
                    <input type="number" name="free_shipping_threshold" value="{{ $settings['free_shipping_threshold'] ?? '2500' }}" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>

            <!-- Language Setting -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-black/10">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">🌐 Storefront Language / لغة المتجر</label>
                    <select name="storefront_lang" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none bg-white cursor-pointer">
                        <option value="en" {{ ($settings['storefront_lang'] ?? 'en') === 'en' ? 'selected' : '' }}>🇬🇧 English (LTR)</option>
                        <option value="ar" {{ ($settings['storefront_lang'] ?? 'en') === 'ar' ? 'selected' : '' }}>🇪🇬 العربية — Arabic (RTL) · Noto Naskh</option>
                    </select>
                    <p class="text-[9px] text-gray-400 mt-1">Switches the storefront font, direction (RTL/LTR), and layout to the selected language.</p>
                </div>
            </div>
        </div>

        <!-- ─── 2. Announcement Bar ─────────────────────────────────── -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b-2 border-black pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">SECTION 2</span>
                <h2 class="text-lg font-black uppercase tracking-tight text-black">Announcement Bar</h2>
                <p class="text-[10px] text-gray-500">The scrolling banner at the very top of every page</p>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Left Message</label>
                <input type="text" name="announcement_bar_left" value="{{ $settings['announcement_bar_left'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Center Message</label>
                <input type="text" name="announcement_bar_center" value="{{ $settings['announcement_bar_center'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Right Message</label>
                <input type="text" name="announcement_bar_right" value="{{ $settings['announcement_bar_right'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>
        </div>

        <!-- ─── 3. Hero Section ─────────────────────────────────────── -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b-2 border-black pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">SECTION 3</span>
                <h2 class="text-lg font-black uppercase tracking-tight text-black">Homepage Hero Section</h2>
                <p class="text-[10px] text-gray-500">The large, prominent hero panel on the main homepage</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Badge Text (small tag above title)</label>
                    <input type="text" name="homeHeroBadge" value="{{ $settings['homeHeroBadge'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs font-mono uppercase focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Price / Warranty Badge</label>
                    <input type="text" name="homeHeroPriceBadge" value="{{ $settings['homeHeroPriceBadge'] ?? '' }}" placeholder="From 540 EGP · 2-Year Warranty" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Hero Headline (Main Title)</label>
                <input type="text" name="homeHeroTitle" value="{{ $settings['homeHeroTitle'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-sm font-black uppercase focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Hero Subtitle / Description</label>
                <textarea name="homeHeroSubtitle" rows="2" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">{{ $settings['homeHeroSubtitle'] ?? '' }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Primary CTA Button Text</label>
                    <input type="text" name="homeHeroCtaText" value="{{ $settings['homeHeroCtaText'] ?? 'Explore Catalog' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">CTA Button Link</label>
                    <input type="text" name="homeHeroCtaLink" value="{{ $settings['homeHeroCtaLink'] ?? '/collections/all' }}" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>
        </div>

        <!-- ─── 4. Promo Banner Section ─────────────────────────────── -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b-2 border-black pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">SECTION 4</span>
                <h2 class="text-lg font-black uppercase tracking-tight text-black">Promotional Banner / Campaign Media</h2>
                <p class="text-[10px] text-gray-500">Use an optimized image or a muted looping video below the hero</p>
            </div>

            <!-- Current Banner Preview -->
            @if(!empty($settings['homepage_banner_image']))
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Current Banner Image</label>
                    <div class="relative w-full h-32 border-2 border-black overflow-hidden">
                        <img src="{{ str_starts_with($settings['homepage_banner_image'], 'http') ? $settings['homepage_banner_image'] : url($settings['homepage_banner_image']) }}" alt="Current Promo Banner" class="w-full h-full object-cover">
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Upload New Banner Image</label>
                <input type="file" name="homepage_banner_file" accept="image/jpeg,image/png,image/webp" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                <span class="text-[9px] text-gray-400">Recommended: 1800×700px WebP/JPEG, Max 8MB</span>
            </div>

            @if(!empty($settings['homepage_banner_video']))
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Current Banner Video</label>
                    <video controls preload="metadata" class="w-full h-32 border-2 border-black object-cover">
                        <source src="{{ str_starts_with($settings['homepage_banner_video'], 'http') ? $settings['homepage_banner_video'] : url($settings['homepage_banner_video']) }}">
                    </video>
                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-bold cursor-pointer"><input type="checkbox" name="remove_homepage_banner_video" value="1"> Remove video and use the image</label>
                </div>
            @endif

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Upload Banner Video</label>
                <input type="file" name="homepage_banner_video_file" accept="video/mp4,video/webm,video/quicktime" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                <span class="text-[9px] text-gray-400">MP4 or WebM recommended, muted loop, 8–15 seconds, maximum 50MB. Video automatically uses the image as a fallback poster.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Banner Headline</label>
                    <input type="text" name="homepage_banner_title" value="{{ $settings['homepage_banner_title'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs font-black uppercase focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Banner CTA Link</label>
                    <input type="text" name="homepage_banner_link" value="{{ $settings['homepage_banner_link'] ?? '/collections/all' }}" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Banner Subtitle</label>
                <input type="text" name="homepage_banner_subtitle" value="{{ $settings['homepage_banner_subtitle'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Height (CSS)</label>
                    <input type="text" name="homepage_banner_height" value="{{ $settings['homepage_banner_height'] ?? '48vh' }}" placeholder="e.g. 48vh, 600px" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Overlay Strength</label>
                    <select name="homepage_banner_overlay" class="w-full border-2 border-black p-2.5 text-xs bg-white focus:outline-none">
                        <option value="none" {{ ($settings['homepage_banner_overlay'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                        <option value="light" {{ ($settings['homepage_banner_overlay'] ?? '') === 'light' ? 'selected' : '' }}>Light</option>
                        <option value="medium" {{ ($settings['homepage_banner_overlay'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="dark" {{ ($settings['homepage_banner_overlay'] ?? '') === 'dark' ? 'selected' : '' }}>Dark</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Image Fit</label>
                    <select name="homepage_banner_fit" class="w-full border-2 border-black p-2.5 text-xs bg-white focus:outline-none">
                        <option value="cover" {{ ($settings['homepage_banner_fit'] ?? 'cover') === 'cover' ? 'selected' : '' }}>Cover</option>
                        <option value="contain" {{ ($settings['homepage_banner_fit'] ?? '') === 'contain' ? 'selected' : '' }}>Contain</option>
                        <option value="fill" {{ ($settings['homepage_banner_fit'] ?? '') === 'fill' ? 'selected' : '' }}>Fill</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Zoom % (100 = normal)</label>
                    <input type="number" name="homepage_banner_zoom" value="{{ $settings['homepage_banner_zoom'] ?? '100' }}" min="80" max="200" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>
        </div>

        <!-- ─── 5. Section Titles ────────────────────────────────────── -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b-2 border-black pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">SECTION 5</span>
                <h2 class="text-lg font-black uppercase tracking-tight text-black">Section Titles & Trust Bar</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Collections / Categories Section Title</label>
                    <input type="text" name="section_categories_title" value="{{ $settings['section_categories_title'] ?? 'Curated Collections' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Categories Section Subtitle</label>
                    <input type="text" name="section_categories_sub" value="{{ $settings['section_categories_sub'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Featured Products Section Title</label>
                    <input type="text" name="section_featured_title" value="{{ $settings['section_featured_title'] ?? 'Featured Products' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Best seller product</label>
                    <select name="featured_bestseller_product_id" class="w-full border-2 border-black bg-white p-2.5 text-xs focus:outline-none">
                        <option value="">No best seller badge</option>
                        @foreach($products as $settingsProduct)
                            <option value="{{ $settingsProduct->id }}" @selected((string) ($settings['featured_bestseller_product_id'] ?? '') === (string) $settingsProduct->id)>{{ $settingsProduct->title }} · {{ $settingsProduct->sku }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-gray-500">Only this item receives the Best seller badge and featured-home link.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Guarantee / Trust Bar Title</label>
                    <input type="text" name="guarantee_title" value="{{ $settings['guarantee_title'] ?? 'The ATELIER Guarantee' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Guarantee / Trust Bar Subtitle</label>
                <input type="text" name="guarantee_subtitle" value="{{ $settings['guarantee_subtitle'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
            </div>
        </div>

        <!-- ─── 6. Footer ─────────────────────────────────────────────── -->
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="border-b-2 border-black pb-2">
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">SECTION 6</span>
                <h2 class="text-lg font-black uppercase tracking-tight text-black">Footer & Social Media</h2>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Footer Brand Tagline</label>
                <textarea name="footer_brand_tagline" rows="2" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">{{ $settings['footer_brand_tagline'] ?? '' }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Footer Copyright Line</label>
                    <input type="text" name="footer_copyright" value="{{ $settings['footer_copyright'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Shipping Note in Footer</label>
                    <input type="text" name="footer_shipping_note" value="{{ $settings['footer_shipping_note'] ?? '' }}" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Instagram URL</label>
                    <input type="url" name="social_instagram" value="{{ $settings['social_instagram'] ?? '' }}" placeholder="https://instagram.com/..." class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Facebook URL</label>
                    <input type="url" name="social_facebook" value="{{ $settings['social_facebook'] ?? '' }}" placeholder="https://facebook.com/..." class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">TikTok URL</label>
                    <input type="url" name="social_tiktok" value="{{ $settings['social_tiktok'] ?? '' }}" placeholder="https://tiktok.com/@..." class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="sticky bottom-4 bg-white border-2 border-black p-4 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] flex items-center justify-between gap-4">
            <p class="text-xs text-gray-600">
                <strong>⚡ Instant Publish:</strong> Changes go live on every page of the store the moment you save — no cache clearing required.
            </p>
            <button type="submit" class="bg-black text-white px-8 py-3.5 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] whitespace-nowrap transition-transform active:translate-y-0.5">
                Save All Changes →
            </button>
        </div>

    </form>
</div>
@endsection
