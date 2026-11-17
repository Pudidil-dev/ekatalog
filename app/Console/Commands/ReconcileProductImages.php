<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReconcileProductImages extends Command
{
    protected $signature = 'products:reconcile-images {--delete-orphans : Delete unreferenced objects older than one day}';

    protected $description = 'Report missing product images and reconcile old, unreferenced Storage objects';

    public function handle(): int
    {
        $disk = Storage::disk(config('filesystems.product_disk'));
        $references = ProductImage::query()->pluck('object_path')->all();
        $known = array_fill_keys($references, true);
        $missing = 0;
        foreach ($references as $path) {
            if (! $disk->exists($path)) {
                $this->warn("Missing referenced object: {$path}");
                $missing++;
            }
        }
        $orphans = 0;
        foreach ($disk->allFiles('products') as $path) {
            if (isset($known[$path])) {
                continue;
            }
            $orphans++;
            if ($this->option('delete-orphans') && $disk->lastModified($path) < now()->subDay()->timestamp) {
                $disk->delete($path);
                $this->line("Deleted orphan: {$path}");
            } else {
                $this->line("Unreferenced object: {$path}");
            }
        }
        $this->info("Missing: {$missing}; orphan candidates: {$orphans}");

        return $missing ? self::FAILURE : self::SUCCESS;
    }
}
