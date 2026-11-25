@props([
    'value' => 1,
    'label' => 'Quantity',
    'small' => false,
])

<div @class(['qty-control', 'qty-control-sm' => $small])>
    <button type="button" data-qty-minus aria-label="{{ __('Decrease quantity') }}">−</button>
    <input {{ $attributes }} type="number" value="{{ $value }}" min="1" max="99" aria-label="{{ $label }}">
    <button type="button" data-qty-plus aria-label="{{ __('Increase quantity') }}">+</button>
</div>
