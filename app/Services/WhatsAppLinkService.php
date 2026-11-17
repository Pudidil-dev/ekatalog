<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StoreSetting;

class WhatsAppLinkService
{
    public function forProduct(Product $product, StoreSetting $store): ?string
    {
        if (! $store->whatsapp_number) {
            return null;
        }

        $message = sprintf('Halo, saya ingin bertanya tentang %s (SKU: %s). %s',
            // Do not derive an outbound link from the untrusted HTTP Host header.
            $product->name, $product->sku, rtrim(config('app.url'), '/').route('catalog.show', $product, false));

        return 'https://wa.me/'.$store->whatsapp_number.'?text='.rawurlencode($message);
    }
}
