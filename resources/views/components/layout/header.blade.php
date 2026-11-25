{{--
    Topbar + main header + desktop category navigation.
    active: home | shop | deals | a department slug (e.g. electrical)
    Data from StorefrontShellComposer: $menuDepartments, $navDepartments, $storeSettings, $signedInFirstName, $cartCount, $wishlistCount
--}}
@props(['active' => null])

<div class="topbar">
    <span class="topbar-text">
        🚚 {{ __('Cash on Delivery Available Nationwide') }}
        @if ($storeSettings->freeDeliveryThreshold !== null)
            — {{ __('Free Delivery on Orders Over :amount', ['amount' => \App\Support\Money::format($storeSettings->freeDeliveryThreshold)]) }}
        @endif
    </span>
</div>

<header class="site-header">
    <div class="container header-inner">
        <button class="icon-btn menu-toggle" data-drawer="mobile-menu" aria-label="{{ __('Open menu') }}">
            <x-icon name="menu" class="icon" />
        </button>

        <x-logo :aria-label="$storeSettings->storeName.' home'" />

        <div class="header-search">
            <form class="search-form" action="{{ route('search') }}" method="GET" role="search" aria-label="{{ __('Product search') }}">
                <label class="visually-hidden" for="header-search-input">{{ __('Search products') }}</label>
                <input class="input" type="search" id="header-search-input" name="q" value="{{ request('q') }}" placeholder="{{ __('Search products, brands and categories...') }}">
                <button class="search-btn" type="submit" aria-label="{{ __('Search') }}">
                    <x-icon name="search" class="icon-sm" stroke-width="2" />
                </button>
            </form>
        </div>

        <nav class="header-actions" aria-label="{{ __('Account and cart') }}">
            <x-language-switcher />
            @if ($signedInFirstName)
                <a class="header-action" href="{{ route('account.dashboard') }}">
                    <x-icon name="user" class="icon" />
                    <span>{{ $signedInFirstName }}</span>
                </a>
            @else
                <a class="header-action" href="{{ route('login') }}">
                    <x-icon name="user" class="icon" />
                    <span>{{ __('Account') }}</span>
                </a>
            @endif
            <a class="header-action" href="{{ route('wishlist') }}" data-wishlist-badge-host>
                <x-icon name="heart" class="icon" />
                <span>{{ __('Wishlist') }}</span>
                @if ($wishlistCount > 0)<span class="count-badge" data-wishlist-count>{{ $wishlistCount }}</span>@endif
            </a>
            <button class="header-action" data-drawer="cart-drawer" data-cart-badge-host aria-label="{{ __('Open cart') }}">
                <x-icon name="cart" class="icon" />
                <span>{{ __('Cart') }}</span>
                @if ($cartCount > 0)<span class="count-badge" data-cart-count>{{ $cartCount }}</span>@endif
            </button>
        </nav>
    </div>
</header>

<nav class="category-nav" aria-label="{{ __('Categories') }}">
    <div class="container">
        <div class="all-categories">
            <button type="button" aria-expanded="false" aria-haspopup="true">
                <x-icon name="menu" class="icon-sm" />
                {{ __('All Categories') }}
                <x-icon name="chevron-down" class="icon-sm caret" />
            </button>
            <div class="mega-menu" role="menu">
                @foreach ($menuDepartments as $department)
                    <div class="mega-col">
                        <h4><a href="{{ route('category', $department['slug']) }}">{{ $department['name'] }}</a></h4>
                        <ul>
                            @foreach ($department['children'] as $subcategory)
                                <li><a href="{{ route('category', $subcategory['slug']) }}">{{ $subcategory['name'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <div class="mega-col">
                    <h4><a href="{{ route('deals') }}">{{ __('Deals') }}</a></h4>
                    <ul>
                        <li><a href="{{ route('deals') }}">{{ __('Today\'s Deals') }}</a></li>
                        <li><a href="{{ route('deals') }}">{{ __('Clearance') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <ul class="nav-list">
            <li><a href="{{ route('home') }}" @class(['active' => $active === 'home'])>{{ __('Home') }}</a></li>
            <li><a href="{{ route('shop') }}" @class(['active' => $active === 'shop'])>{{ __('Shop') }}</a></li>
            @foreach ($navDepartments as $department)
                <li><a href="{{ route('category', $department['slug']) }}" @class(['active' => $active === $department['slug']])>{{ $department['name'] }}</a></li>
            @endforeach
            <li><a href="{{ route('deals') }}" @class(['nav-deals', 'active' => $active === 'deals'])>{{ __('Deals') }}</a></li>
        </ul>
    </div>
</nav>
