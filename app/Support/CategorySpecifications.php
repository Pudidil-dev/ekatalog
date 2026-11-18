<?php

namespace App\Support;

final class CategorySpecifications
{
    public const FIELDS = [
        'ssd' => ['capacity_gb' => 'Kapasitas (GB)', 'interface' => 'Interface', 'protocol' => 'Protokol', 'form_factor' => 'Form factor'],
        'ram' => ['capacity_gb' => 'Kapasitas (GB)', 'generation' => 'Generasi DDR', 'speed_mt' => 'Kecepatan (MT/s)', 'form_factor' => 'Form factor modul'],
        'motherboard' => ['socket' => 'Socket', 'chipset' => 'Chipset', 'form_factor' => 'Form factor', 'memory_type' => 'Jenis memori'],
        'prosesor' => ['socket' => 'Socket', 'cores' => 'Jumlah core', 'threads' => 'Jumlah thread', 'base_clock_ghz' => 'Base clock (GHz)'],
        'gpu' => ['chipset' => 'Chipset', 'vram_gb' => 'VRAM (GB)', 'memory_type' => 'Jenis memori'],
    ];

    public static function rules(string $category, bool $required = true): array
    {
        $presence = $required ? 'required' : 'nullable';
        $fields = self::FIELDS[$category] ?? [];
        $numeric = ['capacity_gb', 'speed_mt', 'cores', 'threads', 'vram_gb'];
        $rules = [];
        foreach ($fields as $key => $label) {
            $rules['specifications.'.$key] = in_array($key, $numeric, true)
                ? [$presence, 'integer', 'min:1', 'max:100000']
                : ($key === 'base_clock_ghz' ? [$presence, 'numeric', 'gt:0', 'max:100'] : [$presence, 'string', 'max:100']);
        }

        return $rules;
    }
}
