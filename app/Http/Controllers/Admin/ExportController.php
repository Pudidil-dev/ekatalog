<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'category' => ['nullable', 'exists:categories,slug'],
            'status' => ['nullable', 'in:draft,published'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'ekatalog_');
        if ($path === false) {
            abort(503, 'Ekspor tidak tersedia.');
        }
        $writer = new Writer;
        $opened = false;
        try {
            try {
                $writer->openToFile($path);
                $opened = true;
                $writer->addRow(Row::fromValues(['SKU', 'Nama', 'Kategori', 'Spesifikasi', 'Harga', 'Stok', 'Publikasi', 'Diperbarui']));
                // A stable snapshot is necessary when stock or publication changes during the export.
                DB::transaction(function () use ($writer, $data) {
                    if (DB::getDriverName() === 'pgsql') {
                        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
                    }
                    $query = Product::query()->with('category')
                        ->when($data['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
                        ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
                    if (isset($data['q']) && $data['q'] !== '') {
                        $search = '%'.addcslashes($data['q'], '%_\\').'%';
                        $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                        $query->where(fn ($q) => $q->where('name', $operator, $search)->orWhere('sku', $operator, $search));
                    }
                    foreach ($query->orderBy('id')->lazy(200) as $product) {
                        // Spreadsheet programs can ignore leading whitespace/control characters
                        // before interpreting a formula. Prefix the whole value as text.
                        $safe = fn ($s) => preg_match('/^[\p{Z}\p{C}]*[=+@\-]/u', (string) $s) ? "'".(string) $s : (string) $s;
                        $writer->addRow(Row::fromValues([
                            $safe($product->sku), $safe($product->name), $safe($product->category->name),
                            $safe(json_encode($product->specifications, JSON_UNESCAPED_UNICODE)),
                            (int) $product->price, $product->stock_quantity, $product->status,
                            $product->updated_at?->toIso8601String(),
                        ]));
                    }
                });
            } finally {
                if ($opened) {
                    $writer->close();
                }
            }
        } catch (\Throwable $e) {
            File::delete($path);
            throw $e;
        }

        return response()->download($path, 'produk-ekatalog.xlsx')->deleteFileAfterSend(true);
    }
}
