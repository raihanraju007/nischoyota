@php
    $trustPoints = [
        ['icon' => 'truck', 'title' => 'Cash on Delivery', 'text' => 'Pay when your order arrives.'],
        ['icon' => 'map-pin', 'title' => 'Nationwide Delivery', 'text' => 'Delivery across Bangladesh.'],
        ['icon' => 'shield', 'title' => '100% Natural', 'text' => 'No chemicals, no added sugar.'],
        ['icon' => 'return', 'title' => 'Easy Returns', 'text' => 'Simple 7-day return process.'],
        ['icon' => 'headset', 'title' => 'Customer Support', 'text' => "We're here to help, 9am–10pm."],
    ];
@endphp

<section class="section trust-strip" aria-label="{{ __('Why shop with us') }}">
    <div class="container trust-grid">
        @foreach ($trustPoints as $point)
            <div class="trust-item">
                <span class="trust-icon">
                    <x-icon :name="$point['icon']" class="icon" />
                </span>
                <div>
                    <h3>{{ $point['title'] }}</h3>
                    <p>{{ $point['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
