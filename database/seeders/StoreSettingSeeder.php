<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    /**
     * Creates the single store record and fills only empty fields, so values
     * edited in the admin panel are never overwritten by a re-seed.
     */
    public function run(): void
    {
        $store = StoreSetting::query()->firstOrCreate([], ['store_name' => config('store.name', 'E-Katalog')]);

        if (! $store->whatsapp_number && config('store.whatsapp_number')) {
            $store->update(['whatsapp_number' => config('store.whatsapp_number')]);
        }
    }
}
