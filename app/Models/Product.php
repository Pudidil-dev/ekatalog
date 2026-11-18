<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'sku', 'slug', 'name', 'description', 'specifications', 'price', 'status', 'lock_version'];

    protected function casts(): array
    {
        return ['stock_quantity' => 'integer', 'lock_version' => 'integer', 'stock_updated_at' => 'datetime'];
    }

    /**
     * Specifications are optional. They are stored as a JSON object ("{}" when
     * empty) to satisfy the PostgreSQL products_specs_object constraint, which
     * a plain array cast would break by writing "[]".
     */
    protected function specifications(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? [] : (json_decode($value, true) ?: []),
            set: fn (?array $value) => json_encode((object) ($value ?? []), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }
}
