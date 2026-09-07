<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TelegramRecipient;
use App\Services\TelegramBotAssistantService;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_and_orders_reports_generate_accurate_analytics(): void
    {
        Setting::set('store_name', 'ATELIER Egypt');

        // Create sample product and order
        $product = Product::factory()->create([
            'title'               => 'Test Leather Piece',
            'inventory'           => 3, // low stock!
            'retail_price_minor'  => 50000,
            'status'              => 'active',
        ]);

        $customer = Customer::create([
            'first_name' => 'Ahmed',
            'last_name'  => 'VIP',
            'email'      => 'ahmed@example.com',
            'phone'      => '01011112222',
        ]);

        $order = Order::create([
            'order_number'        => 'AT-2026-TEST1',
            'customer_id'         => $customer->id,
            'customer_email'      => 'ahmed@example.com',
            'customer_name'       => 'Ahmed VIP',
            'customer_phone'      => '01011112222',
            'currency'            => 'EGP',
            'subtotal_minor'      => 50000,
            'shipping_minor'      => 7500,
            'total_amount_minor'  => 57500,
            'payment_status'      => 'cod_pending',
            'payment_method'      => 'cod',
            'fulfillment_status'  => 'unfulfilled',
            'shipping_status'     => 'pending',
            'metadata_json'       => [
                'shipping_address' => [
                    'name'      => 'Ahmed VIP',
                    'phone'     => '01011112222',
                    'street'    => '9 Road 9',
                    'city'      => 'Maadi',
                    'latitude'  => 29.9602,
                    'longitude' => 31.2569,
                ]
            ],
        ]);

        /** @var TelegramBotAssistantService $assistant */
        $assistant = app(TelegramBotAssistantService::class);

        // 1. Stats report
        $stats = $assistant->getStatsReport();
        $this->assertStringContainsString('ATELIER Egypt', $stats);
        $this->assertStringContainsString('EGP', $stats);
        $this->assertStringContainsString('المبيعات والأرباح', $stats);

        // 2. Recent orders
        $recent = $assistant->getRecentOrders();
        $this->assertStringContainsString('AT-2026-TEST1', $recent['text']);
        $this->assertStringContainsString('Ahmed VIP', $recent['text']);
        $this->assertNotEmpty($recent['buttons']);

        // 3. Low stock report
        $lowStock = $assistant->getLowStockReport();
        $this->assertStringContainsString('Test Leather Piece', $lowStock['text']);
        $this->assertStringContainsString('3', $lowStock['text']);

        // 4. Search by phone
        $searchResult = $assistant->search('01011112222');
        $this->assertStringContainsString('AT-2026-TEST1', $searchResult['text']);
        $this->assertStringContainsString('Maadi', $searchResult['text']);

        // 5. Search by order number
        $searchOrder = $assistant->search('AT-2026-TEST1');
        $this->assertStringContainsString('575 EGP', $searchOrder['text']);
    }

    public function test_incoming_webhook_processes_command_and_responds(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        Setting::set('telegram_bot_token', '123456:FAKE_TOKEN_ABC');

        $payload = [
            'update_id' => 10001,
            'message' => [
                'message_id' => 45,
                'chat' => [
                    'id' => 8817257718,
                    'type' => 'private',
                ],
                'text' => '/stats',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);
        $response->assertOk();
        $response->assertJson(['ok' => true]);

        // Verify that a request was sent back to Telegram sendMessage
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                   $request['chat_id'] == '8817257718';
        });
    }

    public function test_admin_dashboard_dispatch_action(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        Setting::set('telegram_bot_token', '123456:FAKE_TOKEN_ABC');

        TelegramRecipient::create([
            'label'     => 'Store Manager',
            'chat_id'   => '8817257718',
            'is_active' => true,
        ]);

        $admin = \App\Models\User::create([
            'name'       => 'Admin User',
            'email'      => 'admin_test@atelier.com',
            'password'   => bcrypt('password123'),
            'is_admin'   => true,
            'admin_role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.telegram.send-dashboard'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                   str_contains($request['text'], 'المساعد الذكي');
        });
    }
}
