<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAdminAutoRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_designated_google_account_is_auto_promoted_to_admin_and_redirected_to_admin(): void
    {
        Setting::set('admin_google_emails', 'admin_boss@gmail.com, mnosec206@gmail.com');

        // Fake Google OAuth endpoints
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_type'  => 'bearer',
                'access_token' => 'fake_google_access_token',
                'id_token'     => 'fake_id_token',
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email'          => 'admin_boss@gmail.com',
                'name'           => 'The Boss',
                'email_verified' => true,
            ], 200),
        ]);

        session(['google_oauth_state' => 'valid_state_123']);

        $res = $this->get('/auth/google/callback?state=valid_state_123&code=fake_auth_code');

        $res->assertRedirect('/admin');

        $this->assertDatabaseHas('users', [
            'email'      => 'admin_boss@gmail.com',
            'is_admin'   => true,
            'admin_role' => 'super_admin',
        ]);
    }

    public function test_regular_google_account_remains_regular_client(): void
    {
        Setting::set('admin_google_emails', 'admin_boss@gmail.com');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_type'  => 'bearer',
                'access_token' => 'fake_google_access_token',
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email'          => 'normal_shopper@gmail.com',
                'name'           => 'Normal Shopper',
                'email_verified' => true,
            ], 200),
        ]);

        session(['google_oauth_state' => 'valid_state_456']);

        $res = $this->get('/auth/google/callback?state=valid_state_456&code=fake_auth_code');

        $res->assertRedirect('/account');

        $this->assertDatabaseHas('users', [
            'email'    => 'normal_shopper@gmail.com',
            'is_admin' => false,
        ]);
    }

    public function test_admin_can_save_google_admin_emails_and_toggle_roles(): void
    {
        $admin = User::create([
            'name'       => 'Super Admin',
            'email'      => 'super@atelier.com',
            'password'   => bcrypt('pass'),
            'is_admin'   => true,
            'admin_role' => 'admin',
        ]);

        $shopper = User::create([
            'name'       => 'Promotable User',
            'email'      => 'promotable@gmail.com',
            'password'   => bcrypt('pass'),
            'is_admin'   => false,
        ]);

        // 1. Save emails list via settings
        $res = $this->actingAs($admin)->post(route('admin.settings.google-admins'), [
            'admin_google_emails' => 'promotable@gmail.com, extra@gmail.com'
        ]);
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertTrue($shopper->fresh()->is_admin);
        $this->assertEquals('promotable@gmail.com, extra@gmail.com', Setting::get('admin_google_emails'));

        // 2. Toggle user admin status directly
        $toggleRes = $this->actingAs($admin)->post(route('admin.users.toggle-admin', $shopper->id));
        $toggleRes->assertRedirect();
        $this->assertFalse($shopper->fresh()->is_admin);
    }
}
