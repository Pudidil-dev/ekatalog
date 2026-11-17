<?php

namespace Tests\Feature;

use App\Actions\AdjustProductStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.product_disk' => 'supabase', 'seed_accounts.accounts' => []]);
        Storage::fake('supabase');
    }

    public function test_demo_has_correct_categories_photos_specs_and_audited_stock_and_is_public(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $this->seed(DemoCatalogSeeder::class);

        $this->assertDatabaseCount('products', 10);
        $this->assertDatabaseCount('product_images', 10);
        $this->assertDatabaseCount('stock_movements', 10);
        $this->assertCount(10, Storage::disk('supabase')->allFiles('products'));

        foreach (Category::with('products')->get() as $category) {
            $this->assertCount(2, $category->products);
            $response = $this->get('/?category='.$category->slug)->assertOk();
            foreach ($category->products as $product) {
                $response->assertSee($product->name);
                $this->assertSame('published', $product->status);
                $this->assertStringContainsString('Data simulasi', $product->description);
                $cover = $product->images()->sole();
                $this->assertTrue($cover->is_cover);
                $this->assertSame($product->name, $cover->alt_text);
                Storage::disk('supabase')->assertExists($cover->object_path);
                $bytes = Storage::disk('supabase')->get($cover->object_path);
                $this->assertSame('image/webp', getimagesizefromstring($bytes)['mime']);
                $this->get('/produk/'.$product->slug)->assertOk()->assertSee($cover->url(), false);
                $movement = $product->stockMovements()->sole();
                $this->assertSame($admin->id, $movement->admin_id);
                $this->assertEquals(0, $movement->quantity_before);
                $this->assertSame($product->stock_quantity, $movement->quantity_after);
                $this->assertEquals($product->stock_quantity, $movement->delta);
                $this->assertNotNull($product->stock_updated_at);
            }
        }

        $this->assertSame(480, Product::where('sku', 'DEMO-SSD-A400-480')->sole()->specifications['capacity_gb']);
        $this->assertSame('DDR5', Product::where('sku', 'DEMO-RAM-BEAST-D5-16')->sole()->specifications['generation']);
        $this->assertSame('Micro-ATX', Product::where('sku', 'DEMO-MB-B550M-VDH')->sole()->specifications['form_factor']);
        $this->assertSame(8, Product::where('sku', 'DEMO-CPU-R7-5700X')->sole()->specifications['cores']);
        $outOfStock = Product::where('sku', 'DEMO-GPU-3060-12')->sole();
        $this->assertSame(12, $outOfStock->specifications['vram_gb']);
        $this->assertSame(0, $outOfStock->stock_quantity);
    }

    public function test_replay_preserves_edits_stock_images_and_archived_products_case_insensitively(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $this->seed(DemoCatalogSeeder::class);
        $edited = Product::where('sku', 'DEMO-SSD-A400-480')->sole();
        app(AdjustProductStock::class)->handle($edited, $admin->id, 9, 'Stok dikoreksi admin', $edited->lock_version, (string) Str::uuid());
        $edited->refresh()->update(['name' => 'Nama dari admin', 'price' => 700000, 'status' => 'draft', 'sku' => strtolower($edited->sku)]);
        $archived = Product::where('sku', 'DEMO-SSD-NV3-1TB')->sole();
        $archived->delete();
        $files = Storage::disk('supabase')->allFiles('products');
        $snapshot = Product::withTrashed()->orderBy('id')->get()->toArray();

        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame($snapshot, Product::withTrashed()->orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('product_images', 10);
        $this->assertDatabaseCount('stock_movements', 11);
        $this->assertSame($files, Storage::disk('supabase')->allFiles('products'));
        $this->assertTrue($archived->fresh()->trashed());
    }

    public function test_failure_after_upload_rolls_back_product_and_removes_its_photo_then_retry_succeeds(): void
    {
        User::factory()->create(['is_active' => true]);
        $this->mock(AdjustProductStock::class, function ($mock) {
            $mock->shouldReceive('handle')->once()->andThrow(new RuntimeException('Stock write failed'));
        });

        try {
            $this->seed(DemoCatalogSeeder::class);
            $this->fail('The stock failure should stop the seeder.');
        } catch (RuntimeException $e) {
            $this->assertSame('Stock write failed', $e->getMessage());
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_images', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame([], Storage::disk('supabase')->allFiles('products'));

        $this->app->forgetInstance(AdjustProductStock::class);
        $this->seed(DemoCatalogSeeder::class);
        $this->assertDatabaseCount('products', 10);
    }

    public function test_missing_active_admin_fails_before_writing_catalog_or_uploading_photos(): void
    {
        User::factory()->create(['is_active' => false]);
        try {
            $this->seed(DemoCatalogSeeder::class);
            $this->fail('An active admin is required for the stock audit.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('admin aktif', $e->getMessage());
        }

        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('supabase')->allFiles());
    }

    public function test_normal_database_seeder_does_not_add_demo_inventory(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('categories', 5);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('supabase')->allFiles());
    }
}
