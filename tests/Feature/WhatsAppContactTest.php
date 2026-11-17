<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Database\Seeders\StoreSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppContactTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock = 3): Product
    {
        $category = Category::create(['code' => 'ssd', 'name' => 'SSD', 'slug' => 'ssd', 'sort_order' => 1]);

        $product = Product::create([
            'category_id' => $category->id, 'sku' => 'SSD-WA-1', 'slug' => 'ssd-wa', 'name' => 'SSD Kontak',
            'specifications' => ['capacity_gb' => 512], 'price' => 500000, 'status' => 'published',
        ]);
        // Stock is not mass-assignable; production changes go through AdjustProductStock.
        $product->forceFill(['stock_quantity' => $stock])->save();

        return $product;
    }

    public function test_catalog_card_and_detail_link_to_configured_whatsapp_number(): void
    {
        config(['store.whatsapp_number' => '6282253889636', 'app.url' => 'https://katalog.test']);
        $product = $this->product();
        $message = rawurlencode('Halo, saya ingin bertanya tentang SSD Kontak (SKU: SSD-WA-1). https://katalog.test/produk/ssd-wa');

        $this->get('/')->assertOk()
            ->assertSee('https://wa.me/6282253889636?text='.$message, false)
            ->assertSee('Tanya via WhatsApp')
            ->assertSee('href="https://wa.me/6282253889636"', false);
        $this->get('/produk/'.$product->slug)->assertOk()
            ->assertSee('https://wa.me/6282253889636?text='.$message, false)
            ->assertSee('Hubungi via WhatsApp');
    }

    public function test_out_of_stock_card_asks_for_availability(): void
    {
        config(['store.whatsapp_number' => '6282253889636']);
        $this->product(0);

        $this->get('/')->assertOk()->assertSee('Tanyakan ketersediaan');
    }

    public function test_admin_saved_number_overrides_configured_default(): void
    {
        config(['store.whatsapp_number' => '6282253889636']);
        StoreSetting::create(['store_name' => 'Toko', 'whatsapp_number' => '628111111111']);
        $this->product();

        $this->get('/')->assertOk()->assertSee('https://wa.me/628111111111?text=', false)
            ->assertDontSee('wa.me/6282253889636', false);
    }

    public function test_no_whatsapp_button_without_a_valid_number(): void
    {
        config(['store.whatsapp_number' => '+62 0812']);
        $this->product();

        $this->get('/')->assertOk()->assertDontSee('wa.me', false);
    }

    public function test_configured_number_is_normalized(): void
    {
        config(['store.whatsapp_number' => '+62 822-5388-9636']);

        $this->assertSame('6282253889636', StoreSetting::current()->whatsappNumber());
    }

    public function test_seeder_fills_empty_number_without_overwriting_admin_value(): void
    {
        config(['store.whatsapp_number' => '6282253889636']);
        $this->seed(StoreSettingSeeder::class);
        $this->assertSame('6282253889636', StoreSetting::current()->whatsapp_number);

        StoreSetting::current()->update(['whatsapp_number' => '628111111111']);
        $this->seed(StoreSettingSeeder::class);
        $this->assertSame('628111111111', StoreSetting::current()->fresh()->whatsapp_number);
        $this->assertSame(1, StoreSetting::count());
    }
}
