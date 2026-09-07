<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    // Default values returned when no settings are in the DB yet
    private const DEFAULTS = [
        'storeName'               => 'ATELIER',
        'announcementText'        => 'FREE SHIPPING FOR ORDERS ABOVE 2,500 EGP · NO RETURNS DURING SALE · EXCHANGES ARE WELCOMED!',
        'announcementSecondary'   => 'EGYPTIAN LUXURY CRAFTSMANSHIP · 24H EXPRESS DISPATCH · CASH ON DELIVERY & PAYMOB',
        'freeShippingThreshold'   => '2500',
        'homeHeroTitle'           => 'PRECISION CRAFTED ACCESSORIES',
        'homeHeroSubtitle'        => 'Handcrafted full-grain leather cases, aerospace titanium hardware, and minimalist everyday carry.',
        'homeHeroImage'           => 'https://images.unsplash.com/photo-1620138290379-3d12234551d0?w=1800&q=85',
        'homeHeroCtaText'         => 'Explore Catalog',
        'homeHeroCtaLink'         => '/collections/all',
        'homeBgColor'             => '#0c0a09',
        'homeBgImage'             => '',
        'homeBannerImage'         => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80',
        'guaranteeTitle'          => 'The ATELIER Guarantee',
        'guaranteeSubtitle'       => '100% Genuine Italian & Egyptian Leather · 2-Year Craftsmanship Warranty · Fast Door-to-Door Delivery across Egypt',
        'guaranteeImage'          => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80',
        'logoUrl'                 => '',
        'whiteLogoUrl'            => '',
        'supportEmail'            => 'concierge@atelier.eg',
        'supportPhone'            => '+20 100 000 0000',
        'currency'                => 'EGP',
        'taxRate'                 => '14.0',
        'shippingDomestic'        => '50.00',
        'shippingInternational'   => '250.00',
        'homepage_banner_image'   => '',
        'homepage_banner_title'   => 'LIMITED RELEASE — AUTUMN ARCHIVE',
        'homepage_banner_subtitle'=> 'Discover handcrafted leather goods and titanium EDC essentials.',
        'homepage_banner_link'    => '/collections/all',
    ];

    public function index(): JsonResponse
    {
        $stored = Setting::allAsMap();

        // Merge defaults so the frontend always gets a complete object
        $settings = array_merge(self::DEFAULTS, $stored);

        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $all = $request->all();
        $imageKeys = [
            'homepage_banner_image',
            'homeHeroImage',
            'homeBannerImage',
            'logoUrl',
            'whiteLogoUrl',
            'store_logo',
            'store_favicon',
            'guaranteeImage',
        ];

        foreach ($all as $key => $value) {
            if (is_string($key) && $key !== '_token') {
                // Prevent wiping out existing images with empty string or null
                if (in_array($key, $imageKeys, true) && empty($value)) {
                    continue;
                }

                if (is_array($value)) {
                    $value = json_encode($value);
                }
                Setting::set($key, (string) ($value ?? ''));
            }
        }

        $settings = array_merge(self::DEFAULTS, Setting::allAsMap());

        return response()->json(['success' => true, 'data' => $settings]);
    }
}
