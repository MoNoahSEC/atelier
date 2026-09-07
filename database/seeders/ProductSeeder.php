<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Collection;
use App\Models\MediaAsset;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch or create collections
        $magSafeCol = Collection::firstOrCreate(['slug' => 'magsafe-wallets'], ['title' => 'MagSafe Smart Wallets', 'status' => 'active', 'sort_order' => 10]);
        $rfidCol    = Collection::firstOrCreate(['slug' => 'rfid-cardholders'], ['title' => 'Pop-Up RFID Cardholders', 'status' => 'active', 'sort_order' => 20]);
        $leatherCol = Collection::firstOrCreate(['slug' => 'leather-wallets'], ['title' => 'Slim Leather Bifolds', 'status' => 'active', 'sort_order' => 30]);
        $moneyCol   = Collection::firstOrCreate(['slug' => 'money-clips'], ['title' => 'Minimalist Money Clips', 'status' => 'active', 'sort_order' => 40]);
        $travelCol  = Collection::firstOrCreate(['slug' => 'travel-wallets'], ['title' => 'Passport & Travel Wallets', 'status' => 'active', 'sort_order' => 50]);
        $batteryCol = Collection::firstOrCreate(['slug' => 'powerbank-wallets'], ['title' => 'MagSafe Battery E-Wallets', 'status' => 'active', 'sort_order' => 60]);
        $bundleCol  = Collection::firstOrCreate(['slug' => 'bundles-gift-sets'], ['title' => 'Executive Wallet Gift Sets', 'status' => 'active', 'sort_order' => 70]);

        // Helper: only attach media if product doesn't already have custom images
        // This prevents re-seeding from overwriting admin-uploaded images
        $attachMedia = function (Product $product, array $imageUrls) {
            // If product already has media assets, skip — admin may have replaced them
            if ($product->mediaAssets()->count() > 0) {
                return;
            }
            $mediaSync = [];
            foreach ($imageUrls as $i => $url) {
                $asset = MediaAsset::firstOrCreate(
                    ['url' => $url],
                    [
                        'type' => 'image',
                        'filename' => basename(parse_url($url, PHP_URL_PATH)) ?: "wallet-{$product->id}-{$i}.jpg",
                        'mime_type' => 'image/jpeg',
                        'size_bytes' => 250000,
                    ]
                );
                $mediaSync[$asset->id] = ['sort_order' => $i, 'group' => 'gallery'];
            }
            $product->mediaAssets()->sync($mediaSync);
        };

        // ── 1. ATELIER MagSafe Slim Leather Wallet – Stone Grey ───────────────
        $p1 = Product::updateOrCreate(
            ['slug' => 'atelier-magsafe-slim-leather-wallet-stone-grey'],
            [
                'sku' => 'ATL-WLT-MAG-01',
                'title' => 'ATELIER Slim MagSafe Leather Wallet – Stone Grey',
                'description' => 'A sleek leather smart wallet designed to snap magnetically onto your iPhone while keeping your everyday carry refined and minimal. Slim, lightweight, and engineered with double-strength N52 neodymium magnets and RFID-blocking protection.',
                'image_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
                'cost_price_minor' => 28000,
                'retail_price_minor' => 54000, // 540 EGP (Sale from 900 EGP)
                'currency' => 'EGP',
                'inventory' => 65,
                'status' => 'active',
                'attributes_json' => [
                    'Material' => '100% Top-Grain Tuscan Leather',
                    'Compatibility' => 'MagSafe iPhone 12, 13, 14, 15, 16 & 17 Series',
                    'Card Capacity' => '1 to 3 Cards + Folded Emergency Cash',
                    'Security' => 'Full RFID & NFC Anti-Theft Shielding',
                    'Magnet Array' => '3,200 Gauss N52 Ultra-Strong Hold',
                ]
            ]
        );
        $p1->collections()->sync([$magSafeCol->id, $bundleCol->id]);
        $attachMedia($p1, [
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=1000&q=85',
            'https://images.unsplash.com/photo-1544816155-12df9643f363?w=1000&q=85',
        ]);
        foreach (['Stone Grey', 'Obsidian Black', 'Cognac Tan', 'Midnight Navy'] as $color) {
            $p1->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-MAG-{$color}"],
                ['title' => $color, 'cost_price_minor' => 28000, 'retail_price_minor' => 54000, 'inventory' => 16]
            );
        }

        // ── 2. ATELIER Titanium Pop-Up RFID Cardholder ───────────────────────
        $p2 = Product::updateOrCreate(
            ['slug' => 'atelier-titanium-popup-rfid-cardholder'],
            [
                'sku' => 'ATL-WLT-TIT-02',
                'title' => 'ATELIER Titanium Pop-Up RFID Cardholder',
                'description' => 'Patented quick-flick ergonomic card ejector trigger. Cascades up to 6 cards instantly in a smooth fan for effortless one-handed selection. Encased in laser-etched aerospace titanium with military-grade RFID protection.',
                'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
                'cost_price_minor' => 35000,
                'retail_price_minor' => 68000, // 680 EGP (Sale from 1,100 EGP)
                'currency' => 'EGP',
                'inventory' => 50,
                'status' => 'active',
                'attributes_json' => [
                    'Material' => 'Grade-5 Aerospace Titanium & Anodized Aluminum',
                    'Mechanism' => 'Patented Instant Card Ejector Trigger',
                    'Capacity' => '6 Cards in Ejector + 2 in Cash Band',
                    'Weight' => '48 Grams Ultra-Lightweight Profile',
                ]
            ]
        );
        $p2->collections()->sync([$rfidCol->id, $moneyCol->id]);
        $attachMedia($p2, [
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=1000&q=85',
            'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=1000&q=85',
        ]);
        foreach (['Stealth Matte Black', 'Brushed Titanium', 'Gunmetal Grey'] as $finish) {
            $p2->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-TIT-{$finish}"],
                ['title' => $finish, 'cost_price_minor' => 35000, 'retail_price_minor' => 68000, 'inventory' => 15]
            );
        }

        // ── 3. ATELIER Heritage Slim Bifold Leather Wallet ────────────────────
        $p3 = Product::updateOrCreate(
            ['slug' => 'atelier-heritage-slim-bifold-leather-wallet'],
            [
                'sku' => 'ATL-WLT-BF-03',
                'title' => 'ATELIER Heritage Slim Bifold Leather Wallet',
                'description' => 'Handcrafted from Italian vegetable-tanned calfskin. Ultra-compact folded profile that prevents pocket bulge while holding up to 8 cards and full-length unfolded banknotes.',
                'image_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
                'cost_price_minor' => 42000,
                'retail_price_minor' => 85000, // 850 EGP (Sale from 1,400 EGP)
                'currency' => 'EGP',
                'inventory' => 40,
                'status' => 'active',
                'attributes_json' => [
                    'Leather' => 'Vegetable-Tanned Full-Grain Leather',
                    'Capacity' => '8 Cards + Full-Size Currency Pocket',
                    'Stitching' => 'Reinforced Waxed Thread Edge Craft',
                    'Thickness' => '0.8cm When Closed',
                ]
            ]
        );
        $p3->collections()->sync([$leatherCol->id, $bundleCol->id]);
        $attachMedia($p3, [
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&q=85',
            'https://images.unsplash.com/photo-1544816155-12df9643f363?w=1000&q=85',
        ]);
        foreach (['Saddle Brown', 'Espresso Black', 'Burgundy Wine'] as $color) {
            $p3->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-BF-{$color}"],
                ['title' => $color, 'cost_price_minor' => 42000, 'retail_price_minor' => 85000, 'inventory' => 12]
            );
        }

        // ── 4. ATELIER 3K Carbon Fiber Minimalist Money Clip ─────────────────
        $p4 = Product::updateOrCreate(
            ['slug' => 'atelier-carbon-fiber-minimalist-money-clip'],
            [
                'sku' => 'ATL-WLT-CF-04',
                'title' => 'ATELIER 3K Carbon Fiber Minimalist Money Clip Wallet',
                'description' => 'Real 3K matte carbon fiber plates bonded with high-elastic silicone cash band. Featherlight, unbendable, and scratch-resistant with quick-access finger bevel for card dispensing.',
                'image_url' => 'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=1000&q=85',
                'cost_price_minor' => 30000,
                'retail_price_minor' => 59000, // 590 EGP (Sale from 950 EGP)
                'currency' => 'EGP',
                'inventory' => 55,
                'status' => 'active',
                'attributes_json' => [
                    'Material' => '100% Real 3K Matte Carbon Fiber',
                    'Cash Clip' => 'Integrated Spring-Steel Money Clip & Cash Strap',
                    'Capacity' => '1 to 12 Cards + 15 Folded Bills',
                    'Weight' => '32 Grams',
                ]
            ]
        );
        $p4->collections()->sync([$moneyCol->id, $rfidCol->id]);
        $attachMedia($p4, [
            'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=1000&q=85',
        ]);
        foreach (['Matte Carbon Weave', 'Forged Carbon Grain'] as $texture) {
            $p4->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-CF-{$texture}"],
                ['title' => $texture, 'cost_price_minor' => 30000, 'retail_price_minor' => 59000, 'inventory' => 25]
            );
        }

        // ── 5. ATELIER Find My MagSafe Trackable E-Wallet ─────────────────────
        $p5 = Product::updateOrCreate(
            ['slug' => 'atelier-find-my-trackable-magsafe-wallet'],
            [
                'sku' => 'ATL-WLT-FND-05',
                'title' => 'ATELIER Find My Trackable MagSafe Smart Wallet',
                'description' => 'Never lose your wallet again. Integrated Apple Find My tracking module rechargeable via Qi wireless charger. Emits a loud ring and updates location globally on the Apple Find My network.',
                'image_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
                'cost_price_minor' => 48000,
                'retail_price_minor' => 95000, // 950 EGP (Sale from 1,500 EGP)
                'currency' => 'EGP',
                'inventory' => 45,
                'status' => 'active',
                'attributes_json' => [
                    'Network' => 'Official Apple Find My Certification',
                    'Battery Life' => 'Up to 6 Months per Wireless Charge',
                    'Speaker' => 'Integrated 85dB Audio Beeper',
                    'Material' => 'Premium Full-Grain Italian Leather',
                ]
            ]
        );
        $p5->collections()->sync([$magSafeCol->id, $leatherCol->id]);
        $attachMedia($p5, [
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&q=85',
        ]);
        foreach (['Desert Tan', 'Carbon Obsidian', 'Saddle Brown'] as $color) {
            $p5->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-FND-{$color}"],
                ['title' => $color, 'cost_price_minor' => 48000, 'retail_price_minor' => 95000, 'inventory' => 15]
            );
        }

        // ── 6. ATELIER MagSafe Battery PowerBank E-Wallet ─────────────────────
        $p6 = Product::updateOrCreate(
            ['slug' => 'atelier-magsafe-powerbank-battery-wallet'],
            [
                'sku' => 'ATL-WLT-BAT-06',
                'title' => 'ATELIER MagSafe 5,000mAh PowerBank E-Wallet',
                'description' => '2-in-1 hybrid charging wallet. Snaps magnetically to charge your iPhone with 15W wireless fast charging while securely holding 2 daily credit cards in genuine leather slots.',
                'image_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=1000&q=85',
                'cost_price_minor' => 75000,
                'retail_price_minor' => 145000, // 1,450 EGP (Sale from 2,200 EGP)
                'currency' => 'EGP',
                'inventory' => 35,
                'status' => 'active',
                'attributes_json' => [
                    'Capacity' => '5,000mAh High-Density Graphene Battery',
                    'Output' => '15W MagSafe Wireless + 20W USB-C PD Fast Output',
                    'Card Slot' => 'Genuine Leather Dual Card Storage',
                    'Safety' => 'Overheat & Short-Circuit Safety Protection',
                ]
            ]
        );
        $p6->collections()->sync([$batteryCol->id, $magSafeCol->id]);
        $attachMedia($p6, [
            'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=1000&q=85',
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
        ]);
        foreach (['Matte Obsidian', 'Titanium Silver'] as $color) {
            $p6->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-BAT-{$color}"],
                ['title' => $color, 'cost_price_minor' => 75000, 'retail_price_minor' => 145000, 'inventory' => 17]
            );
        }

        // ── 7. ATELIER All-In-One RFID Passport & Travel Wallet ───────────────
        $p7 = Product::updateOrCreate(
            ['slug' => 'atelier-rfid-passport-travel-wallet'],
            [
                'sku' => 'ATL-WLT-TRV-07',
                'title' => 'ATELIER All-In-One RFID Passport & Travel Wallet',
                'description' => 'The ultimate airport and voyage organizer. Fits international passports, boarding passes, 6 cards, foreign cash, micro SIM cards & includes a dedicated SIM eject pin slot.',
                'image_url' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=1000&q=85',
                'cost_price_minor' => 38000,
                'retail_price_minor' => 78000, // 780 EGP (Sale from 1,250 EGP)
                'currency' => 'EGP',
                'inventory' => 50,
                'status' => 'active',
                'attributes_json' => [
                    'Material' => 'Water-Resistant Full-Grain Waxed Leather',
                    'Slots' => 'Passport, Boarding Pass, 6 Cards, SIM Tray Tool',
                    'Security' => '360° RFID Jamming Shield',
                    'Closure' => 'Concealed Magnetic Snap',
                ]
            ]
        );
        $p7->collections()->sync([$travelCol->id, $leatherCol->id]);
        $attachMedia($p7, [
            'https://images.unsplash.com/photo-1544816155-12df9643f363?w=1000&q=85',
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&q=85',
        ]);
        foreach (['Vintage Brown', 'Classic Black', 'Navy Blue'] as $color) {
            $p7->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-TRV-{$color}"],
                ['title' => $color, 'cost_price_minor' => 38000, 'retail_price_minor' => 78000, 'inventory' => 16]
            );
        }

        // ── 8. ATELIER Executive Smart Wallet Gift Box Package ────────────────
        $p8 = Product::updateOrCreate(
            ['slug' => 'atelier-executive-smart-wallet-gift-set'],
            [
                'sku' => 'ATL-WLT-PKG-08',
                'title' => 'ATELIER Executive Smart Wallet Gift Set',
                'description' => 'The complete luxury EDC collection in an embossed matte gift box. Contains the ATELIER MagSafe Smart Leather Wallet, Aerospace Titanium Key Organizer, and Heavy-Duty Braided Pen.',
                'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&q=85',
                'cost_price_minor' => 110000,
                'retail_price_minor' => 220000, // 2,200 EGP (Sale from 3,500 EGP)
                'currency' => 'EGP',
                'inventory' => 25,
                'status' => 'active',
                'attributes_json' => [
                    'Set Contains' => 'Smart MagSafe Wallet + Titanium Key Organizer + EDC Pen',
                    'Packaging' => 'Hard-Shell Rigid Gift Box with Velvet Tray',
                    'Warranty' => '2-Year Full Craftsmanship Guarantee',
                ]
            ]
        );
        $p8->collections()->sync([$bundleCol->id, $magSafeCol->id, $rfidCol->id]);
        $attachMedia($p8, [
            'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&q=85',
            'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85',
            'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=1000&q=85',
        ]);
        foreach (['Executive Brown Gift Set', 'Obsidian Black Gift Set'] as $bundle) {
            $p8->variants()->updateOrCreate(
                ['sku' => "ATL-WLT-PKG-{$bundle}"],
                ['title' => $bundle, 'cost_price_minor' => 110000, 'retail_price_minor' => 220000, 'inventory' => 12]
            );
        }
    }
}
