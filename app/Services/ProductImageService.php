<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImageService
{
    public function add(Product $product, UploadedFile $upload): void
    {
        if ($upload->getSize() > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'Maksimal lima gambar, masing-masing 2 MB.']);
        }
        $mime = $upload->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages(['image' => 'Gunakan gambar JPEG, PNG, atau WebP.']);
        }
        $info = @getimagesize($upload->getRealPath());
        if (! $info || $info[0] > 4096 || $info[1] > 4096 || $info[0] < 100 || $info[1] < 100) {
            throw ValidationException::withMessages(['image' => 'Dimensi gambar harus 100–4096 piksel.']);
        }
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($upload->getRealPath()),
            'image/png' => @imagecreatefrompng($upload->getRealPath()),
            default => @imagecreatefromwebp($upload->getRealPath()),
        };
        if (! $source) {
            throw ValidationException::withMessages(['image' => 'Gambar tidak dapat dibaca.']);
        }
        try {
            $scale = min(1, 1600 / max($info[0], $info[1]));
            $width = max(1, (int) round($info[0] * $scale));
            $height = max(1, (int) round($info[1] * $scale));
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
            ob_start();
            if (! imagewebp($image, null, 82)) {
                ob_end_clean();
                imagedestroy($image);
                throw ValidationException::withMessages(['image' => 'Gambar tidak dapat diproses.']);
            }
            $bytes = ob_get_clean();
            imagedestroy($image);
        } finally {
            imagedestroy($source);
        }
        $disk = Storage::disk(config('filesystems.product_disk'));
        $uploadedPath = null;
        try {
            DB::transaction(function () use ($product, $bytes, $disk, &$uploadedPath) {
                $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $count = $locked->images()->count();
                if ($count >= 5) {
                    throw ValidationException::withMessages(['image' => 'Maksimal lima gambar per produk.']);
                }
                $path = 'products/'.$locked->id.'/'.Str::uuid().'.webp';
                if (! $disk->put($path, $bytes, ['visibility' => 'public', 'ContentType' => 'image/webp'])) {
                    throw ValidationException::withMessages(['image' => 'Gambar gagal disimpan.']);
                }
                $uploadedPath = $path;
                $locked->images()->create(['object_path' => $path, 'alt_text' => $locked->name,
                    'sort_order' => $count, 'is_cover' => $count === 0]);
                $locked->increment('lock_version');
            });
        } catch (\Throwable $e) {
            // Also compensate when the transaction fails while committing, not only
            // when inserting the image record fails inside the callback.
            if ($uploadedPath !== null) {
                $disk->delete($uploadedPath);
            }
            throw $e;
        }
    }
}
