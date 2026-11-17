<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StoreSetting;

class WhatsAppLinkService
{
    public function forStore(StoreSetting $store): ?string
    {
        $number = $store->whatsappNumber();

        return $number ? 'https://wa.me/'.$number : null;
    }

    public function forProduct(Product $product, StoreSetting $store): ?string
    {
        $base = $this->forStore($store);

        if (! $base) {
            return null;
        }

        $message = sprintf('Halo, saya ingin bertanya tentang %s (SKU: %s). %s',
            // Do not derive an outbound link from the untrusted HTTP Host header.
            $product->name, $product->sku, rtrim(config('app.url'), '/').route('catalog.show', $product, false));

        return $base.'?text='.rawurlencode($message);
    }
}
