{{-- Shared admin navigation for the desktop sidebar and the mobile menu. --}}
@php
    $links = [
        ['route' => 'admin.products.index', 'active' => 'admin.products.*', 'label' => 'Produk'],
        ['route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'label' => 'Pengaturan toko'],
        ['route' => 'admin.export', 'active' => 'admin.export', 'label' => 'Ekspor Excel'],
        ['route' => 'catalog.index', 'active' => null, 'label' => 'Lihat katalog'],
    ];
@endphp
@foreach ($links as $link)
    @php($isActive = $link['active'] && request()->routeIs($link['active']))
    <a href="{{ route($link['route']) }}" @class(['is-active' => $isActive]) @if ($isActive) aria-current="page" @endif>{{ $link['label'] }}</a>
@endforeach
