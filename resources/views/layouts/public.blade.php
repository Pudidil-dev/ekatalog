<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Katalog produk') · {{ $store->store_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main">Langsung ke konten</a>
<header class="site-header"><div class="container header-inner"><a class="brand" href="{{ route('catalog.index') }}"><span class="brand-mark">◇</span><span>{{ $store->store_name }}<small>Spare part komputer</small></span></a>
    <nav aria-label="Navigasi utama" class="desktop-nav"><a href="{{ route('catalog.index') }}">Katalog</a><a href="{{ route('catalog.index') }}#categories">Kategori</a><a href="#about">Tentang Toko</a><a href="#contact">Hubungi</a></nav>
    <details class="mobile-nav"><summary>Menu</summary><nav aria-label="Navigasi mobile"><a href="{{ route('catalog.index') }}">Katalog</a><a href="{{ route('catalog.index') }}#categories">Kategori</a><a href="#about">Tentang Toko</a><a href="#contact">Hubungi</a></nav></details>
</div></header>
<main id="main" class="container public-main">@yield('content')</main>
<footer class="site-footer"><div class="container footer-grid"><section id="about"><h2>{{ $store->store_name }}</h2><p>{{ $store->short_description ?: 'Temukan spesifikasi dan ketersediaan komponen komputer sebelum menghubungi toko.' }}</p></section><section><h2>Kategori produk</h2>@foreach(\App\Models\Category::orderBy('sort_order')->get() as $category)<a href="{{ route('catalog.index', ['category' => $category->slug]) }}">{{ $category->name }}</a>@endforeach</section><section id="contact"><h2>Hubungi toko</h2>@if($store->whatsapp_number)<a href="https://wa.me/{{ $store->whatsapp_number }}" rel="noopener noreferrer">Hubungi via WhatsApp</a>@endif @if($store->address)<p>{{ $store->address }}</p>@endif @if($store->instagram_url)<a href="{{ $store->instagram_url }}" rel="noopener noreferrer">Instagram</a>@endif @if($store->facebook_url)<a href="{{ $store->facebook_url }}" rel="noopener noreferrer">Facebook</a>@endif</section></div><div class="container footer-bottom">© {{ date('Y') }} {{ $store->store_name }}</div></footer>
</body></html>
