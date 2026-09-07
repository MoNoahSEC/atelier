<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\MediaAsset;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'homepage'],
            [
                'title' => 'Home',
                'status' => 'published',
                'seo_title' => 'ATELIER | Premium Accessories',
                'seo_description' => 'Precision crafted accessories for the modern wardrobe.'
            ]
        );

        // Skip if sections already seeded to prevent overwriting
        if ($page->sections()->count() > 0) {
            return;
        }

        // 1. Hero
        $heroImage = MediaAsset::firstOrCreate(
            ['url' => 'https://images.unsplash.com/photo-1620138290379-3d12234551d0?w=1800&q=85'],
            [
                'type' => 'image',
                'filename' => 'hero-demo.jpg',
                'mime_type' => 'image/jpeg',
                'size_bytes' => 102400,
            ]
        );
        $heroSection = $page->sections()->create([
            'type' => 'hero',
            'settings' => [
                'title' => 'EVERYDAY\nEXCELLENCE',
                'subtitle' => 'Season 01 Collection. Precision-crafted accessories engineered for form and function.',
                'cta_text' => 'Explore Collection',
                'cta_link' => '/collections/all',
            ],
            'sort_order' => 1
        ]);
        $heroSection->mediaAssets()->attach($heroImage->id);

        // 2. Video Feature
        $videoAsset = MediaAsset::firstOrCreate(
            ['url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4'],
            [
                'type' => 'video',
                'filename' => 'demo-video.mp4',
                'mime_type' => 'video/mp4',
                'size_bytes' => 5000000,
            ]
        );
        $videoSection = $page->sections()->create([
            'type' => 'video_feature',
            'settings' => [
                'title' => 'THE PROCESS',
                'autoplay' => true,
                'loop' => true,
                'muted' => true
            ],
            'sort_order' => 2
        ]);
        $videoSection->mediaAssets()->attach($videoAsset->id);

        // 3. 3D Showcase
        $modelAsset = MediaAsset::firstOrCreate(
            ['url' => 'https://modelviewer.dev/shared-assets/models/Astronaut.glb'],
            [
                'type' => '3d',
                'filename' => 'astronaut.glb',
                'mime_type' => 'model/gltf-binary',
                'size_bytes' => 2000000,
            ]
        );
        $modelSection = $page->sections()->create([
            'type' => '3d_showcase',
            'settings' => [
                'title' => 'TITANIUM CARABINER',
                'description' => 'Drag to interact. Precision milled from aerospace grade titanium.',
            ],
            'sort_order' => 3
        ]);
        $modelSection->mediaAssets()->attach($modelAsset->id);

        // 4. Product Carousel
        $page->sections()->create([
            'type' => 'product_carousel',
            'settings' => ['title' => 'Just Dropped'],
            'sort_order' => 4
        ]);
    }
}

