@extends('layouts.admin')
@section('title', 'Produk')
@section('content')
<div class="page-heading">
    <h1>Produk</h1>
    <p>Kelola produk, publikasi, dan ketersediaan dalam satu tempat.</p>
    <div class="actions">
        <a class="button primary" href="{{ route('admin.products.create') }}">Tambah produk</a>
        <a class="button neutral" href="{{ route('admin.export', request()->only(['q', 'category', 'status'])) }}">Ekspor Excel</a>
    </div>
</div>
<form class="filter-form" method="get" action="{{ route('admin.products.index') }}">
    <label>Cari produk<input name="q" value="{{ request('q') }}" placeholder="Nama atau SKU"></label>
    <label>Kategori<select name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category')===$category->slug)>{{ $category->name }}</option>@endforeach</select></label>
    <label>Publikasi<select name="status"><option value="">Semua status</option><option value="draft" @selected(request('status')==='draft')>Draft</option><option value="published" @selected(request('status')==='published')>Terbit</option></select></label>
    <button class="button secondary">Cari</button>
    @if(request()->filled('q') || request('category') || request('status'))<a class="button neutral" href="{{ route('admin.products.index') }}">Reset</a>@endif
</form>
<div class="table-scroll"><table class="stack-table" role="table" aria-label="Daftar produk">
    {{-- Peran ARIA dipertahankan agar semantik tabel tidak hilang saat baris ditampilkan sebagai kartu di ponsel. --}}
    <thead role="rowgroup"><tr role="row"><th role="columnheader">Produk / SKU</th><th role="columnheader">Kategori</th><th role="columnheader">Harga</th><th role="columnheader">Stok</th><th role="columnheader">Publikasi</th><th role="columnheader">Tindakan</th></tr></thead>
    <tbody role="rowgroup">@forelse($products as $product)
        <tr role="row"><td role="cell" data-label="Produk / SKU"><div><strong>{{ $product->name }}</strong><small>{{ $product->sku }}</small></div></td>
            <td role="cell" data-label="Kategori">{{ $product->category->name }}</td><td role="cell" data-label="Harga">Rp{{ number_format((int) $product->price, 0, ',', '.') }}</td><td role="cell" data-label="Stok">{{ $product->stock_quantity }}</td>
            <td role="cell" data-label="Publikasi"><span class="stock-badge {{ $product->status === 'published' ? 'available' : 'empty' }}">{{ $product->status === 'published' ? 'Terbit' : 'Draft' }}</span></td>
            <td role="cell" data-label="Tindakan"><div class="actions"><a class="button neutral" href="{{ route('admin.products.edit', $product) }}">Edit</a><a class="button neutral" href="{{ route('admin.products.history', $product) }}">Stok</a><form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Arsipkan produk ini?')">@csrf @method('DELETE')<button class="button neutral">Arsip</button></form></div></td></tr>
    @empty<tr role="row"><td role="cell" colspan="6">Belum ada produk. Tambahkan produk pertama Anda.</td></tr>@endforelse</tbody>
</table></div>
{{ $products->links() }}
@endsection
