<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_menu_contains_all_admin_links_and_logout(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true, 'name' => 'Admin Toko']));

        $html = $this->get('/admin/products')->assertOk()->getContent();
        $start = strpos($html, '<details class="admin-mobile-nav"');
        $this->assertNotFalse($start, 'Mobile admin menu missing');
        $menu = substr($html, $start, strpos($html, '</details>', $start) - $start);

        foreach (['/admin/products', '/admin/settings', '/admin/export', 'Lihat katalog', 'Admin Toko'] as $expected) {
            $this->assertStringContainsString($expected, $menu);
        }
        $this->assertStringContainsString('action="'.route('admin.logout').'"', $menu);
        $this->assertStringContainsString('>Keluar</button>', $menu);
        $this->assertStringContainsString('aria-current="page"', $menu);
    }

    public function test_logout_from_mobile_menu_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->post(route('admin.logout'))->assertRedirect();
        $this->assertGuest();
    }
}
