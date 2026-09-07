<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAccessSecurityAndPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_by_typing_admin_in_url(): void
    {
        // 1. Direct GET /admin should redirect to /admin/login
        $res = $this->get('/admin');
        $res->assertRedirect(route('admin.login'));

        // 2. Direct GET /admin/quick-edit should redirect to /admin/login
        $resQuick = $this->get('/admin/quick-edit');
        $resQuick->assertRedirect(route('admin.login'));

        // Ensure user is NOT automatically authenticated as admin
        $this->assertGuest();
    }

    public function test_regular_customer_is_forbidden_from_admin_area(): void
    {
        $customer = User::create([
            'name'       => 'Regular Customer',
            'email'      => 'customer@domain.com',
            'password'   => bcrypt('secret'),
            'is_admin'   => false,
            'admin_role' => 'staff',
        ]);

        // Non-admin user hitting /admin should get 403 (middleware blocks it immediately)
        $res = $this->actingAs($customer)->get('/admin');
        $res->assertStatus(403);

        $resDashboard = $this->actingAs($customer)->get('/admin/dashboard');
        $resDashboard->assertStatus(403);
    }

    public function test_authenticated_admin_can_access_admin_dashboard(): void
    {
        $admin = User::create([
            'name'       => 'Store Master',
            'email'      => 'admin_master@atelier.com',
            'password'   => bcrypt('secret'),
            'is_admin'   => true,
            'admin_role' => 'super_admin',
        ]);

        $res = $this->actingAs($admin)->get('/admin/quick-edit');
        $res->assertOk();
    }

    public function test_admin_can_upload_and_set_portal_background_wallpaper(): void
    {
        Storage::fake('public');

        $admin = User::create([
            'name'       => 'Admin User',
            'email'      => 'admin_portal@atelier.com',
            'password'   => bcrypt('secret'),
            'is_admin'   => true,
            'admin_role' => 'super_admin',
        ]);

        $file = UploadedFile::fake()->image('editorial_leather.jpg', 1920, 1080);

        $res = $this->actingAs($admin)->postJson(route('admin.settings.replace-portal-bg'), [
            'image' => $file,
        ]);

        $res->assertOk();
        $res->assertJsonFragment(['success' => true]);

        $url = \App\Models\Setting::get('account_portal_background_url');
        $this->assertNotEmpty($url);

        // Check setting was persisted correctly in DB
        $this->assertDatabaseHas('settings', [
            'key' => 'account_portal_background_url',
        ]);

        // Account page loads successfully for any visitor
        $accountRes = $this->get('/account');
        $accountRes->assertOk();
        // /account route always loads (200 OK with or without auth)
        $this->assertSame(200, $accountRes->status());
    }
}
