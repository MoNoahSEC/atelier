<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapWhatsAppAndSurveysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('admin_direct_access', '1');
    }

    public function test_checkout_with_saved_address_succeeds_without_validation_errors(): void
    {
        $user = User::factory()->create([
            'email' => 'client.cairo@example.com',
            'name'  => 'Tarek Mostafa',
        ]);

        $address = Address::create([
            'user_id'        => $user->id,
            'label'          => 'Zamalek Residence',
            'full_name'      => 'Tarek Mostafa',
            'phone'          => '01012345678',
            'street_address' => '15 Gezira St, Tower 4, Apt 12',
            'city'           => 'Cairo',
            'latitude'       => 30.0626,
            'longitude'      => 31.2224,
            'is_default'     => true,
        ]);

        $product = Product::factory()->create([
            'title'              => 'ATELIER MagSafe Carbon Wallet',
            'retail_price_minor' => 45000,
            'status'             => 'active',
            'inventory'          => 10,
        ]);

        // When customer checks out with saved_address_id, new address fields are empty (which ConvertEmptyStringsToNull turns to null)
        $response = $this->actingAs($user)->post('/checkout/place', [
            'saved_address_id' => $address->id,
            'full_name'        => '',
            'phone'            => '',
            'city'             => '',
            'street_address'   => '',
            'payment_method'   => 'cod',
            'product_id'       => $product->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/account');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'client.cairo@example.com',
            'payment_method' => 'cod',
            'payment_status' => 'cod_pending',
        ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(30.0626, $order->metadata_json['shipping_address']['latitude']);
        $this->assertEquals(31.2224, $order->metadata_json['shipping_address']['longitude']);
    }

    public function test_checkout_with_new_pinned_coordinates_saves_to_address_and_order(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'retail_price_minor' => 60000,
            'status'             => 'active',
            'inventory'          => 5,
        ]);

        $response = $this->actingAs($user)->post('/checkout/place', [
            'full_name'            => 'Sherif Kamel',
            'phone'                => '01198765432',
            'city'                 => 'New Cairo',
            'street_address'       => 'Villa 45, Choueifat',
            'latitude'             => 30.012345,
            'longitude'            => 31.456789,
            'save_to_address_book' => '1',
            'address_label'        => 'Villa',
            'payment_method'       => 'cod',
            'product_id'           => $product->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/account');

        $this->assertDatabaseHas('addresses', [
            'user_id'   => $user->id,
            'label'     => 'Villa',
            'city'      => 'New Cairo',
            'latitude'  => 30.012345,
            'longitude' => 31.456789,
        ]);

        $order = Order::latest()->first();
        $this->assertEquals(30.012345, $order->metadata_json['shipping_address']['latitude']);
    }

    public function test_whatsapp_admin_settings_and_test_message(): void
    {
        $admin = User::factory()->create([
            'is_admin'   => true,
            'admin_role' => 'super_admin',
        ]);

        // View WhatsApp settings page
        $response = $this->actingAs($admin)->get('/admin/whatsapp');
        $response->assertOk();
        $response->assertSee('WhatsApp Client Notifications');
        $response->assertSee('UltraMsg');

        // Update WhatsApp settings
        $postRes = $this->actingAs($admin)->post('/admin/whatsapp/settings', [
            'whatsapp_provider'    => 'ultramsg',
            'whatsapp_instance_id' => 'instanceTest123',
            'whatsapp_api_token'   => 'tokenSecret999',
            'whatsapp_enabled'     => '1',
        ]);

        $postRes->assertRedirect();
        $this->assertEquals('instanceTest123', Setting::get('whatsapp_instance_id'));
        $this->assertEquals('tokenSecret999', Setting::get('whatsapp_api_token'));
    }

    public function test_surveys_creation_toggle_and_customer_response(): void
    {
        $admin = User::factory()->create([
            'is_admin'   => true,
            'admin_role' => 'super_admin',
        ]);

        // Create new survey
        $res = $this->actingAs($admin)->post('/admin/surveys', [
            'title'       => 'Packaging Feedback',
            'question'    => 'How satisfied are you with the luxury packaging?',
            'type'        => 'rating',
            'target_page' => 'order_confirmation',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'title'    => 'Packaging Feedback',
            'type'     => 'rating',
            'is_active'=> true,
        ]);

        $survey = Survey::where('title', 'Packaging Feedback')->first();

        // Customer responds via public API
        $apiRes = $this->postJson("/api/surveys/{$survey->id}/respond", [
            'rating'        => 5,
            'response_text' => 'Exceptional unboxing experience.',
            'customer_name' => 'Kareem Tarek',
        ]);

        $apiRes->assertOk();
        $apiRes->assertJson(['success' => true]);

        $this->assertDatabaseHas('survey_responses', [
            'survey_id'     => $survey->id,
            'rating'        => 5,
            'customer_name' => 'Kareem Tarek',
        ]);

        // Toggle active status
        $toggleRes = $this->actingAs($admin)->post("/admin/surveys/{$survey->id}/toggle");
        $toggleRes->assertRedirect();
        $this->assertFalse($survey->fresh()->is_active);

        // Export CSV
        $exportRes = $this->actingAs($admin)->get("/admin/surveys/{$survey->id}/export-csv");
        $exportRes->assertOk();
    }
}
