{{--
    Account dashboard (AccountDashboardController).
    $defaultAddress  Address or null
    $recentOrders    up to 3 latest Orders with item_count (F-024)
--}}
<x-layouts.account :title="__('My Account')" active="dashboard">
    <div class="panel">
        <h2 class="panel-title">Hello, {{ auth()->user()->name }}!</h2>
        <p class="mb-0 text-light">{{ __('From here you can see your orders, manage your delivery addresses and update your profile.') }}</p>
    </div>

    <div class="stat-cards">
        <a class="stat-card" href="{{ route('account.orders') }}">
            <span class="stat-icon stat-icon-primary"><x-icon name="cart" class="icon" /></span>
            <div><span class="stat-label">{{ __('My Orders') }}</span></div>
        </a>
        <a class="stat-card" href="{{ route('wishlist') }}">
            <span class="stat-icon stat-icon-rose"><x-icon name="heart" class="icon" /></span>
            <div><span class="stat-label">{{ __('Wishlist') }}</span></div>
        </a>
        <a class="stat-card" href="{{ route('account.addresses') }}">
            <span class="stat-icon stat-icon-blue"><x-icon name="map-pin" class="icon" /></span>
            <div><span class="stat-label">{{ __('Addresses') }}</span></div>
        </a>
        <a class="stat-card" href="{{ route('account.profile') }}">
            <span class="stat-icon stat-icon-amber"><x-icon name="user" class="icon" /></span>
            <div><span class="stat-label">{{ __('Profile') }}</span></div>
        </a>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2>{{ __('Recent Orders') }}</h2>
            <a class="btn btn-sm btn-outline" href="{{ route('account.orders') }}">{{ __('View All') }}</a>
        </div>
        @if ($recentOrders->isEmpty())
            <p class="mb-0 text-light">{{ __('No orders yet.') }} <a href="{{ route('shop') }}">{{ __('Start shopping') }}</a>.</p>
        @else
            <x-account.order-table :orders="$recentOrders" />
        @endif
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2>{{ __('Default Delivery Address') }}</h2>
            <a class="btn btn-sm btn-outline" href="{{ route('account.addresses') }}">{{ __('Manage Addresses') }}</a>
        </div>
        @if ($defaultAddress)
            <address class="address-block text-light">
                <strong>{{ $defaultAddress->recipient_name }}</strong><br>
                @foreach ($defaultAddress->displayLines() as $line)
                    {{ $line }}<br>
                @endforeach
                {{ $defaultAddress->phone }}
            </address>
        @else
            <p class="mb-0 text-light">{{ __('No address saved yet.') }} <a href="{{ route('account.addresses.create') }}">{{ __('Add one') }}</a> {{ __('to check out faster.') }}</p>
        @endif
    </div>
</x-layouts.account>
