{{-- Data from StorefrontShellComposer: $footerDepartments, $storeSettings --}}
@php
    $shopLinks = [];
    foreach ($footerDepartments as $department) {
        $shopLinks[$department['name']] = route('category', $department['slug']);
    }
    $shopLinks["Today's Deals"] = route('deals');

    $serviceLinks = [
        'Contact Us' => route('contact'),
        'FAQ' => route('faq'),
        'Shipping Policy' => route('shipping'),
        'Return & Refund' => route('returns'),
        'Track Order' => route('orders.track'),
    ];

    $aboutLinks = [
        'About Us' => route('about'),
        'Privacy Policy' => route('privacy'),
        'Terms & Conditions' => route('terms'),
    ];
@endphp

<footer class="site-footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand">
                <x-logo />
                <p>{{ __('প্রাকৃতিক ও খাঁটি পণ্যের নিশ্চয়তা — pure honey, ghee, natural foods and plants, delivered anywhere in Bangladesh.') }}</p>
                <span class="footer-note">
                    <x-icon name="truck" class="icon-sm" />
                    {{ __('Cash on Delivery Available') }}
                </span>
            </div>

            @foreach (['Shop' => $shopLinks, 'Customer Service' => $serviceLinks, 'About' => $aboutLinks] as $heading => $links)
                <nav class="footer-col" aria-label="{{ $heading }} links">
                    <h4>{{ $heading }}</h4>
                    <ul>
                        @foreach ($links as $label => $url)
                            <li><a href="{{ $url }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            <div class="footer-col">
                <h4>{{ __('Contact') }}</h4>
                <ul class="footer-contact">
                    <li>
                        <x-icon name="phone" class="icon-sm" />
                        <span>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $storeSettings->supportPhone) }}">{{ $storeSettings->supportPhone }}</a>
                            @if ($storeSettings->supportHours)<br>({{ $storeSettings->supportHours }})@endif
                        </span>
                    </li>
                    <li>
                        <x-icon name="mail" class="icon-sm" />
                        <span><a href="mailto:{{ $storeSettings->supportEmail }}">{{ $storeSettings->supportEmail }}</a></span>
                    </li>
                    @if ($storeSettings->storeAddress)
                        <li>
                            <x-icon name="map-pin" class="icon-sm" />
                            <span>{{ $storeSettings->storeAddress }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="mb-0">© {{ \App\Support\Numerals::localize(date('Y')) }} {{ $storeSettings->storeName }}. {{ __('All rights reserved.') }}</p>
            <nav class="footer-links" aria-label="{{ __('Legal links') }}">
                <a href="{{ route('privacy') }}">{{ __('Privacy') }}</a>
                <a href="{{ route('terms') }}">{{ __('Terms') }}</a>
                <a href="{{ route('shipping') }}">{{ __('Shipping') }}</a>
                <a href="{{ route('returns') }}">{{ __('Returns') }}</a>
            </nav>
        </div>
    </div>
</footer>
