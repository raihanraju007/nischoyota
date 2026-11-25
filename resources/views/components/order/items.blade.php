{{--
    Compact order line items.
    items: list of ['name', 'image' or 'imageUrl', 'alt'?, 'variant'?, 'quantity', 'total']
    Prototype pages pass taka; real data passes paisa with :paisa="true" (F-023+).
--}}
@props(['items', 'paisa' => false])

<ul class="order-items-mini">
    @foreach ($items as $item)
        <li>
            <img src="{{ $item['imageUrl'] ?? asset('images/'.$item['image']) }}" alt="{{ $item['alt'] ?? $item['name'] }}" width="54" height="54">
            <div>
                <p class="mini-name">{{ $item['name'] }}</p>
                @if (! empty($item['variant']))
                    <span class="mini-qty">{{ $item['variant'] }} · </span>
                @endif
                <span class="mini-qty">{{ __('Qty:') }} {{ \App\Support\Numerals::localize($item['quantity']) }}</span>
            </div>
            <span class="mini-price">{{ $paisa ? \App\Support\Money::format($item['total']) : '৳ '.\App\Support\Numerals::localize(number_format($item['total'])) }}</span>
        </li>
    @endforeach
</ul>
