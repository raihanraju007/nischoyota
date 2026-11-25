{{--
    Mobile menu drawer, opened by the header hamburger and the bottom-nav "Categories" button.
    active: home | shop | deals | a department slug
    Data from StorefrontShellComposer: $menuDepartments, $signedInFirstName
--}}
@props(['active' => null])

<x-drawer id="mobile-menu" side="left" title="Menu" class="mobile-menu">
    <ul class="mobile-menu-list">
        <li><a href="{{ route('home') }}" @class(['active' => $active === 'home'])>Home</a></li>
        <li><a href="{{ route('shop') }}" @class(['active' => $active === 'shop'])>Shop All Products</a></li>
        <li><a href="{{ route('deals') }}" @class(['active' => $active === 'deals'])>Today's Deals 🔥</a></li>
    </ul>

    <p class="mobile-menu-label">Categories</p>
    <ul class="mobile-menu-list mobile-menu-sub">
        @foreach ($menuDepartments as $department)
            <li><a href="{{ route('category', $department['slug']) }}" @class(['active' => $active === $department['slug']])>{{ $department['name'] }}</a></li>
        @endforeach
    </ul>

    <p class="mobile-menu-label">Account</p>
    <ul class="mobile-menu-list mobile-menu-sub">
        @if ($signedInFirstName)
            <li><a href="{{ route('account.dashboard') }}">My Account</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link-button">Logout</button>
                </form>
            </li>
        @else
            <li><a href="{{ route('login') }}">Login / Register</a></li>
        @endif
        <li><a href="{{ route('account.orders') }}">My Orders</a></li>
        <li><a href="{{ route('orders.track') }}">Track Order</a></li>
        <li><a href="{{ route('contact') }}">Contact Us</a></li>
    </ul>
</x-drawer>
