@extends('layouts.admin')
@section('title', $product->exists ? 'Edit produk' : 'Tambah produk')
@section('content')
<h1>{{ $product->exists ? 'Edit produk' : 'Tambah produk' }}</h1>
<p>Simpan sebagai draft, lalu terbitkan ketika informasi sudah lengkap.</p>
<form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="product-form" x-data="{ category: '{{ old('category_id', $product->category_id ?? $categories->first()?->id) }}' }">
    @csrf
    @if($product->exists)
        @method('PUT')
        <input type="hidden" name="version" value="{{ $product->lock_version }}">
    @endif
    <div class="form-main">
        <section class="panel">
            <h2>Informasi produk</h2>
            <label>Nama produk *<input name="name" required value="{{ old('name', $product->name) }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror></label>
            @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
            <label>SKU *<input name="sku" required value="{{ old('sku', $product->sku) }}" @error('sku') aria-invalid="true" aria-describedby="sku-error" @enderror></label>
            @error('sku')<p id="sku-error" class="field-error">{{ $message }}</p>@enderror
            <label>Kategori *<select name="category_id" required x-model="category">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? $categories->first()?->id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select></label>
            <label>Harga (Rp) *<input type="number" min="0" name="price" required value="{{ old('price', $product->price ?? 0) }}"></label>
            <label>Deskripsi<textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea></label>
        </section>
        <section class="panel">
            <h2>Spesifikasi</h2>
            @foreach($categories as $category)
                <div x-show="category === '{{ $category->id }}'" class="spec-fields">
                    @foreach(\App\Support\CategorySpecifications::FIELDS[$category->code] as $key => $label)
                        <label>{{ $label }} *<input :disabled="category !== '{{ $category->id }}'" name="specifications[{{ $key }}]" value="{{ old('specifications.'.$key, $product->specifications[$key] ?? '') }}"></label>
                    @endforeach
                </div>
            @endforeach
        </section>
    </div>
    <aside class="panel">
        <h2>Publikasi</h2>
        <label>Status<select name="status">
            <option value="draft" @selected(old('status', $product->status) === 'draft')>Draft</option>
            @if($product->exists)<option value="published" @selected(old('status', $product->status) === 'published')>Terbit</option>@endif
        </select></label>
        @unless($product->exists)
            <label>Stok awal *<input type="number" min="0" name="initial_stock" required value="{{ old('initial_stock', 0) }}"></label>
            <label>Alasan stok awal *<textarea name="initial_reason" required>{{ old('initial_reason', 'Stok awal') }}</textarea></label>
        @endunless
        <button class="button primary" type="submit">Simpan produk</button>
        <a class="button neutral" href="{{ route('admin.products.index') }}">Kembali</a>
    </aside>
</form>
@if($product->exists)
    <section class="panel image-admin">
        <h2>Gambar produk</h2><p>1–5 gambar, JPEG/PNG/WebP, maksimal 2 MB.</p>
        <div class="image-list">
            @foreach($product->images as $image)
                <div>
                    <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}" width="180" height="135">
                    <p>{{ $image->is_cover ? 'Gambar utama' : 'Gambar tambahan' }}</p>
                    @unless($image->is_cover)
                        <form method="post" action="{{ route('admin.products.images.cover', [$product, $image]) }}">@csrf @method('PATCH')<button class="button secondary">Jadikan utama</button></form>
                    @endunless
                    <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')<button class="button neutral">Hapus</button></form>
                </div>
            @endforeach
        </div>
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.products.images.store', $product) }}">@csrf<label>Pilih gambar<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label><button class="button secondary">Unggah gambar</button></form>
    </section>
@endif
@endsection
