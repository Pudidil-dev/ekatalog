<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocalizationAndAdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Locale aplikasi pada lingkungan berjalan adalah bahasa Indonesia. Fallback juga "id" agar kunci yang hilang
        // tidak tertutupi oleh pesan bahasa Inggris bawaan Laravel.
        config(['app.locale' => 'id', 'app.fallback_locale' => 'id']);
        app()->setLocale('id');
        app('translator')->setFallback('id');
    }

    private function category(): Category
    {
        return Category::create(['code' => 'ssd', 'name' => 'SSD', 'slug' => 'ssd', 'sort_order' => 1]);
    }

    private function product(Category $category, string $name = 'SSD Uji', string $sku = 'SSD-UJI-01'): Product
    {
        return Product::create([
            'category_id' => $category->id, 'sku' => $sku, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'name' => $name, 'specifications' => ['capacity_gb' => 512, 'interface' => 'NVMe', 'protocol' => 'PCIe', 'form_factor' => 'M.2'],
            'price' => 500000, 'status' => 'published',
        ]);
    }

    private function assertReadableErrors(): void
    {
        $messages = session('errors')?->getBag('default')->all() ?? [];
        $this->assertNotEmpty($messages);
        foreach ($messages as $message) {
            $this->assertStringNotContainsString('validation.', $message);
            $this->assertDoesNotMatchRegularExpression('/(The|field|must|is required|has already been taken)/', $message);
        }
    }

    public function test_indonesian_validation_file_covers_every_framework_rule(): void
    {
        $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
            $keys = [];
            foreach ($lines as $key => $value) {
                if (in_array($prefix.$key, ['custom', 'attributes'], true)) {
                    continue;
                }
                $keys = [...$keys, ...(is_array($value) ? $flatten($value, $prefix.$key.'.') : [$prefix.$key])];
            }

            return $keys;
        };
        $english = $flatten(require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'));
        $indonesian = $flatten(require lang_path('id/validation.php'));

        $this->assertSame([], array_values(array_diff($english, $indonesian)));
        foreach (['Showing', 'to', 'of', 'results', 'Pagination Navigation', 'Go to page :page'] as $line) {
            $this->assertNotSame($line, __($line), $line);
        }
    }

    public function test_array_category_input_is_rejected_without_server_error(): void
    {
        $this->category();
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->from('/admin/products/create')->post('/admin/products', [
            'category_id' => [1], 'name' => 'SSD Baru', 'sku' => 'SSD-BARU', 'price' => 1, 'status' => 'draft',
            'initial_stock' => 1, 'initial_reason' => 'Stok awal', 'specifications' => ['capacity_gb' => 1],
        ])->assertRedirect('/admin/products/create')->assertSessionHasErrors(['category_id' => 'Kategori yang dipilih tidak valid.']);
    }

    public function test_product_validation_messages_are_indonesian_sentences(): void
    {
        $category = $this->category();
        $this->product($category, 'SSD Lama', 'SSD-LAMA');
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $payload = [
            'category_id' => 999, 'name' => '', 'sku' => 'ssd-lama', 'price' => -1, 'status' => 'draft',
            'initial_stock' => -1, 'initial_reason' => '', 'specifications' => [],
        ];

        $this->from('/admin/products/create')->post('/admin/products', $payload)->assertSessionHasErrors([
            'name' => 'Nama produk wajib diisi.',
            'category_id' => 'Kategori yang dipilih tidak valid.',
            'price' => 'Harga minimal 0.',
            'initial_stock' => 'Stok awal minimal 0.',
            'initial_reason' => 'Alasan stok awal wajib diisi.',
        ]);
        $this->assertReadableErrors();

        $this->from('/admin/products/create')->post('/admin/products', [...$payload, 'category_id' => $category->id, 'name' => 'SSD Baru'])
            ->assertSessionHasErrors(['sku' => 'SKU sudah digunakan.'])
            ->assertSessionDoesntHaveErrors('specifications.capacity_gb');
        $this->assertReadableErrors();
    }

    public function test_image_stock_and_settings_messages_are_indonesian_sentences(): void
    {
        $product = $this->product($this->category());
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->post('/admin/products/'.$product->slug.'/images', ['image' => UploadedFile::fake()->create('besar.png', 3000, 'image/png')])
            ->assertSessionHasErrors(['image' => 'Ukuran gambar maksimal 2048 KB.']);
        $this->post('/admin/products/'.$product->slug.'/images', ['image' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['image' => 'Gambar harus berformat jpg, jpeg, png, webp.']);
        $this->assertReadableErrors();

        $this->patch('/admin/products/'.$product->slug.'/stock', ['quantity' => -1, 'reason' => '', 'version' => 0, 'request_key' => (string) Str::uuid()])
            ->assertSessionHasErrors(['quantity' => 'Jumlah stok minimal 0.', 'reason' => 'Alasan perubahan wajib diisi.']);
        $this->assertReadableErrors();

        $this->put('/admin/settings', ['store_name' => 'RAKOM', 'whatsapp_number' => '08123456789', 'instagram_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors([
                'whatsapp_number' => 'Nomor WhatsApp harus 8–15 digit, diawali kode negara tanpa tanda + atau angka 0, misalnya 6281234567890.',
                'instagram_url' => 'URL Instagram harus berupa alamat https:// yang valid.',
            ]);
        $this->put('/admin/settings', ['store_name' => 'RAKOM', 'whatsapp_number' => '6281234567890123', 'facebook_url' => 'http://facebook.com/rakom'])
            ->assertSessionHasErrors([
                'whatsapp_number' => 'Nomor WhatsApp harus 8–15 digit, diawali kode negara tanpa tanda + atau angka 0, misalnya 6281234567890.',
                'facebook_url' => 'URL Facebook harus berupa alamat https:// yang valid.',
            ]);
        $this->assertReadableErrors();

        $this->post('/admin/password', ['current_password' => 'bukan-password', 'password' => 'PasswordBaru-123', 'password_confirmation' => 'PasswordBaru-123'])
            ->assertSessionHasErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        $this->assertReadableErrors();
    }

    public function test_catalog_pagination_uses_indonesian_labels(): void
    {
        $category = $this->category();
        for ($i = 1; $i <= 25; $i++) {
            $this->product($category, sprintf('SSD Halaman %02d', $i), sprintf('SSD-HAL-%02d', $i));
        }

        $this->get('/')->assertOk()
            ->assertSee('Berikutnya')->assertSee('Navigasi halaman')
            ->assertDontSee('pagination.next')->assertDontSee('pagination.previous')
            ->assertDontSee('Showing')->assertDontSee('results');
        $this->get('/?page=2')->assertOk()->assertSee('« Sebelumnya')->assertDontSee('pagination.previous')
            ->assertDontSee('&amp;laquo;', false);
    }

    public function test_admin_product_rows_carry_labels_for_the_stacked_mobile_layout(): void
    {
        $this->product($this->category());
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $html = $this->get('/admin/products')->assertOk()->getContent();
        $this->assertStringContainsString('class="stack-table"', $html);
        foreach (['Produk / SKU', 'Kategori', 'Harga', 'Stok', 'Publikasi', 'Tindakan'] as $label) {
            $this->assertStringContainsString('data-label="'.$label.'"', $html);
        }
        $this->assertStringContainsString('role="columnheader">Tindakan</th>', $html);
        $this->assertSame(6, substr_count($html, 'role="cell" data-label='));
    }
}
