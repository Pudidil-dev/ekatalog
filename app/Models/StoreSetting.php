<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['store_name', 'short_description', 'whatsapp_number', 'address', 'instagram_url', 'facebook_url'];

    public static function current(): self
    {
        return static::query()->first() ?? new static(['store_name' => 'E-Katalog']);
    }
}
