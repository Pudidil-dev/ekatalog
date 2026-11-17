<?php

namespace App\Queries;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class CatalogQuery
{
    public function published(?string $search = null, ?string $category = null): Builder
    {
        return Product::query()->with(['category', 'images'])
            ->where('status', 'published')
            ->when($category, fn (Builder $query) => $query->whereHas('category', fn (Builder $c) => $c->where('slug', $category)))
            ->when($search !== null && $search !== '', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $query->where(fn (Builder $q) => $q->where('name', $operator, $like)->orWhere('sku', $operator, $like));
            });
    }
}
