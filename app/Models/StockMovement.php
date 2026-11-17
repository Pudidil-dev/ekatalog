<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'admin_id', 'quantity_before', 'quantity_after', 'delta', 'request_version', 'reason', 'request_key'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'product_id' => 'integer', 'admin_id' => 'integer', 'quantity_after' => 'integer', 'request_version' => 'integer'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
