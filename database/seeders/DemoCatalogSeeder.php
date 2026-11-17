<?php

namespace Database\Seeders;

use App\Actions\AdjustProductStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use App\Support\CategorySpecifications;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DemoCatalogSeeder extends Seeder
{
    public function run(ProductImageService $images, AdjustProductStock $stock): void
    {
        $items = json_decode(file_get_contents(__DIR__.'/data/demo-products.json'), true, 512, JSON_THROW_ON_ERROR);

        // Check the entire bundle before writing products or Storage objects.
        foreach ($items as $item) {
            if (! isset(CategorySpecifications::FIELDS[$item['category']])) {
                throw new RuntimeException('Kategori demo tidak dikenal: '.$item['category']);
            }
            Validator::make($item, CategorySpecifications::rules($item['category']))->validate();
            $path = __DIR__.'/data/images/'.$item['image']['file'];
            if (! is_file($path) || hash_file('sha256', $path) !== $item['image']['sha256']) {
                throw new RuntimeException('Foto demo hilang atau berubah: '.$item['image']['file']);
            }
        }

        $actor = User::query()->where('is_active', true)->orderBy('id')->first();
        if (! $actor) {
            throw new RuntimeException('Seeder demo membutuhkan admin aktif untuk riwayat stok. Jalankan db:seed dengan SEED_ADMIN_EMAIL/PASSWORD atau admin:provision terlebih dahulu.');
        }

        $this->call(CategorySeeder::class);
        $categories = Category::query()->pluck('id', 'code');
        $disk = Storage::disk(config('filesystems.product_disk'));
        $created = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $uploadedPaths = [];
            try {
                // Commit one complete product at a time; a failed run can be replayed.
                $inserted = DB::transaction(function () use ($item, $categories, $actor, $images, $stock, &$uploadedPaths) {
                    if (Product::withTrashed()->whereRaw('lower(sku) = ?', [strtolower($item['sku'])])->exists()) {
                        return false;
                    }

                    $product = Product::query()->create([
                        'category_id' => $categories[$item['category']],
                        'sku' => $item['sku'],
                        'slug' => Str::slug($item['name']).'-'.strtolower($item['sku']),
                        'name' => $item['name'],
                        'description' => $item['description']."\n\nData simulasi katalog. Harga dan stok merupakan contoh, bukan penawaran atau persediaan aktual.",
                        'specifications' => $item['specifications'],
                        'price' => $item['price'],
                        'status' => 'draft',
                    ]);

                    $images->add($product, new UploadedFile(
                        __DIR__.'/data/images/'.$item['image']['file'],
                        $item['image']['file'],
                        test: true,
                    ));
                    $uploadedPaths = $product->images()->pluck('object_path')->all();
                    $product->refresh();

                    $stock->handle($product, $actor->id, $item['stock_quantity'],
                        'Stok awal simulasi dari DemoCatalogSeeder', $product->lock_version, (string) Str::uuid());
                    $product->refresh()->update(['status' => 'published']);

                    return true;
                });
            } catch (Throwable $e) {
                // ProductImageService handles its own failures. Also compensate if
                // stock initialization, publication, or the outer commit fails.
                if ($uploadedPaths !== []) {
                    $disk->delete($uploadedPaths);
                }
                throw $e;
            }

            if ($inserted) {
                $created++;
                $this->command?->info('Dibuat: '.$item['sku'].' - '.$item['name']);
            } else {
                $skipped++;
                $this->command?->line('Dilewati (SKU sudah ada, termasuk arsip): '.$item['sku']);
            }
        }

        $this->command?->info("Demo katalog: {$created} dibuat, {$skipped} dilewati. Disk foto: ".config('filesystems.product_disk'));
    }
}
