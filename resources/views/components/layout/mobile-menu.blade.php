{{--
    Mobile menu drawer, opened by the header hamburger and the bottom-nav "Categories" button.
    active: home | shop | deals | a department slug
    Data from StorefrontShellComposer: $menuDepartments, $signedInFirstName
--}}
@props(['active' => null])

<x-drawer id="mobile-menu" side="left" :title="__('Menu')" class="mobile-menu">
    <ul class="mobile-menu-list">
        <li><a href="{{ route('home') }}" @class(['active' => $active === 'home'])>{{ __('Home') }}</a></li>
        <li><a href="{{ route('shop') }}" @class(['active' => $active === 'shop'])>{{ __('Shop All Products') }}</a></li>
        <li><a href="{{ route('deals') }}" @class(['active' => $active === 'deals'])>{{ __('Today\'s Deals 🔥') }}</a></li>
    </ul>

    <p class="mobile-menu-label">{{ __('Categories') }}</p>
    <ul class="mobile-menu-list mobile-menu-sub">
        @foreach ($menuDepartments as $department)
            <li><a href="{{ route('category', $department['slug']) }}" @class(['active' => $active === $department['slug']])>{{ $department['name'] }}</a></li>
        @endforeach
    </ul>

    <p class="mobile-menu-label">{{ __('Account') }}</p>
    <ul class="mobile-menu-list mobile-menu-sub">
        @if ($signedInFirstName)
            <li><a href="{{ route('account.dashboard') }}">{{ __('My Account') }}</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link-button">{{ __('Logout') }}</button>
                </form>
            </li>
        @else
            <li><a href="{{ route('login') }}">{{ __('Login / Register') }}</a></li>
        @endif
        <li><a href="{{ route('account.orders') }}">{{ __('My Orders') }}</a></li>
        <li><a href="{{ route('orders.track') }}">{{ __('Track Order') }}</a></li>
        <li><a href="{{ route('contact') }}">{{ __('Contact Us') }}</a></li>
    </ul>

    <x-language-switcher />
</x-drawer>
