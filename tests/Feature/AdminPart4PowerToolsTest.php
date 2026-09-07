<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TelegramRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPart4PowerToolsTest extends TestCase
{
    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_p4@atelier.com'],
            [
                'name'       => 'Super Admin P4',
                'password'   => Hash::make('AdminPass2026!'),
                'is_admin'   => true,
                'admin_role' => 'super_admin',
            ]
        );

        $this->customer = User::firstOrCreate(
            ['email' => 'client_p4@atelier.com'],
            [
                'name'       => 'Tarek Youssef',
                'password'   => Hash::make('ClientPass2026!'),
                'is_admin'   => false,
            ]
        );
        TelegramRecipient::truncate();
        AbandonedCart::truncate();
    }

    public function test_dashboard_stats_caching(): void
    {
        Cache::flush();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Assert cached stats exist
        $this->assertTrue(Cache::has('admin.dashboard.stats'));
        $cached = Cache::get('admin.dashboard.stats');
        $this->assertArrayHasKey('totalOrders', $cached);
        $this->assertArrayHasKey('totalRevenue', $cached);
    }

    public function test_global_quick_search_endpoint(): void
    {
        $uniqueSku = 'SRCH-' . time();
        $product = Product::create([
            'sku'                => $uniqueSku,
            'title'              => 'Bespoke Search Test Product',
            'slug'               => 'bespoke-search-test-' . time(),
            'retail_price_minor' => 85000,
            'inventory'          => 12,
            'status'             => 'active',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.search', ['q' => 'Bespoke Search']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'products'  => [['id', 'label', 'sub', 'badge', 'url']],
            'orders'    => [],
            'customers' => [],
        ]);

        $this->assertTrue(collect($response->json('products'))->contains('id', $product->id));
    }

    public function test_inline_quick_edit_for_price_and_stock(): void
    {
        $product = Product::create([
            'sku'                => 'INLINE-' . time(),
            'title'              => 'Inline Edit Test Product',
            'slug'               => 'inline-edit-test-' . time(),
            'retail_price_minor' => 45000, // 450 EGP
            'inventory'          => 10,
            'status'             => 'draft',
        ]);

        // 1. Edit price
        $priceResponse = $this->actingAs($this->admin)->postJson(
            route('admin.products.inline-edit', $product->id),
            ['field' => 'retail_price_minor', 'value' => 620]
        );
        $priceResponse->assertStatus(200);
        $priceResponse->assertJson(['success' => true]);
        $this->assertEquals(62000, $product->fresh()->retail_price_minor);

        // 2. Edit stock
        $stockResponse = $this->actingAs($this->admin)->postJson(
            route('admin.products.inline-edit', $product->id),
            ['field' => 'inventory', 'value' => 25]
        );
        $stockResponse->assertStatus(200);
        $stockResponse->assertJson(['success' => true]);
        $this->assertEquals(25, $product->fresh()->inventory);
    }

    public function test_abandoned_carts_visibility_and_follow_up(): void
    {
        $cart = AbandonedCart::create([
            'user_id'        => $this->customer->id,
            'cart_data_json' => ['product_title' => 'Titanium Slim Cardholder', 'price_minor' => 95000],
            'abandoned_at'   => now()->subHours(3),
        ]);

        // View index
        $response = $this->actingAs($this->admin)->get(route('admin.abandoned-carts.index'));
        $response->assertStatus(200);
        $response->assertSee('Tarek Youssef');

        // Mark as followed up
        $followUpResponse = $this->actingAs($this->admin)->post(
            route('admin.abandoned-carts.follow-up', $cart->id),
            ['note' => 'Dispatched WhatsApp reminder with 10% coupon']
        );
        $followUpResponse->assertSessionHas('success');

        $this->assertNotNull($cart->fresh()->followed_up_at);
        $this->assertEquals('Dispatched WhatsApp reminder with 10% coupon', $cart->fresh()->follow_up_note);
    }

    public function test_telegram_settings_recipients_and_test_message(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 123]], 200),
        ]);

        // 1. Save Bot Token
        $tokenResponse = $this->actingAs($this->admin)->post(route('admin.telegram.settings'), [
            'telegram_bot_token' => '123456789:ABCdefGhIJKlmNoPQRstuVwxyZ123456',
        ]);
        $tokenResponse->assertSessionHas('success');
        $this->assertEquals('123456789:ABCdefGhIJKlmNoPQRstuVwxyZ123456', Setting::get('telegram_bot_token'));

        // 2. Add Recipient
        $recipientResponse = $this->actingAs($this->admin)->post(route('admin.telegram.recipients.store'), [
            'label'   => 'VIP Store Concierge',
            'chat_id' => '987654321',
        ]);
        $recipientResponse->assertSessionHas('success');

        $recipient = TelegramRecipient::where('chat_id', '987654321')->first();
        $this->assertNotNull($recipient);
        $this->assertTrue($recipient->is_active);

        // 3. Send Test Message
        $testResponse = $this->actingAs($this->admin)->post(route('admin.telegram.test'));
        $testResponse->assertSessionHas('success');

        // 4. Toggle recipient to paused
        $toggleResponse = $this->actingAs($this->admin)->post(route('admin.telegram.recipients.toggle', $recipient->id));
        $toggleResponse->assertSessionHas('success');
        $this->assertFalse($recipient->fresh()->is_active);
    }

    public function test_order_creation_telegram_non_blocking_safety(): void
    {
        // Simulate Telegram API network failure
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        Setting::set('telegram_bot_token', 'invalid_token_test');
        TelegramRecipient::create(['label' => 'Test', 'chat_id' => '111', 'is_active' => true]);

        $product = Product::create([
            'sku'                => 'TG-SAFE-' . time(),
            'title'              => 'Telegram Safety Test Item',
            'slug'               => 'tg-safe-test-' . time(),
            'retail_price_minor' => 75000,
            'inventory'          => 10,
            'status'             => 'active',
        ]);

        // Place order on storefront — order MUST succeed and redirect, despite Telegram failure
        $checkoutResponse = $this->actingAs($this->customer)->post(route('checkout.place'), [
            'full_name'      => 'Tarek Youssef',
            'phone'          => '01012345678',
            'city'           => 'Cairo',
            'street_address' => '90th St, New Cairo',
            'payment_method' => 'cod',
            'product_id'     => $product->id,
        ]);

        $checkoutResponse->assertRedirect(route('account'));
        $checkoutResponse->assertSessionHas('success');

        // Confirm order was saved in database
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'client_p4@atelier.com',
        ]);
    }

    public function test_storefront_typo_tolerant_search(): void
    {
        $product = Product::create([
            'sku'                => 'TYPO-' . time(),
            'title'              => 'Damascus Cardholder Premium',
            'slug'               => 'damascus-cardholder-premium-' . time(),
            'description'        => 'Handcrafted Damascus steel accessory',
            'retail_price_minor' => 90000,
            'inventory'          => 15,
            'status'             => 'active',
        ]);

        $response = $this->get(route('collections.show', ['slug' => 'all', 'q' => 'damascus cardholder']));
        $response->assertStatus(200);
        $response->assertSee('Damascus Cardholder Premium');
    }
}
