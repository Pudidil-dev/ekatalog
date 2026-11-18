<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OptionalSpecificationsTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::create(['code' => 'ssd', 'name' => 'SSD', 'slug' => 'ssd', 'sort_order' => 1]);
    }

    public function test_admin_form_has_no_specification_card(): void
    {
        $this->category();
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->get('/admin/products/create')->assertOk()
            ->assertDontSee('<h2>Spesifikasi</h2>', false)
            ->assertDontSee('name="specifications[', false);
    }

    public function test_product_can_be_created_without_specifications_and_is_stored_as_json_object(): void
    {
        $category = $this->category();
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->post('/admin/products', [
            'category_id' => $category->id, 'name' => 'SSD Tanpa Spek', 'sku' => 'ssd-tanpa-spek',
            'price' => 450000, 'status' => 'draft', 'initial_stock' => 3, 'initial_reason' => 'Stok awal',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'SSD-TANPA-SPEK')->sole();
        $this->assertSame([], $product->specifications);
        // PostgreSQL requires a JSON object; an empty PHP array must not become "[]".
        $this->assertSame('{}', DB::table('products')->where('id', $product->id)->value('specifications'));
        $this->assertSame(3, $product->stock_quantity);
    }

    public function test_editing_without_specification_fields_keeps_saved_specifications(): void
    {
        $category = $this->category();
        $product = Product::create([
            'category_id' => $category->id, 'sku' => 'SSD-LAMA-1', 'slug' => 'ssd-lama-1', 'name' => 'SSD Lama',
            'specifications' => ['capacity_gb' => 512, 'interface' => 'NVMe'], 'price' => 500000, 'status' => 'draft',
        ]);
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->put('/admin/products/'.$product->slug, [
            'category_id' => $category->id, 'name' => 'SSD Lama Diubah', 'sku' => 'SSD-LAMA-1',
            'price' => 520000, 'status' => 'draft', 'version' => $product->fresh()->lock_version,
        ])->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertSame('SSD Lama Diubah', $fresh->name);
        $this->assertSame(['capacity_gb' => 512, 'interface' => 'NVMe'], $fresh->specifications);
    }

    public function test_public_pages_hide_specifications_when_empty(): void
    {
        $category = $this->category();
        $product = Product::create([
            'category_id' => $category->id, 'sku' => 'SSD-KOSONG-1', 'slug' => 'ssd-kosong', 'name' => 'SSD Kosong',
            'specifications' => [], 'price' => 400000, 'status' => 'published',
        ]);

        $this->get('/')->assertOk()->assertSee('SSD Kosong')->assertDontSee('class="card-spec"', false);
        $this->get('/produk/'.$product->slug)->assertOk()->assertDontSee('Spesifikasi produk');
    }
}
