<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardSectionsTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_suite_test@atelier.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('AdminPass2026!'), 'is_admin' => true]
        );
    }

    public function test_dashboard_renders_with_metrics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Store Dashboard');
        $response->assertSee('EXECUTIVE OVERVIEW');
    }

    public function test_products_management_flow(): void
    {
        // 1. List products
        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));
        $response->assertStatus(200);
        $response->assertSee('All Products');

        // 2. Create product view
        $response = $this->actingAs($this->admin)->get(route('admin.products.create'));
        $response->assertStatus(200);
        $response->assertSee('+ Add New Product');

        // 3. Store product
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'title'            => 'Test Bifold Carbon Edition',
            'retail_price'     => 1200,
            'compare_at_price' => 1500,
            'cost_price'       => 450,
            'inventory'        => 25,
            'status'           => 'active',
            'description'      => 'High durability carbon finish with RFID block.',
        ]);
        
        $product = Product::where('title', 'Test Bifold Carbon Edition')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('CATA-TESTBI-', $product->sku);
        $this->assertEquals(120000, $product->retail_price_minor);
        $this->assertEquals(150000, $product->compare_at_price_minor);
        $response->assertRedirect(route('admin.products.edit', $product->id));

        // 4. Edit view
        $response = $this->actingAs($this->admin)->get(route('admin.products.edit', $product->id));
        $response->assertStatus(200);
        $response->assertSee('Test Bifold Carbon Edition');
        $response->assertSee('PRIMARY COVER');

        // 5. Toggle status
        $this->actingAs($this->admin)->post(route('admin.products.toggle-status', $product->id));
        $this->assertEquals('draft', $product->fresh()->status);
    }

    public function test_collections_management_flow(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.collections.index'));
        $response->assertStatus(200);
        $response->assertSee('Store Collections');

        $title = 'Test Archive Collection ' . time();
        $response = $this->actingAs($this->admin)->post(route('admin.collections.store'), [
            'title'       => $title,
            'description' => 'Test collection description',
            'sort_order'  => 5,
            'status'      => 'active',
        ]);
        $response->assertRedirect(route('admin.collections.index'));

        $col = Collection::where('title', $title)->first();
        $this->assertNotNull($col);
        $this->assertEquals(5, $col->sort_order);
    }

    public function test_orders_management_and_status_update(): void
    {
        $order = Order::create([
            'order_number'       => 'TEST-ORD-' . time(),
            'customer_email'     => 'test_buyer@atelier.com',
            'customer_name'      => 'John Test',
            'customer_phone'     => '+201012345678',
            'subtotal_minor'     => 150000,
            'total_price_minor'  => 150000,
            'status'             => 'pending',
            'payment_method'     => 'cod',
            'payment_status'     => 'pending',
            'currency'           => 'EGP',
        ]);

        // Orders list
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertStatus(200);
        $response->assertSee($order->order_number);

        // Order detail
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->id));
        $response->assertStatus(200);
        $response->assertSee($order->order_number);

        // Update status
        $response = $this->actingAs($this->admin)->post(route('admin.orders.update-status', $order->id), [
            'status'         => 'shipped',
            'payment_status' => 'paid',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('shipped', $order->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_customer_view_and_secure_reset_token(): void
    {
        $customer = User::create([
            'name'     => 'Client VIP',
            'email'    => 'client_vip_' . time() . '@atelier.com',
            'password' => Hash::make('CustomerPass2026!'),
            'is_admin' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee($customer->email);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $customer->id));
        $response->assertStatus(200);
        $response->assertSee('Client VIP');
        $response->assertDontSee('CustomerPass2026!'); // Security check: Plaintext password never rendered

        // Trigger secure password reset
        $response = $this->actingAs($this->admin)->post(route('admin.customers.send-reset', $customer->id));
        $response->assertSessionHas('success');
        $this->assertNotNull($customer->fresh()->password_reset_requested_at);
    }

    public function test_site_content_settings_update_applies_immediately(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.content.index'));
        $response->assertStatus(200);
        $response->assertSee('Site Content & Branding');

        $uniqueHeadline = 'EXCLUSIVE TITANIUM SERIES ' . time();
        $response = $this->actingAs($this->admin)->post(route('admin.content.update'), [
            'store_name'            => 'ATELIER LUXE',
            'homepage_banner_title' => $uniqueHeadline,
            'homeHeroTitle'         => 'ENGRAVED TITANIUM WALLETS',
            'announcement_bar_left' => 'SAME-DAY CAIRO DISPATCH',
        ]);
        $response->assertSessionHas('success');

        // Verify setting is live immediately
        $this->assertEquals($uniqueHeadline, Setting::get('homepage_banner_title'));
        $this->assertEquals('ATELIER LUXE', Setting::get('store_name'));
    }

    public function test_analytics_intelligence_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.index'));
        $response->assertStatus(200);
        $response->assertSee('Analytics & Traffic');
        $response->assertSee('Views Today');
    }

    public function test_coupon_creation_and_toggle(): void
    {
        $code = 'PROMO' . time();
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code'             => $code,
            'type'             => 'percentage',
            'value'            => 15,
            'min_order_amount' => 1000,
            'is_active'        => 1,
        ]);
        $response->assertSessionHas('success');

        $coupon = Coupon::where('code', $code)->first();
        $this->assertNotNull($coupon);
        $this->assertTrue($coupon->is_active);

        // Toggle coupon
        $this->actingAs($this->admin)->post(route('admin.coupons.toggle', $coupon->id));
        $this->assertFalse($coupon->fresh()->is_active);
    }

    public function test_reviews_and_audit_log_pages(): void
    {
        // Reviews
        $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));
        $response->assertStatus(200);
        $response->assertSee('Product Reviews');

        // Audit Log
        $response = $this->actingAs($this->admin)->get(route('admin.audit-log.index'));
        $response->assertStatus(200);
        $response->assertSee('Security Audit Log');
    }
}
