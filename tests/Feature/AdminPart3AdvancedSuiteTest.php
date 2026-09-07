<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPart3AdvancedSuiteTest extends TestCase
{
    private User $superAdmin;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_p3@atelier.com'],
            [
                'name'       => 'Super Administrator',
                'password'   => Hash::make('SuperAdmin2026!'),
                'is_admin'   => true,
                'admin_role' => 'super_admin',
            ]
        );

        $this->staffUser = User::firstOrCreate(
            ['email' => 'staff_p3@atelier.com'],
            [
                'name'       => 'Fulfillment Staff',
                'password'   => Hash::make('StaffPass2026!'),
                'is_admin'   => true,
                'admin_role' => 'staff',
            ]
        );
    }

    public function test_multi_attribute_variants_with_price_overrides_and_stock(): void
    {
        $product = Product::create([
            'sku'                => 'TEST-VAR-' . time(),
            'title'              => 'Multi-Variant Titanium EDC',
            'slug'               => 'multi-variant-titanium-edc-' . time(),
            'retail_price_minor' => 100000, // 1000 EGP
            'inventory'          => 50,
            'status'             => 'active',
            'material'           => 'Aerospace Titanium Grade 5',
            'dimensions'         => '10.5 × 6.8 × 0.8 cm',
            'weight'             => '58g',
        ]);

        // 1. Add custom variant with price override (1250 EGP instead of 1000)
        $response = $this->actingAs($this->superAdmin)->post(route('admin.products.add-variant', $product->id), [
            'attribute_name' => 'Color',
            'title'          => 'Damascus Engraved',
            'price_override' => 1250,
            'inventory'      => 15,
        ]);
        $response->assertSessionHas('success');

        $variant = ProductVariant::where('product_id', $product->id)->first();
        $this->assertNotNull($variant);
        $this->assertEquals('Color', $variant->attribute_name);
        $this->assertEquals('Damascus Engraved', $variant->title);
        $this->assertEquals(125000, $variant->price_override_minor);
        $this->assertEquals(125000, $variant->effective_price_minor);
        $this->assertEquals(15, $variant->inventory);

        // 2. Storefront detail page renders specifications and variant
        $storefrontResponse = $this->get(route('products.show', ['slug' => $product->slug]));
        $storefrontResponse->assertStatus(200);
        $storefrontResponse->assertSee('Aerospace Titanium Grade 5');
        $storefrontResponse->assertSee('Damascus Engraved');
    }

    public function test_bulk_product_actions(): void
    {
        $p1 = Product::create([
            'sku'                => 'BULK-1-' . time(),
            'title'              => 'Bulk Product 1',
            'slug'               => 'bulk-1-' . time(),
            'retail_price_minor' => 50000,
            'inventory'          => 20,
            'status'             => 'draft',
        ]);

        $p2 = Product::create([
            'sku'                => 'BULK-2-' . time(),
            'title'              => 'Bulk Product 2',
            'slug'               => 'bulk-2-' . time(),
            'retail_price_minor' => 80000,
            'inventory'          => 30,
            'status'             => 'draft',
        ]);

        // Bulk activate
        $response = $this->actingAs($this->superAdmin)->post(route('admin.products.bulk'), [
            'action'      => 'activate',
            'product_ids' => [$p1->id, $p2->id],
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('active', $p1->fresh()->status);
        $this->assertEquals('active', $p2->fresh()->status);

        // Bulk price percentage adjustment (+10%)
        $response = $this->actingAs($this->superAdmin)->post(route('admin.products.bulk'), [
            'action'           => 'price_percent',
            'adjustment_value' => 10,
            'product_ids'      => [$p1->id, $p2->id],
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(55000, $p1->fresh()->retail_price_minor);
        $this->assertEquals(88000, $p2->fresh()->retail_price_minor);
    }

    public function test_csv_export_and_import_flow(): void
    {
        // 1. Export CSV stream test
        $response = $this->actingAs($this->superAdmin)->get(route('admin.products.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // 2. Import CSV test with a new product and an update
        $skuNew = 'CSV-NEW-' . time();
        $csvContent = "SKU,Name,Retail_Price_EGP,Inventory,Status,Material\n" .
                      "{$skuNew},Automated CSV Item,1450,40,active,Carbon Fiber";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.products.import'), [
            'csv_file' => $file,
        ]);
        $response->assertSessionHas('success');

        $imported = Product::where('sku', $skuNew)->first();
        $this->assertNotNull($imported);
        $this->assertEquals('Automated CSV Item', $imported->title);
        $this->assertEquals(145000, $imported->retail_price_minor);
        $this->assertEquals('Carbon Fiber', $imported->material);
    }

    public function test_inventory_oversight_and_threshold_controls(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.inventory.index'));
        $response->assertStatus(200);
        $response->assertSee('Inventory Oversight');

        // Update threshold
        $response = $this->actingAs($this->superAdmin)->post(route('admin.inventory.update-threshold'), [
            'low_stock_threshold' => 8,
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('8', Setting::get('low_stock_threshold'));
    }

    public function test_shipping_zones_and_free_shipping_rules(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.shipping.index'));
        $response->assertStatus(200);
        $response->assertSee('Shipping');
        $response->assertSee('Tax Rules');

        // Create new zone
        $response = $this->actingAs($this->superAdmin)->post(route('admin.shipping.zones.store'), [
            'name'           => 'New Capital & Madinaty',
            'governorates'   => 'New Capital, Madinaty, Badr City',
            'rate'           => 50,
            'estimated_days' => '24 Hours VIP',
        ]);
        $response->assertSessionHas('success');

        $zone = ShippingZone::where('name', 'New Capital & Madinaty')->first();
        $this->assertNotNull($zone);
        $this->assertEquals(5000, $zone->rate_minor);

        // Test matching helper
        $matched = ShippingZone::findForGovernorate('Madinaty');
        $this->assertNotNull($matched);
        $this->assertEquals('New Capital & Madinaty', $matched->name);
    }

    public function test_role_based_access_control(): void
    {
        // 1. Super Admin CAN access team management
        $response = $this->actingAs($this->superAdmin)->get(route('admin.team.index'));
        $response->assertStatus(200);
        $response->assertSee('Admin Team');
        $response->assertSee('Permissions');

        // 2. Staff user is BLOCKED from team management with 403
        $response = $this->actingAs($this->staffUser)->get(route('admin.team.index'));
        $response->assertStatus(403);
    }

    public function test_maintenance_mode_interception(): void
    {
        // 1. Enable Maintenance Mode
        Setting::set('maintenance_mode', '1');

        // 2. Unauthenticated guest visiting homepage receives 503 maintenance page
        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee('MAINTENANCE PROTOCOL ACTIVE');

        // 3. Admin user visiting homepage bypasses maintenance seamlessly
        $response = $this->actingAs($this->superAdmin)->get('/');
        $response->assertStatus(200);

        // Clean up
        Setting::set('maintenance_mode', '0');
    }
}
