<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock): Product
    {
        $category = Category::create(['code' => 'ram', 'name' => 'RAM', 'slug' => 'ram', 'sort_order' => 1]);
        $product = Product::create([
            'category_id' => $category->id, 'sku' => 'RAM-STK-1', 'slug' => 'ram-stok', 'name' => 'RAM Stok',
            'specifications' => ['capacity_gb' => 16], 'price' => 750000, 'status' => 'published',
        ]);
        // Stock is not mass-assignable; production changes go through AdjustProductStock.
        $product->forceFill(['stock_quantity' => $stock])->save();

        return $product;
    }

    public function test_catalog_and_detail_show_exact_stock_quantity(): void
    {
        $product = $this->product(1250);

        $this->get('/')->assertOk()->assertSeeInOrder(['Stok:', '1.250', 'unit'])->assertDontSee('Stok habis');
        $this->get('/produk/'.$product->slug)->assertOk()->assertSeeInOrder(['Stok:', '1.250', 'unit']);
    }

    public function test_zero_stock_is_labelled_out_of_stock(): void
    {
        $product = $this->product(0);

        $this->get('/')->assertOk()->assertSee('Stok habis')->assertDontSee('Stok:');
        $this->get('/produk/'.$product->slug)->assertOk()->assertSee('Stok habis');
    }
}
