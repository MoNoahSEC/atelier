<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDirectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_direct_link_redirects_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect(route('admin.login'));
    }

    public function test_guest_direct_link_quick_edit_redirects_to_login(): void
    {
        $response = $this->get('/admin/quick-edit');
        $response->assertRedirect(route('admin.login'));
    }

    public function test_guest_direct_admin_root_redirects_cleanly(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('admin.login'));
    }
}
