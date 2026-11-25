{{-- Static promo banners (F-019). links: department slug => URL (HomePageData::promoLinks); missing slugs link to the Shop. --}}
@props(['links' => []])

@php
    $banners = [
        ['department' => 'honey', 'image' => 'promo-honey.jpg', 'kicker' => __('খাঁটি মধু ও ঘি'), 'title' => __('Pure Honey & Ghee'), 'text' => __('Raw honey and slow-cooked ghee — no chemicals, ever.'), 'cta' => __('Shop Honey'), 'button' => 'btn-primary'],
    ];

    if (config('shop.clothing_enabled')) {
        $banners[] = ['department' => 'clothing', 'image' => 'promo-fashion.jpg', 'kicker' => __('Clothing'), 'title' => __('Comfort in Cotton'), 'text' => __('Panjabi, T-shirts and everyday wear.'), 'cta' => __('Shop Clothing'), 'button' => 'btn-dark'];
    } else {
        $banners[] = ['department' => 'plants', 'image' => null, 'background' => 'linear-gradient(135deg, #e6f1e3, #cfe5c8)', 'kicker' => __('Plants'), 'title' => __('Fresh Green Indoor Plants'), 'text' => __('Easy-care plants that bring life to your home.'), 'cta' => __('Shop Plants'), 'button' => 'btn-primary'];
    }
@endphp

<section class="section" aria-label="{{ __('Promotions') }}">
    <div class="container grid-promos">
        @foreach ($banners as $banner)
            <div class="promo-banner" @if (isset($banner['background'])) style="background: {{ $banner['background'] }}" @endif>
                @if ($banner['image'])
                    <img class="promo-bg" src="{{ asset('images/'.$banner['image']) }}" alt="" aria-hidden="true">
                @endif
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
