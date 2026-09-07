<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAndFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test 1: Product Image click-to-replace with validation.
     */
    public function test_product_image_replacement_and_validation(): void
    {
        $product = Product::create([
            'sku'                => 'TEST-SKU-01',
            'slug'               => 'test-product',
            'title'              => 'Luxury Silk Scarf',
            'cost_price_minor'   => 10000,
            'retail_price_minor' => 25000,
            'currency'           => 'EGP',
            'inventory'          => 10,
            'status'             => 'active',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin_test_sec@atelier.com'],
            ['name' => 'Admin Security', 'password' => Hash::make('secret'), 'is_admin' => true]
        );

        // 1. Invalid file rejection
        $invalidFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $response = $this->actingAs($admin)->postJson("/admin/products/{$product->id}/replace-image", [
            'image' => $invalidFile,
        ]);
        $response->assertStatus(422);

        // 2. Valid image upload
        $image = UploadedFile::fake()->image('scarf.jpg', 800, 800);
        $response = $this->actingAs($admin)->postJson("/admin/products/{$product->id}/replace-image", [
            'image' => $image,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $product->refresh();
        $this->assertStringContainsString('/storage/media/', $product->image_url);
    }

    /**
     * Test 2: Policy server-side security (OrderPolicy & AddressPolicy).
     */
    public function test_server_side_authorization_prevents_unauthorized_data_access(): void
    {
        $userA = User::create([
            'name'     => 'User A',
            'email'    => 'usera@example.com',
            'password' => Hash::make('password123'),
        ]);

        $userB = User::create([
            'name'     => 'User B',
            'email'    => 'userb@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Order belonging to User A
        $orderA = Order::create([
            'order_number'       => 'AT-ORDER-001',
            'customer_email'     => 'usera@example.com',
            'currency'           => 'EGP',
            'subtotal_minor'     => 5000,
            'shipping_minor'     => 0,
            'tax_minor'          => 0,
            'discount_minor'     => 0,
            'total_amount_minor' => 5000,
            'payment_status'     => 'paid',
            'fulfillment_status' => 'unfulfilled',
        ]);

        // Address belonging to User A
        $addressA = Address::create([
            'user_id'        => $userA->id,
            'label'          => 'Home',
            'full_name'      => 'User A Name',
            'phone'          => '01011111111',
            'street_address' => '123 Nile St',
            'city'           => 'Cairo',
            'country_code'   => 'EG',
            'is_default'     => true,
        ]);

        // User B attempts to access User A's order -> 403 Forbidden
        $response = $this->actingAs($userB)->get("/account/orders/{$orderA->id}");
        $response->assertStatus(403);

        // User B attempts to edit User A's address -> 403 Forbidden
        $response = $this->actingAs($userB)->put("/account/addresses/{$addressA->id}", [
            'label'          => 'Hacked',
            'full_name'      => 'Hacker',
            'phone'          => '01099999999',
            'street_address' => 'Hacked St',
            'city'           => 'Giza',
        ]);
        $response->assertStatus(403);

        // User B attempts to delete User A's address -> 403 Forbidden
        $response = $this->actingAs($userB)->delete("/account/addresses/{$addressA->id}");
        $response->assertStatus(403);

        // User A accesses their own order -> 200 OK
        $response = $this->actingAs($userA)->get("/account/orders/{$orderA->id}");
        $response->assertStatus(200);
    }

    /**
     * Test 3: Password reset flow end-to-end.
     */
    public function test_user_password_reset_flow_and_audit(): void
    {
        $user = User::create([
            'name'     => 'Test Client',
            'email'    => 'client@atelier.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        // 1. User requests reset link
        $response = $this->post('/forgot-password', [
            'email' => 'client@atelier.com',
        ]);
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertNotNull($user->password_reset_requested_at);
        $this->assertEquals('User', $user->password_reset_requested_by);

        // 2. Generate token to simulate clicking email link
        $token = Password::broker()->createToken($user);

        // 3. User sets new password
        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => 'client@atelier.com',
            'password'              => 'NewSecurePasscode123!',
            'password_confirmation' => 'NewSecurePasscode123!',
        ]);

        $response->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePasscode123!', $user->password));
        $this->assertNotNull($user->password_changed_at);
    }

    /**
     * Test 4: Admin-triggered password reset and zero plaintext exposure.
     */
    public function test_admin_triggered_password_reset_and_activity_audit(): void
    {
        $user = User::create([
            'name'     => 'Customer Name',
            'email'    => 'customer@example.com',
            'password' => Hash::make('customerpass123'),
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin_pwd_sec@atelier.com'],
            ['name' => 'Admin Reset', 'password' => Hash::make('secret'), 'is_admin' => true]
        );

        $response = $this->actingAs($admin)->postJson("/admin/users/{$user->id}/send-password-reset");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $user->refresh();
        $this->assertNotNull($user->password_reset_requested_at);
        $this->assertEquals('Admin', $user->password_reset_requested_by);
    }

    /**
     * Test 5: Address book CRUD & Default exclusivity.
     */
    public function test_address_book_crud_and_default_exclusivity(): void
    {
        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 1. Add first address (becomes default automatically)
        $response = $this->actingAs($user)->post('/account/addresses', [
            'label'          => 'Home',
            'full_name'      => 'Jane Doe',
            'phone'          => '01012345678',
            'street_address' => '10 Corniche El Nil',
            'city'           => 'Cairo',
            'state'          => 'Cairo Governorate',
        ]);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('addresses', [
            'user_id'    => $user->id,
            'label'      => 'Home',
            'is_default' => true,
        ]);

        $address1 = $user->addresses()->first();

        // 2. Add second address marked as default
        $response = $this->actingAs($user)->post('/account/addresses', [
            'label'          => 'Office',
            'full_name'      => 'Jane Doe',
            'phone'          => '01087654321',
            'street_address' => '50 Smart Village',
            'city'           => 'Giza',
            'is_default'     => 1,
        ]);

        $address1->refresh();
        $this->assertFalse($address1->is_default);

        $this->assertDatabaseHas('addresses', [
            'user_id'    => $user->id,
            'label'      => 'Office',
            'is_default' => true,
        ]);
    }

    /**
     * Test 6: Checkout integration with saved address and order placement.
     */
    public function test_checkout_with_saved_address_and_order_placement(): void
    {
        $user = User::create([
            'name'     => 'Checkout User',
            'email'    => 'checkout@example.com',
            'password' => Hash::make('password123'),
        ]);

        $savedAddress = Address::create([
            'user_id'        => $user->id,
            'label'          => 'Home',
            'full_name'      => 'Checkout User',
            'phone'          => '01022223333',
            'street_address' => '44 Zamalek St',
            'city'           => 'Cairo',
            'country_code'   => 'EG',
            'is_default'     => true,
        ]);

        $product = Product::create([
            'sku'                => 'AT-RING-01',
            'slug'               => 'platinum-ring',
            'title'              => 'Platinum Band',
            'cost_price_minor'   => 15000,
            'retail_price_minor' => 30000,
            'currency'           => 'EGP',
            'inventory'          => 5,
            'status'             => 'active',
        ]);

        $response = $this->actingAs($user)->post('/checkout/place', [
            'saved_address_id' => $savedAddress->id,
            'payment_method'   => 'cod',
            'product_id'       => $product->id,
        ]);

        $response->assertRedirect('/account');
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'checkout@example.com',
            'payment_method' => 'cod',
            'payment_status' => 'cod_pending',
        ]);
    }
}
