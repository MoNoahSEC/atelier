<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            [
                'title' => 'MagSafe Smart Wallets',
                'slug' => 'magsafe-wallets',
                'description' => 'Magnetic snap-on leather wallets with Find My tracking slot and double-strength N52 magnet arrays.',
                'image_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 10,
            ],
            [
                'title' => 'Pop-Up RFID Cardholders',
                'slug' => 'rfid-cardholders',
                'description' => 'Quick-flick card ejector mechanisms crafted with aerospace titanium and 3K carbon fiber.',
                'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 20,
            ],
            [
                'title' => 'Slim Leather Bifolds',
                'slug' => 'leather-wallets',
                'description' => 'Handcrafted Tuscan and Egyptian vegetable-tanned full-grain leather bifold wallets.',
                'image_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 30,
            ],
            [
                'title' => 'Minimalist Money Clips',
                'slug' => 'money-clips',
                'description' => 'Ultra-thin aerospace aluminum and titanium cardholders with integrated cash band.',
                'image_url' => 'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 40,
            ],
            [
                'title' => 'Passport & Travel Wallets',
                'slug' => 'travel-wallets',
                'description' => 'All-in-one RFID passport organizers, boarding pass holders & SIM card compartments.',
                'image_url' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 50,
            ],
            [
                'title' => 'MagSafe Battery E-Wallets',
                'slug' => 'powerbank-wallets',
                'description' => '5,000mAh magnetic fast wireless powerbank with integrated 2-card leather storage.',
                'image_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 60,
            ],
            [
                'title' => 'Executive Wallet Gift Sets',
                'slug' => 'bundles-gift-sets',
                'description' => 'Matching bespoke leather smart wallet, titanium key organizer & pen in an embossed luxury gift box.',
                'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800&auto=format&fit=crop&q=80',
                'sort_order' => 70,
            ],
        ];

        foreach ($collections as $data) {
            Collection::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'image_url' => $data['image_url'],
                    'status' => 'active',
                    'sort_order' => $data['sort_order'],
                ]
            );
        }
    }
}
