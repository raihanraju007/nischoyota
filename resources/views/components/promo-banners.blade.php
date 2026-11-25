{{-- Static promo banners (F-019). links: department slug => URL (HomePageData::promoLinks); missing slugs link to the Shop. --}}
@props(['links' => []])

@php
    $banners = [
        ['department' => 'honey', 'image' => 'promo-honey.jpg', 'kicker' => 'খাঁটি মধু ও ঘি', 'title' => 'Pure Honey & Ghee', 'text' => 'Raw honey and slow-cooked ghee — no chemicals, ever.', 'cta' => 'Shop Honey', 'button' => 'btn-primary'],
        ['department' => 'clothing', 'image' => 'promo-fashion.jpg', 'kicker' => 'Clothing', 'title' => 'Comfort in Cotton', 'text' => 'Panjabi, T-shirts and everyday wear.', 'cta' => 'Shop Clothing', 'button' => 'btn-dark'],
    ];
@endphp

<section class="section" aria-label="Promotions">
    <div class="container grid-promos">
        @foreach ($banners as $banner)
            <div class="promo-banner">
                <img class="promo-bg" src="{{ asset('images/'.$banner['image']) }}" alt="" aria-hidden="true">
                <div class="promo-content">
                    <p class="promo-kicker">{{ $banner['kicker'] }}</p>
                    <h3>{{ $banner['title'] }}</h3>
                    <p>{{ $banner['text'] }}</p>
                    <a class="btn {{ $banner['button'] }}" href="{{ $links[$banner['department']] ?? route('shop') }}">{{ $banner['cta'] }}</a>
                </div>
            </div>
        @endforeach
    </div>
</section>
