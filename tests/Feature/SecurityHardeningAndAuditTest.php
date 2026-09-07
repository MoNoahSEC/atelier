<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityHardeningAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owasp_security_headers_are_present_on_responses(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_brute_force_rate_limiting_locks_out_attacker(): void
    {
        RateLimiter::clear('attacker@example.com|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/account/login', [
                'email'    => 'attacker@example.com',
                'password' => 'wrong-pass',
            ]);
        }

        // 6th attempt should be blocked with 429
        $res = $this->postJson('/account/login', [
            'email'    => 'attacker@example.com',
            'password' => 'wrong-pass',
        ]);
        $res->assertStatus(429);
        $res->assertJsonFragment(['success' => false]);
    }

    public function test_traditional_registration_is_redirected_to_google_oauth(): void
    {
        $res = $this->post(route('client.register'), [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'password'   => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
        ]);

        $res->assertRedirect(route('auth.google'));
    }

    public function test_checkout_server_side_price_integrity_prevents_client_price_tampering(): void
    {
        $product = Product::factory()->create([
            'title'              => 'Luxury Genuine Cardholder',
            'retail_price_minor' => 90000, // 900 EGP in DB
            'inventory'          => 10,
            'status'             => 'active',
        ]);

        // Attempt to forge a tampered cart with 1 EGP price in session
        session([
            'cart' => [
                $product->id . '_0' => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name'       => $product->title,
                    'variant'    => null,
                    'price'      => 1, // Tampered to 1 EGP!
                    'qty'        => 2,
                ],
            ]
        ]);

        $checkoutData = [
            'full_name'      => 'Honest Client',
            'phone'          => '01099998888',
            'city'           => 'Cairo',
            'street_address' => '10 Nile St',
            'latitude'       => 30.0444,
            'longitude'      => 31.2357,
            'payment_method' => 'cod',
        ];

        $res = $this->post(route('checkout.place'), $checkoutData);
        $res->assertRedirect();

        // Check order in DB: subtotal MUST be calculated from DB (900 * 2 = 1800 EGP = 180000 minor)
        $this->assertDatabaseHas('orders', [
            'customer_email' => '01099998888@client.atelier.com',
            'subtotal_minor' => 180000, // NOT tampered 200 minor!
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id'       => $product->id,
            'unit_price_minor' => 90000,
            'total_price_minor'=> 180000,
        ]);
    }
}
