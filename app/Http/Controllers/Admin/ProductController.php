<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AdjustProductStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\CategorySpecifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('category')->latest('id');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'exists:categories,slug'],
            'status' => ['nullable', 'in:draft,published'],
        ]);
        $query->when($filters['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        if (isset($filters['q']) && $filters['q'] !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(fn ($q) => $q->where('name', $operator, $like)->orWhere('sku', $operator, $like));
        }

        return view('admin.products.index', [
            'products' => $query->paginate(20)->withQueryString(),
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.products.form', [
            'product' => new Product(['status' => 'draft', 'category_id' => $categories->first()?->id]),
            'categories' => $categories,
        ]);
    }

    public function store(SaveProductRequest $request, AdjustProductStock $adjust)
    {
        $data = $request->validated();
        $category = Category::findOrFail($data['category_id']);
        // A new product cannot have a cover yet, regardless of a crafted status value.
        $this->requirePublishable($data, false);
        $product = DB::transaction(function () use ($data, $category, $request, $adjust) {
            $product = Product::create([
                'category_id' => $category->id, 'sku' => $data['sku'],
                'slug' => Str::slug($data['name']).'-'.strtolower(Str::random(7)),
                'name' => $data['name'], 'description' => $data['description'] ?? null,
                'specifications' => $this->specifications($data['specifications'] ?? [], $category->code),
                'price' => $data['price'], 'status' => $data['status'],
            ]);
            $adjust->handle($product, $request->user()->id, (int) $data['initial_stock'], $data['initial_reason'], 0, (string) Str::uuid());

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Produk berhasil dibuat.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product->load(['images', 'category']), 'categories' => Category::orderBy('sort_order')->get()]);
    }

    public function update(SaveProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $category = Category::findOrFail($data['category_id']);
        DB::transaction(function () use ($product, $data, $category) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($locked->lock_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Data telah berubah. Muat ulang sebelum menyimpan.']);
            }
            $this->requirePublishable($data, $locked->images()->where('is_cover', true)->exists());
            $locked->fill([
                'name' => $data['name'], 'sku' => $data['sku'], 'category_id' => $category->id,
                'description' => $data['description'] ?? null,
                // Keep saved specifications when the form does not submit any.
                'specifications' => $this->specifications($data['specifications'] ?? $locked->specifications, $category->code),
                'price' => $data['price'], 'status' => $data['status'],
            ]);
            $locked->lock_version++;
            $locked->save();
        });

        return back()->with('success', 'Produk berhasil disimpan.');
    }

    /**
     * Keep only non-empty fields that belong to the selected category.
     */
    private function specifications(array $specifications, string $categoryCode): array
    {
        return array_filter(
            array_intersect_key($specifications, CategorySpecifications::FIELDS[$categoryCode] ?? []),
            fn ($value) => $value !== null && $value !== '',
        );
    }

    private function requirePublishable(array $data, bool $hasImage): void
    {
        if ($data['status'] === 'published' && (! $hasImage || (int) $data['price'] < 1)) {
            throw ValidationException::withMessages(['status' => 'Untuk terbit, isi harga dan unggah gambar utama terlebih dahulu.']);
        }
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $locked->delete();
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diarsipkan.');
    }

    public function stock(Request $request, Product $product, AdjustProductStock $adjust)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'version' => ['required', 'integer', 'min:0'],
            'request_key' => ['required', 'uuid'],
        ]);
        $adjust->handle($product, $request->user()->id, (int) $data['quantity'], $data['reason'], (int) $data['version'], $data['request_key']);

        return back()->with('success', 'Stok berhasil diperbarui.');
    }

    public function history(Product $product)
    {
        return view('admin.products.history', ['product' => $product, 'movements' => $product->stockMovements()->with('admin')->paginate(30)]);
    }
}
