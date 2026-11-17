@php($store = \App\Models\StoreSetting::current())
@extends('layouts.public')
@section('title', 'Halaman tidak ditemukan')
@section('content')
<div class="empty-state"><h1>Halaman tidak ditemukan</h1><p>Produk mungkin telah diarsipkan atau tautannya sudah tidak tersedia.</p><a class="button primary" href="{{ route('catalog.index') }}">Kembali ke katalog</a></div>
@endsection
