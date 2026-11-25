{{--
    Customer account shell (sidebar + content), built on <x-layouts.app>.
    Props:
        title        Page title and <h1>.
        active       Sidebar item: dashboard | orders | wishlist | addresses | profile
        breadcrumbs  Crumbs after "Home › My Account" ([label => url|null]).
--}}
@props([
    'title',
    'active',
    'breadcrumbs' => [],
])

@php
    $menu = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'grid', 'url' => route('account.dashboard')],
        'orders' => ['label' => 'My Orders', 'icon' => 'cart', 'url' => route('account.orders')],
        'wishlist' => ['label' => 'Wishlist', 'icon' => 'heart', 'url' => route('wishlist')],
        'addresses' => ['label' => 'Addresses', 'icon' => 'map-pin', 'url' => route('account.addresses')],
        'profile' => ['label' => 'Profile', 'icon' => 'user', 'url' => route('account.profile')],
    ];

    $customer = auth()->user();

    $breadcrumbs = $breadcrumbs
        ? ['My Account' => route('account.dashboard')] + $breadcrumbs
        : ['My Account' => null];
@endphp

<x-layouts.app :title="$title" :breadcrumbs="$breadcrumbs" bottom-nav="account">
    <div class="container account-layout">
        <aside class="account-sidebar">
            <div class="account-user">
                <x-avatar :name="$customer->name" />
                <div>
                    <h3>{{ $customer->name }}</h3>
                    <p>{{ $customer->email ?? $customer->phone }}</p>
                </div>
            </div>
            <nav class="account-menu" aria-label="Account navigation">
                @foreach ($menu as $key => $item)
                    <a href="{{ $item['url'] }}" @class(['active' => $active === $key])>
                        <x-icon :name="$item['icon']" class="icon-sm" /> {{ $item['label'] }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout">
                        <x-icon name="logout" class="icon-sm" /> Logout
                    </button>
                </form>
            </nav>
        </aside>

        <div class="account-content">
            <h1>{{ $title }}</h1>
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
