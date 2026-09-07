<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Default fallbacks for all store settings across the platform.
     */
    public static function defaults(): array
    {
        return [
            // Store Identity & Branding
            'store_name'               => 'ATELIER',
            'storeName'                => 'ATELIER',
            'store_tagline'            => 'Bespoke Luxury Leather Goods & Aerospace EDC Essentials',
            'store_logo'               => '',
            'store_favicon'            => '',
            'support_email'            => 'concierge@atelier.eg',
            'support_phone'            => '+20 100 000 0000',
            'currency'                 => 'EGP',
            'free_shipping_threshold'  => '2500',

            // Announcement Bar (Dynamic)
            'announcement_bar_left'    => 'COMPLIMENTARY 24H EXPRESS DELIVERY ACROSS EGYPT ON ORDERS OVER 2,500 EGP',
            'announcement_bar_center'  => 'EGYPTIAN LUXURY CRAFTSMANSHIP & ITALIAN TUSCAN LEATHER',
            'announcement_bar_right'   => 'CASH ON DELIVERY & PAYMOB ENCRYPTED CHECKOUT',

            // Homepage Hero Section
            'homeHeroBadge'            => 'BESPOKE LUXURY EDC',
            'homeHeroTitle'            => 'THE ABSOLUTE WALLET EXPERIENCE',
            'homeHeroSubtitle'         => 'Handcrafted full-grain Italian leather, aerospace titanium hardware, and minimalist RFID architecture.',
            'homeHeroCtaText'          => 'Explore Catalog',
            'homeHeroCtaLink'          => '/collections/all',
            'homeHeroSecondaryCta'     => 'View Featured Piece',
            'homeHeroPriceBadge'       => 'From 540 EGP · 2-Year Warranty',

            // Promo Banner
            'homepage_banner_image'    => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1800&q=85',
            'homepage_banner_video'    => '',
            'homepage_banner_title'    => 'EXCLUSIVE ARCHIVE RELEASE — LIMITED DISPATCH',
            'homepage_banner_subtitle' => 'Handcrafted full-grain Italian leather, aerospace titanium hardware, and bespoke craftsmanship.',
            'homepage_banner_link'     => '/collections/all',
            'homepage_banner_height'   => '48vh',
            'homepage_banner_position' => 'center center',
            'homepage_banner_fit'      => 'cover',
            'homepage_banner_zoom'     => '100',
            'homepage_banner_overlay'  => 'medium',

            // Section Titles
            'section_categories_title' => 'Curated Collections',
            'section_categories_sub'   => 'Explore our engineered everyday carry essentials by category',
            'section_featured_title'   => 'Featured Products',
            'section_showcase_title'   => 'Precision Engineering Showcase',
            'featured_bestseller_product_id' => '',

            // Guarantee & Trust Bar
            'guarantee_title'          => 'The ATELIER Guarantee',
            'guarantee_subtitle'       => '100% Genuine Italian & Egyptian Leather · 2-Year Craftsmanship Warranty · Fast Door-to-Door Delivery across Egypt',

            // Footer
            'footer_brand_tagline'     => 'Precision-engineered everyday carry and bespoke Italian leather accessories. Designed in Cairo for the modern connoisseur.',
            'footer_copyright'         => '© 2026 ATELIER STUDIO EGYPT. ALL RIGHTS RESERVED.',
            'footer_shipping_note'     => 'Express shipping across all Egyptian governorates within 24-48 hours.',
            'social_instagram'         => 'https://instagram.com',
            'social_facebook'          => 'https://facebook.com',
            'social_tiktok'            => 'https://tiktok.com',
        ];
    }

    /**
     * Get all settings as a simple key => value array with defaults merged.
     */
    public static function allAsMap(): array
    {
        $defaults = static::defaults();
        try {
            $db = static::pluck('value', 'key')->toArray();
            return array_merge($defaults, $db);
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    /**
     * Get a single setting by key, with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $defaults = static::defaults();
        try {
            $setting = static::where('key', $key)->first();
            if ($setting && $setting->value !== null && $setting->value !== '') {
                return $setting->value;
            }
            return $defaults[$key] ?? $default;
        } catch (\Throwable $e) {
            return $defaults[$key] ?? $default;
        }
    }

    /**
     * Set a single setting by key and immediately invalidate all related caches.
     */
    public static function set(string $key, mixed $value): void
    {
        try {
            static::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
            
            // Immediate cache invalidation so changes apply on next request with zero delay
            Cache::forget('site_settings');
            Cache::forget("setting_{$key}");
            Cache::forget('settings_map');
        } catch (\Throwable $e) {
            Log::warning("Could not persist setting {$key}: " . $e->getMessage());
        }
    }
}
