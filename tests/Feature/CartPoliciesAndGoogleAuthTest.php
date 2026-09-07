<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\CartController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPoliciesAndGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_add_update_remove_and_clear_flow(): void
    {
        $product = Product::factory()->create([
            'title'               => 'Italian Leather Cardholder',
            'retail_price_minor'  => 85000, // 850 EGP
            'inventory'           => 10,
        ]);

        // 1. Add to cart
        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'qty'        => 2,
        ]);
        $response->assertSessionHas('cart');
        $cart = session('cart');
        $cartKey = $product->id . '_0';
        $this->assertArrayHasKey($cartKey, $cart);
        $this->assertEquals(2, $cart[$cartKey]['qty']);
        $this->assertEquals(850, $cart[$cartKey]['price']);
        $this->assertEquals(2, CartController::cartCount());
        $this->assertEquals(1700, CartController::cartSubtotal());

        // 2. View cart page
        $cartView = $this->get(route('cart.index'));
        $cartView->assertOk();
        $cartView->assertSee('Italian Leather Cardholder');
        $cartView->assertSee('1,700');

        // 3. Update qty (increase)
        $this->post(route('cart.update'), [
            'key'    => $cartKey,
            'action' => 'increase',
        ]);
        $this->assertEquals(3, session("cart.{$cartKey}.qty"));
        $this->assertEquals(3, CartController::cartCount());

        // 4. Update qty (decrease)
        $this->post(route('cart.update'), [
            'key'    => $cartKey,
            'action' => 'decrease',
        ]);
        $this->assertEquals(2, session("cart.{$cartKey}.qty"));

        // 5. Remove item
        $this->post(route('cart.remove'), [
            'key' => $cartKey,
        ]);
        $this->assertEmpty(session('cart'));
        $this->assertEquals(0, CartController::cartCount());

        // 6. Add and Clear
        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'qty'        => 1,
        ]);
        $this->assertEquals(1, CartController::cartCount());
        $this->post(route('cart.clear'));
        $this->assertEquals(0, CartController::cartCount());
    }

    public function test_checkout_with_multi_item_cart_creates_order_items_and_decrements_inventory(): void
    {
        $p1 = Product::factory()->create([
            'title'              => 'Luxury Minimalist Bifold',
            'retail_price_minor' => 120000,
            'inventory'          => 5,
            'status'             => 'active',
        ]);
        $p2 = Product::factory()->create([
            'title'              => 'Titanium Keychain Tool',
            'retail_price_minor' => 45000,
            'inventory'          => 8,
            'status'             => 'active',
        ]);

        // Place items in session cart
        session([
            'cart' => [
                $p1->id . '_0' => [
                    'product_id' => $p1->id,
                    'variant_id' => null,
                    'name'       => $p1->title,
                    'variant'    => null,
                    'price'      => 1200,
                    'image'      => null,
                    'qty'        => 2,
                ],
                $p2->id . '_0' => [
                    'product_id' => $p2->id,
                    'variant_id' => null,
                    'name'       => $p2->title,
                    'variant'    => null,
                    'price'      => 450,
                    'image'      => null,
                    'qty'        => 1,
                ],
            ]
        ]);

        $checkoutData = [
            'full_name'      => 'Yousef Client',
            'phone'          => '01012345678',
            'city'           => 'Cairo',
            'street_address' => '22 Tahrir Square',
            'latitude'       => 30.0444,
            'longitude'      => 31.2357,
            'payment_method' => 'cod',
        ];

        $res = $this->post(route('checkout.place'), $checkoutData);
        $res->assertRedirect();

        // Cart is cleared after order
        $this->assertEmpty(session('cart'));

        // Order created with 2 items
        $this->assertDatabaseHas('orders', [
            'customer_email' => '01012345678@client.atelier.com',
            'payment_method' => 'cod',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $p1->id,
            'quantity'   => 2,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $p2->id,
            'quantity'   => 1,
        ]);

        // Inventory decremented
        $this->assertEquals(3, $p1->fresh()->inventory);
        $this->assertEquals(7, $p2->fresh()->inventory);
    }

    public function test_policy_pages_render_and_display_required_legal_and_shipping_terms(): void
    {
        // 1. Privacy
        $res = $this->get(route('pages.show', ['slug' => 'privacy']));
        $res->assertOk();
        $res->assertSee('Privacy Policy');

        // 2. Terms of Service
        $res = $this->get(route('pages.show', ['slug' => 'terms']));
        $res->assertOk();
        $res->assertSee('Terms of Service');

        // 3. Shipping Policy (3-5 days, max 4 days from order)
        $res = $this->get(route('pages.show', ['slug' => 'shipping']));
        $res->assertOk();
        $res->assertSee('3 to 5 Business Days');
        $res->assertSee('Maximum dispatch delay from order date: 4 days');

        // 4. Copyright
        $res = $this->get(route('pages.show', ['slug' => 'copyright']));
        $res->assertOk();
        $res->assertSee('Copyright &amp; Intellectual Property', false);
    }

    public function test_google_oauth_routes_and_setup_guide_render_correctly(): void
    {
        // 1. With Client ID configured, redirecting to auth.google redirects directly to accounts.google.com
        $res = $this->get(route('auth.google'));
        $this->assertTrue(str_starts_with($res->headers->get('Location'), 'https://accounts.google.com/o/oauth2/v2/auth'));
        $this->assertStringContainsString('540199902624', $res->headers->get('Location'));

        // 2. Setup guide view renders with instructions
        $setupRes = $this->get(route('auth.google.setup'));
        $setupRes->assertOk();
        $setupRes->assertSee('Google Cloud Console');
        $setupRes->assertSee('/auth/google/callback');
    }
}
