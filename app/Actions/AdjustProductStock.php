<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustProductStock
{
    public function handle(Product $product, int $actorId, int $quantity, string $reason, int $version, string $requestKey): StockMovement
    {
        if ($quantity < 0 || trim($reason) === '') {
            throw ValidationException::withMessages(['quantity' => 'Jumlah dan alasan stok wajib valid.']);
        }

        return DB::transaction(function () use ($product, $actorId, $quantity, $reason, $version, $requestKey) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $previous = StockMovement::query()->where('request_key', $requestKey)->first();
            if ($previous) {
                if ($previous->product_id !== $locked->id || $previous->admin_id !== $actorId || $previous->quantity_after !== $quantity || $previous->request_version !== $version || $previous->reason !== trim($reason)) {
                    throw ValidationException::withMessages(['request_key' => 'Kunci permintaan sudah dipakai untuk perubahan lain.']);
                }

                return $previous;
            }
            if ($locked->lock_version !== $version) {
                throw ValidationException::withMessages(['version' => 'Stok telah berubah. Muat data terbaru sebelum menyimpan.']);
            }
            $before = $locked->stock_quantity;
            $locked->stock_quantity = $quantity;
            $locked->lock_version++;
            $locked->stock_updated_at = now();
            $locked->save();

            return StockMovement::query()->create([
                'product_id' => $locked->id, 'admin_id' => $actorId,
                'quantity_before' => $before, 'quantity_after' => $quantity,
                'delta' => $quantity - $before, 'request_version' => $version, 'reason' => trim($reason), 'request_key' => $requestKey,
            ]);
        }, 3);
    }
}
