<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class CatalogAndAdminTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::create(['code' => 'ssd', 'name' => 'SSD', 'slug' => 'ssd', 'sort_order' => 1]);
    }

    private function product(Category $category, string $status = 'published', string $name = 'Drive Cepat'): Product
    {
        return Product::create([
            'category_id' => $category->id, 'sku' => 'SSD-'.Str::random(10),
            'slug' => Str::slug($name).'-'.Str::random(6), 'name' => $name,
            'specifications' => ['capacity_gb' => 512, 'interface' => 'NVMe', 'protocol' => 'PCIe', 'form_factor' => 'M.2'],
            'price' => 500000, 'status' => $status,
        ]);
    }

    private function payload(Category $category): array
    {
        return [
            'category_id' => $category->id, 'name' => 'SSD Baru', 'sku' => 'ssd-new',
            'specifications' => ['capacity_gb' => 512, 'interface' => 'NVMe', 'protocol' => 'PCIe', 'form_factor' => 'M.2'],
            'price' => 500000, 'status' => 'draft', 'initial_stock' => 5, 'initial_reason' => 'Stok awal',
        ];
    }

    public function test_catalog_only_shows_published_nonarchived_products_and_filters_by_category_and_search(): void
    {
        $ssd = $this->category();
        $ram = Category::create(['code' => 'ram', 'name' => 'RAM', 'slug' => 'ram', 'sort_order' => 2]);
        $visible = $this->product($ssd);
        $this->product($ssd, 'draft', 'Rahasia');
        $this->product($ram, 'published', 'Memori');
        $this->product($ssd, 'published', 'Arsip')->delete();

        $this->get('/?category=ssd&q=Drive')->assertOk()->assertSee($visible->name)->assertDontSee('Rahasia')->assertDontSee('Memori')->assertDontSee('Arsip');
        $this->get('/produk/'.$visible->slug)->assertOk()->assertSee($visible->name);
        $this->get('/?q=%25')->assertOk()->assertDontSee($visible->name);
    }

    public function test_draft_and_archived_detail_are_not_public(): void
    {
        $category = $this->category();
        $draft = $this->product($category, 'draft');
        $archived = $this->product($category, 'published', 'Arsip');
        $archived->delete();
        $this->get('/produk/'.$draft->slug)->assertNotFound();
        $this->get('/produk/'.$archived->slug)->assertNotFound();
    }

    public function test_admin_auth_requires_active_account_and_logout_ends_session(): void
    {
        $active = User::factory()->create(['email' => 'admin@example.test', 'password' => 'correct-password', 'is_active' => true]);
        $inactive = User::factory()->create(['email' => 'inactive@example.test', 'password' => 'correct-password', 'is_active' => false]);
        $this->get('/admin/products')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => $inactive->email, 'password' => 'correct-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $active->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => ' ADMIN@example.test ', 'password' => 'correct-password'])->assertRedirect('/admin/products');
        $this->assertAuthenticatedAs($active);
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        $this->actingAs($inactive)->get('/admin/products')->assertForbidden();
    }

    public function test_admin_can_create_draft_then_publish_only_after_cover_exists(): void
    {
        $category = $this->category();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->post('/admin/products', $this->payload($category))->assertRedirect();
        $product = Product::where('sku', 'SSD-NEW')->firstOrFail();
        $this->assertSame('draft', $product->status);
        $this->assertSame(5, $product->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $update = array_diff_key($this->payload($category), array_flip(['initial_stock', 'initial_reason']));
        $update['sku'] = 'SSD-NEW';
        $update['status'] = 'published';
        $update['version'] = $product->lock_version;
        $this->put('/admin/products/'.$product->slug, $update)->assertSessionHasErrors('status');
        $product->images()->create(['object_path' => 'fixtures/side.jpg', 'alt_text' => 'Sisi SSD', 'is_cover' => false]);
        $this->put('/admin/products/'.$product->slug, $update)->assertSessionHasErrors('status');
        $product->images()->create(['object_path' => 'fixtures/cover.jpg', 'alt_text' => 'SSD', 'is_cover' => true]);
        $this->put('/admin/products/'.$product->slug, [...$update, 'price' => 0])->assertSessionHasErrors('status');
        $this->put('/admin/products/'.$product->slug, $update)->assertSessionHasNoErrors();
        $this->assertSame('published', $product->fresh()->status);
        $this->put('/admin/products/'.$product->slug, $update)->assertSessionHasErrors('version');
    }

    public function test_stock_retries_are_idempotent_but_conflicting_or_stale_requests_are_rejected(): void
    {
        $category = $this->category();
        $product = $this->product($category);
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $url = '/admin/products/'.$product->slug.'/stock';
        $change = ['quantity' => 7, 'reason' => 'Penerimaan barang', 'version' => 0, 'request_key' => (string) Str::uuid()];
        $this->patch($url, $change)->assertSessionHasNoErrors();
        $this->patch($url, $change)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(1, $product->fresh()->lock_version);
        $this->patch($url, [...$change, 'quantity' => 8])->assertSessionHasErrors('request_key');
        $this->patch($url, [...$change, 'version' => 1])->assertSessionHasErrors('request_key');
        $this->patch($url, [...$change, 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('version');
        $this->patch($url, [...$change, 'quantity' => -1, 'version' => 1, 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('quantity');
        $this->assertSame(7, $product->fresh()->stock_quantity);
    }

    public function test_upload_and_cover_changes_invalidate_a_stale_product_edit(): void
    {
        $category = $this->category();
        $product = $this->product($category, 'draft');
        $this->actingAs(User::factory()->create(['is_active' => true]));
        config(['filesystems.product_disk' => 'public']);
        Storage::fake('public');
        $this->post('/admin/products/'.$product->slug.'/images', ['image' => UploadedFile::fake()->image('front.png', 200, 200)])->assertSessionHasNoErrors();
        $this->assertSame(1, $product->fresh()->lock_version);
        $image = $product->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->object_path);
        $this->assertTrue($image->is_cover);
        $payload = array_diff_key($this->payload($category), array_flip(['initial_stock', 'initial_reason']));
        $payload['version'] = 0;
        $this->put('/admin/products/'.$product->slug, $payload)->assertSessionHasErrors('version');
    }

    public function test_rejected_image_does_not_create_storage_object_or_database_record(): void
    {
        $product = $this->product($this->category(), 'draft');
        $this->actingAs(User::factory()->create(['is_active' => true]));
        config(['filesystems.product_disk' => 'public']);
        Storage::fake('public');

        $this->post('/admin/products/'.$product->slug.'/images', [
            'image' => UploadedFile::fake()->createWithContent('fake.png', '<script>alert(1)</script>'),
        ])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('product_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_export_downloads_filtered_xlsx(): void
    {
        $category = $this->category();
        $this->product($category);
        $this->product($category, 'draft', 'Tidak Diekspor');
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->get('/admin/products?q=Drive&category=ssd&status=published')
            ->assertOk()
            ->assertSee('admin/export?q=Drive&amp;category=ssd&amp;status=published', false)
            ->assertDontSee('Tidak Diekspor');

        $response = $this->get('/admin/export?q=Drive&category=ssd&status=published');
        $response->assertOk()->assertDownload('produk-ekatalog.xlsx');
        $reader = new Reader;
        $reader->open($response->baseResponse->getFile()->getPathname());
        try {
            $values = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values[] = $row->getCells()[1]->getValue();
                }
            }
            $this->assertContains('Drive Cepat', $values);
            $this->assertNotContains('Tidak Diekspor', $values);
        } finally {
            $reader->close();
        }
    }

    public function test_export_treats_whitespace_prefixed_formulas_as_text(): void
    {
        $product = $this->product($this->category(), 'published', " \t=HYPERLINK(\"https://example.test\")");
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $response = $this->get('/admin/export');
        $response->assertOk()->assertDownload('produk-ekatalog.xlsx');
        $reader = new Reader;
        $reader->open($response->baseResponse->getFile()->getPathname());
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($row->getCells()[0]->getValue() === $product->sku) {
                        $this->assertSame("' \t=HYPERLINK(\"https://example.test\")", $row->getCells()[1]->getValue());

                        return;
                    }
                }
            }
            $this->fail('Export row missing');
        } finally {
            $reader->close();
        }
    }

    public function test_whatsapp_link_uses_configured_host_not_request_host(): void
    {
        config(['app.url' => 'https://catalog.example.test']);
        StoreSetting::query()->updateOrCreate(['id' => 1], ['store_name' => 'Toko', 'whatsapp_number' => '628123456789']);
        $product = $this->product($this->category());
        $response = $this->withServerVariables(['HTTP_HOST' => 'attacker.example.test'])->get('/produk/'.$product->slug);
        $response->assertOk()->assertSee(rawurlencode('https://catalog.example.test/produk/'.$product->slug), false)
            ->assertDontSee(rawurlencode('https://attacker.example.test/produk/'.$product->slug), false);
    }
}
