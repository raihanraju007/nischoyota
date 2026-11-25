{{--
    Mobile bottom navigation (hidden on desktop by CSS).
    active: home | search | wishlist | cart | account
    Data from StorefrontShellComposer: $cartCount, $wishlistCount
--}}
@props(['active' => null])

<nav class="bottom-nav" aria-label="{{ __('Mobile navigation') }}">
    <ul>
        <li>
            <a href="{{ route('home') }}" @class(['active' => $active === 'home'])>
                <x-icon name="home" class="icon" />
                {{ __('Home') }}
            </a>
        </li>
        <li>
            <button type="button" data-drawer="mobile-menu">
                <x-icon name="grid" class="icon" />
                {{ __('Categories') }}
            </button>
        </li>
        <li>
            <a href="{{ route('search') }}" @class(['active' => $active === 'search'])>
                <x-icon name="search" class="icon" />
                {{ __('Search') }}
            </a>
        </li>
        <li>
            <a href="{{ route('wishlist') }}" @class(['active' => $active === 'wishlist']) data-wishlist-badge-host>
                <x-icon name="heart" class="icon" />
                {{ __('Wishlist') }}
                @if ($wishlistCount > 0)<span class="count-badge" data-wishlist-count>{{ $wishlistCount }}</span>@endif
            </a>
        </li>
        <li>
            <a href="{{ route('cart') }}" @class(['active' => $active === 'cart']) data-cart-badge-host>
                <x-icon name="cart" class="icon" />
                {{ __('Cart') }}
                @if ($cartCount > 0)<span class="count-badge" data-cart-count>{{ $cartCount }}</span>@endif
            </a>
        </li>
        <li>
            <a href="{{ route('account.dashboard') }}" @class(['active' => $active === 'account'])>
                <x-icon name="user" class="icon" />
                {{ __('Account') }}
            </a>
        </li>
    </ul>
</nav>
