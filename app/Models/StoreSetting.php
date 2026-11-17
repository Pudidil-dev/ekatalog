<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    private const WHATSAPP_PATTERN = '/^[1-9][0-9]{7,14}$/';

    protected $fillable = ['store_name', 'short_description', 'whatsapp_number', 'address', 'instagram_url', 'facebook_url'];

    public static function current(): self
    {
        return static::query()->first() ?? new static(['store_name' => config('store.name', 'E-Katalog')]);
    }

    /**
     * Admin-saved number first, then the configured default; null when neither is valid.
     */
    public function whatsappNumber(): ?string
    {
        foreach ([$this->whatsapp_number, config('store.whatsapp_number')] as $candidate) {
            $digits = preg_replace('/\D+/', '', (string) $candidate);

            if (preg_match(self::WHATSAPP_PATTERN, $digits)) {
                return $digits;
            }
        }

        return null;
    }
}
