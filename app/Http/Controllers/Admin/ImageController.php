<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageController extends Controller
{
    public function store(Request $request, Product $product, ProductImageService $service)
    {
        $data = $request->validate(['image' => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png,webp']]);
        $service->add($product, $data['image']);

        return back()->with('success', 'Gambar berhasil ditambahkan.');
    }

    public function cover(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);
        DB::transaction(function () use ($product, $image) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->images()->whereKey($image->id)->exists(), 404);
            $product->images()->update(['is_cover' => false]);
            $product->images()->whereKey($image->id)->update(['is_cover' => true]);
            $locked->increment('lock_version');
        });

        return back()->with('success', 'Gambar utama berhasil diubah.');
    }

    public function destroy(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);
        $path = DB::transaction(function () use ($product, $image) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $current = $locked->images()->whereKey($image->id)->firstOrFail();
            if ($current->is_cover && $locked->status === 'published') {
                throw ValidationException::withMessages(['image' => 'Pilih gambar utama pengganti sebelum menghapus gambar ini.']);
            }
            $path = $current->object_path;
            $wasCover = $current->is_cover;
            $current->delete();
            if ($wasCover) {
                $locked->images()->first()?->update(['is_cover' => true]);
            }
            $locked->increment('lock_version');

            return $path;
        });
        Storage::disk(config('filesystems.product_disk'))->delete($path);

        return back()->with('success', 'Gambar berhasil dihapus.');
    }
}
