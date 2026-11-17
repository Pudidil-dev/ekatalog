<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['SSD', 'RAM', 'Motherboard', 'Prosesor', 'GPU'] as $i => $name) {
            Category::query()->firstOrCreate(['code' => strtolower($name)], [
                'name' => $name, 'slug' => strtolower($name), 'sort_order' => $i + 1,
            ]);
        }
    }
}
