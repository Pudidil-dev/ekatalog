<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Support\CategorySpecifications;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    private ?Category $selectedCategory = null;

    private bool $categoryResolved = false;

    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
    }

    public function rules(): array
    {
        $category = $this->selectedCategory();
        $product = $this->route('product');

        return array_merge([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'regex:/^[A-Z0-9][A-Z0-9-]{1,79}$/', Rule::unique('products')->ignore($product?->id)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:99999999999999'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'specifications' => ['required', 'array'],
            'version' => [$product ? 'required' : 'nullable', 'integer', 'min:0'],
            'initial_stock' => [$product ? 'prohibited' : 'required', 'integer', 'min:0', 'max:2147483647'],
            'initial_reason' => [$product ? 'prohibited' : 'required', 'string', 'min:3', 'max:500'],
        ], CategorySpecifications::rules($category?->code ?? ''));
    }

    /**
     * Nama isian pada pesan kesalahan; label spesifikasi mengikuti kategori yang dipilih.
     */
    public function attributes(): array
    {
        $labels = CategorySpecifications::FIELDS[$this->selectedCategory()?->code ?? ''] ?? [];

        return [
            'name' => 'nama produk',
            'status' => 'status publikasi',
            ...collect($labels)->mapWithKeys(fn (string $label, string $key) => ['specifications.'.$key => $label])->all(),
        ];
    }

    /**
     * Kategori dicari sekali; nilai bukan skalar (misalnya array) dibiarkan ditolak oleh aturan validasi.
     */
    private function selectedCategory(): ?Category
    {
        if (! $this->categoryResolved) {
            $id = $this->input('category_id');
            $this->selectedCategory = is_scalar($id) ? Category::find($id) : null;
            $this->categoryResolved = true;
        }

        return $this->selectedCategory;
    }
}
