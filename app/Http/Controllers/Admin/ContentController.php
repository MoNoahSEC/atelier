<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(): View
    {
        $settings = Setting::allAsMap();
        $products = Product::active()->orderBy('title')->get(['id', 'title', 'sku']);
        return view('admin.content.index', compact('settings', 'products'));
    }

    public function update(Request $request)
    {
        // 1. Handle file uploads for Logo & Favicon & Banner
        if ($request->hasFile('store_logo_file')) {
            $file = $request->file('store_logo_file');
            $filename = 'logo-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $url = '/storage/' . $path;

            MediaAsset::create([
                'type'       => 'image',
                'url'        => $url,
                'filename'   => $filename,
                'mime_type'  => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            Setting::set('store_logo', $url);
        }

        if ($request->hasFile('store_favicon_file')) {
            $file = $request->file('store_favicon_file');
            $filename = 'favicon-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $url = '/storage/' . $path;

            Setting::set('store_favicon', $url);
        }

        if ($request->hasFile('homepage_banner_file')) {
            $file = $request->file('homepage_banner_file');
            $filename = 'promo-banner-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $url = '/storage/' . $path;

            MediaAsset::create([
                'type'       => 'image',
                'url'        => $url,
                'filename'   => $filename,
                'mime_type'  => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            Setting::set('homepage_banner_image', $url);
        }

        if ($request->hasFile('homepage_banner_video_file')) {
            $request->validate([
                'homepage_banner_video_file' => ['file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'],
            ]);
            $file = $request->file('homepage_banner_video_file');
            $filename = 'promo-banner-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $url = '/storage/' . $path;

            MediaAsset::create([
                'type'       => 'video',
                'url'        => $url,
                'filename'   => $filename,
                'mime_type'  => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            Setting::set('homepage_banner_video', $url);
        }

        if ($request->boolean('remove_homepage_banner_video')) {
            Setting::set('homepage_banner_video', '');
        }

        // 1.5 Handle maintenance_mode checkbox
        Setting::set('maintenance_mode', $request->has('maintenance_mode') ? '1' : '0');

        // 2. Iterate over all text / select settings
        $fields = [
            // Store Identity
            'store_name',
            'storeName',
            'store_tagline',
            'storefront_lang',
            'support_email',
            'support_phone',
            'currency',
            'free_shipping_threshold',

            // Announcement Bar
            'announcement_bar_left',
            'announcement_bar_center',
            'announcement_bar_right',

            // Homepage Hero
            'homeHeroBadge',
            'homeHeroTitle',
            'homeHeroSubtitle',
            'homeHeroCtaText',
            'homeHeroCtaLink',
            'homeHeroSecondaryCta',
            'homeHeroPriceBadge',

            // Promo Banner
            'homepage_banner_title',
            'homepage_banner_subtitle',
            'homepage_banner_link',
            'homepage_banner_height',
            'homepage_banner_position',
            'homepage_banner_fit',
            'homepage_banner_zoom',
            'homepage_banner_overlay',

            // Section Titles
            'section_categories_title',
            'section_categories_sub',
            'section_featured_title',
            'section_showcase_title',
            'featured_bestseller_product_id',
            'guarantee_title',
            'guarantee_subtitle',

            // Footer
            'footer_brand_tagline',
            'footer_copyright',
            'footer_shipping_note',
            'social_instagram',
            'social_facebook',
            'social_tiktok',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field));
                // Keep storeName and store_name in sync
                if ($field === 'store_name') {
                    Setting::set('storeName', $request->input($field));
                }
            }
        }

        // 3. Purge all cache so changes reflect instantly
        Cache::flush();

        AuditLog::log('settings.update', 'setting', 0, 'Updated store content, banner, and branding settings.');

        return back()->with('success', 'Site content, branding, and settings saved successfully! Changes are live immediately.');
    }
}
