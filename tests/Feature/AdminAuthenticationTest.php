<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated guest is redirected to admin login.
     */
    public function test_guest_direct_access_to_admin_panel_redirects_to_login(): void
    {
        $response = $this->get('/admin/quick-edit');
        $response->assertRedirect(route('admin.login'));

        $response = $this->get('/admin/dashboard');
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Authenticated admin can access panel.
     */
    public function test_authenticated_admin_can_access_panel(): void
    {
        $admin = User::create([
            'name'       => 'Administrator',
            'email'      => 'admin@atelier.com',
            'password'   => bcrypt('password'),
            'is_admin'   => true,
            'admin_role' => 'super_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/quick-edit');
        $response->assertStatus(200);
        $response->assertSee('Admin', false);
    }
}
