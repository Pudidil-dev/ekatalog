<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Queries\CatalogQuery;
use App\Services\WhatsAppLinkService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request, CatalogQuery $catalog, WhatsAppLinkService $links)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:80']]);
        $products = $catalog->published($filters['q'] ?? null, $filters['category'] ?? null)->latest('id')->paginate(24)->withQueryString();
        $store = StoreSetting::current();
        $whatsappLinks = $products->getCollection()->mapWithKeys(fn (Product $product) => [$product->id => $links->forProduct($product, $store)]);

        return view('catalog.index', [
            'products' => $products, 'categories' => Category::orderBy('sort_order')->get(),
            'store' => $store, 'whatsappLinks' => $whatsappLinks,
        ]);
    }

    public function show(Product $product, WhatsAppLinkService $links)
    {
        abort_unless($product->status === 'published', 404);
        $product->load(['category', 'images']);
        $store = StoreSetting::current();

        return view('catalog.show', ['product' => $product, 'store' => $store, 'whatsapp' => $links->forProduct($product, $store)]);
    }
}
