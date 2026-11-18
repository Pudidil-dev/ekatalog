{{-- Public stock indicator: exact quantity when available, "Stok habis" at zero. --}}
@props(['quantity'])
@php($quantity = max(0, (int) $quantity))
<span {{ $attributes->class(['stock-badge', $quantity > 0 ? 'available' : 'empty']) }}>
    @if($quantity > 0)
        Stok: <strong>{{ number_format($quantity, 0, ',', '.') }}</strong> unit
    @else
        Stok habis
    @endif
</span>
