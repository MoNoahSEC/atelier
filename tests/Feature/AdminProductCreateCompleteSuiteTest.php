<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductCreateCompleteSuiteTest extends TestCase
{
    private User $admin;
    private Collection $collection1;
    private Collection $collection2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_create_test@atelier.com'],
            [
                'name'       => 'Super Admin Creation Test',
                'password'   => Hash::make('AdminPass2026!'),
                'is_admin'   => true,
                'admin_role' => 'super_admin',
            ]
        );

        $this->collection1 = Collection::firstOrCreate(
            ['slug' => 'test-cardholders'],
            ['title' => 'Test Smart Cardholders', 'status' => 'active', 'sort_order' => 1]
        );

        $this->collection2 = Collection::firstOrCreate(
            ['slug' => 'test-leather-wallets'],
            ['title' => 'Test Leather Wallets', 'status' => 'active', 'sort_order' => 2]
        );
    }

    public function test_create_page_renders_with_images_variants_and_collections(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.create'));
        $response->assertStatus(200);

        // Assert all required sections are present
        $response->assertSee('2. Product Images');
        $response->assertSee('Primary Cover Image');
        $response->assertSee('Additional Gallery Photos');
        $response->assertSee('This product comes in multiple colors');
        $response->assertSee('6. Assign to Collections');
        $response->assertSee('Test Smart Cardholders');
        $response->assertSee('Test Leather Wallets');
    }

    public function test_complete_product_creation_with_images_color_variants_and_collections(): void
    {
        $coverFile = UploadedFile::fake()->image('cover-leather.jpg', 600, 600);
        $gallery1  = UploadedFile::fake()->image('gallery-angle-1.jpg', 600, 600);
        $gallery2  = UploadedFile::fake()->image('gallery-angle-2.jpg', 600, 600);
        $variant1Image = UploadedFile::fake()->image('var-black.jpg', 400, 400);
        $variant2Image = UploadedFile::fake()->image('var-tan.jpg', 400, 400);

        $postData = [
            'title'               => 'Titanium Damascus Smart Bifold',
            'description'         => '<p>Handcrafted aerospace-grade titanium bifold with RFID blocking technology.</p>',
            'material'            => 'Damascus Steel & Italian Leather',
            'dimensions'          => '10.5 x 7.0 x 1.2 cm',
            'weight'              => '85g',
            'retail_price'        => '1250',
            'compare_at_price'    => '1600',
            'cost_price'          => '500',
            'inventory'           => 30,
            'low_stock_threshold' => 5,
            'status'              => 'active',
            'seo_title'           => 'Titanium Damascus Smart Bifold — Luxury EDC',
            'seo_description'     => 'Bespoke Damascus titanium bifold wallet with RFID shield.',
            'cover_image'         => $coverFile,
            'gallery_images'      => [$gallery1, $gallery2],
            'collection_ids'      => [$this->collection1->id, $this->collection2->id],
            'has_color_variants'  => '1',
            'variants'            => [
                0 => [
                    'title'          => 'Obsidian Black',
                    'color_hex'      => '#111111',
                    'price_override' => '1250',
                    'inventory'      => 18,
                    'image'          => $variant1Image,
                ],
                1 => [
                    'title'          => 'Tuscan Cognac',
                    'color_hex'      => '#8B4513',
                    'price_override' => '1350',
                    'inventory'      => 12,
                    'image'          => $variant2Image,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $postData);
        $response->assertSessionHas('success');

        $product = Product::where('title', 'Titanium Damascus Smart Bifold')->first();
        $this->assertNotNull($product);
        $this->assertStringContainsString('-TITANI-', $product->sku);
        $this->assertEquals('Titanium Damascus Smart Bifold', $product->title);
        $this->assertEquals(125000, $product->retail_price_minor);
        $this->assertNotNull($product->image_url);

        // 1. Assert Collections synced
        $this->assertEquals(2, $product->collections()->count());
        $this->assertTrue($product->collections->contains('id', $this->collection1->id));
        $this->assertTrue($product->collections->contains('id', $this->collection2->id));

        // 2. Assert Gallery Photos attached
        $this->assertEquals(2, $product->mediaAssets()->count());

        // 3. Assert Color Variants created
        $this->assertEquals(2, $product->variants()->count());
        $v1 = $product->variants()->where('title', 'Obsidian Black')->first();
        $this->assertNotNull($v1);
        $this->assertEquals(125000, $v1->price_override_minor);
        $this->assertEquals(18, $v1->inventory);
        $this->assertNotNull($v1->image_url);
        $this->assertEquals('#111111', $v1->attributes_json['color_hex']);

        $v2 = $product->variants()->where('title', 'Tuscan Cognac')->first();
        $this->assertNotNull($v2);
        $this->assertEquals(135000, $v2->price_override_minor);
        $this->assertEquals(12, $v2->inventory);
        $this->assertNotNull($v2->image_url);
        $this->assertEquals('#8B4513', $v2->attributes_json['color_hex']);

        // 4. Assert Edit View pre-checks the collections
        $editResponse = $this->actingAs($this->admin)->get(route('admin.products.edit', $product->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Test Smart Cardholders');
        $editResponse->assertSee('Test Leather Wallets');

        // 5. Assert Live Storefront Product View displays product and variants
        $storefrontResponse = $this->get(route('products.show', $product->slug));
        $storefrontResponse->assertStatus(200);
        $storefrontResponse->assertSee('Titanium Damascus Smart Bifold');
        $storefrontResponse->assertSee('Obsidian Black');
        $storefrontResponse->assertSee('Tuscan Cognac');
    }
}
