<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class AdminRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_is_a_literal_search_term_in_catalog_admin_and_excel(): void
    {
        $category = Category::create(['code' => 'ssd', 'name' => 'SSD', 'slug' => 'ssd', 'sort_order' => 1]);
        foreach (['SSD 500GB', 'Drive tanpa angka'] as $index => $name) {
            Product::create([
                'category_id' => $category->id, 'name' => $name, 'sku' => 'SEARCH-'.($index ? 'OTHER' : 'MATCH'),
                'slug' => 'search-'.($index ? 'other' : 'match'), 'price' => 500000,
                'status' => 'published', 'specifications' => ['capacity_gb' => 500],
            ]);
        }

        $this->get('/?q=0')->assertOk()->assertSee('SSD 500GB')->assertDontSee('Drive tanpa angka')
            ->assertSee('q=0&amp;category=ssd', false)->assertSee('Reset filter');
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->get('/admin/products?q=0')->assertOk()->assertSee('SSD 500GB')->assertDontSee('Drive tanpa angka')
            ->assertSee('Reset');

        $response = $this->get('/admin/export?q=0')->assertOk()->assertDownload('produk-ekatalog.xlsx');
        $reader = new Reader;
        $reader->open($response->baseResponse->getFile()->getPathname());
        $names = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $names[] = $row->getCells()[1]->getValue();
                }
            }
        } finally {
            $reader->close();
        }
        $this->assertContains('SSD 500GB', $names);
        $this->assertNotContains('Drive tanpa angka', $names);
    }

    public function test_password_change_rejects_values_beyond_bcrypt_byte_limit_without_server_error(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'password' => 'Current-password-for-test']);
        $this->actingAs($admin);
        $hash = $admin->password;

        foreach ([str_repeat('a', 73), str_repeat('é', 40), "Valid-password\0hidden"] as $password) {
            $this->post('/admin/password', [
                'current_password' => 'Current-password-for-test',
                'password' => $password, 'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
            $this->assertSame($hash, $admin->fresh()->password);
        }
    }

    public function test_valid_password_change_works_and_wrong_current_password_is_rejected(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'password' => 'Current-password-for-test']);
        $this->actingAs($admin);
        $payload = ['password' => 'New-password-for-test', 'password_confirmation' => 'New-password-for-test'];
        $this->post('/admin/password', [...$payload, 'current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
        $this->post('/admin/password', [...$payload, 'current_password' => 'Current-password-for-test'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('New-password-for-test', $admin->fresh()->password));
    }
}
