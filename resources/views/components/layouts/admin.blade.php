{{--
    Admin panel shell: sidebar navigation + top bar + page header.

    Usage:
        <x-layouts.admin title="Products" active="products" subtitle="Manage your catalog">
            <x-slot:actions>
                <a class="btn btn-primary" href="…">Add Product</a>
            </x-slot:actions>
            …page content…
        </x-layouts.admin>

    Props:
        title      Page title and <h1>.
        active     Sidebar item: dashboard | orders | messages | products | categories | brands | attributes | inventory | delivery | pages | customers | reviews | coupons | settings
        subtitle   Line under the <h1>.
        backUrl    Renders a "← backLabel" link above the <h1>.
        backLabel  Text for the back link.
    Slots: default (content), actions (buttons beside the <h1>).

    Sidebar opens as a drawer below 992px via data-drawer="admin-sidebar" (resources/js/storefront.js).
    Page-specific modals: @push('overlays') … @endpush
    $pendingOrderCount, $newMessageCount and $pendingReviewCount come from AdminShellComposer (badges are hidden at 0).
--}}
@props([
    'title',
    'active',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => __('Back'),
    'actions' => null,
])

@php
    $adminUser = auth()->user();
    $canManageStore = $adminUser->can('manage-store');

    $menu = [
        'Overview' => [
            'dashboard' => ['label' => 'Dashboard', 'icon' => 'grid', 'url' => route('admin.dashboard')],
        ],
        'Sales' => [
            'orders' => ['label' => 'Orders', 'icon' => 'cart', 'url' => route('admin.orders.index'), 'count' => $pendingOrderCount],
            'customers' => ['label' => 'Customers', 'icon' => 'users', 'url' => route('admin.customers.index')],
            'messages' => ['label' => 'Messages', 'icon' => 'mail', 'url' => route('admin.messages.index'), 'count' => $newMessageCount],
            'coupons' => ['label' => 'Coupons', 'icon' => 'ticket', 'url' => route('admin.coupons.index'), 'adminOnly' => true],
        ],
        'Catalog' => [
            'products' => ['label' => 'Products', 'icon' => 'box', 'url' => route('admin.products.index')],
            'categories' => ['label' => 'Categories', 'icon' => 'tag', 'url' => route('admin.categories.index')],
            'brands' => ['label' => 'Brands', 'icon' => 'shield', 'url' => route('admin.brands.index')],
            'attributes' => ['label' => 'Attributes', 'icon' => 'filter', 'url' => route('admin.attributes.index')],
            'inventory' => ['label' => 'Inventory', 'icon' => 'box', 'url' => route('admin.inventory.index')],
            'reviews' => ['label' => 'Reviews', 'icon' => 'star', 'url' => route('admin.reviews.index'), 'count' => $pendingReviewCount],
        ],
        'System' => [
            'delivery' => ['label' => 'Delivery', 'icon' => 'truck', 'url' => route('admin.delivery-zones.index'), 'adminOnly' => true],
            'pages' => ['label' => 'Pages', 'icon' => 'edit', 'url' => route('admin.pages.index'), 'adminOnly' => true],
            'settings' => ['label' => 'Settings', 'icon' => 'settings', 'url' => route('admin.settings')],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-admin.head :title="$title" />
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="admin-sidebar" aria-label="{{ __('Admin navigation') }}">
            <div class="admin-sidebar-head">
                <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                    <img src="{{ asset('images/logo-mark.png') }}" alt="" width="34" height="34" decoding="async">
                    {{ __('Nischoyota') }}
                </a>
                <small>{{ __('Admin') }}</small>
                <button class="icon-btn admin-sidebar-close" type="button" data-drawer-close aria-label="{{ __('Close menu') }}">
                    <x-icon name="close" class="icon-sm" />
                </button>
            </div>

            <nav class="admin-nav">
                @foreach ($menu as $group => $items)
                    <p class="admin-nav-label">{{ __($group) }}</p>
                    @foreach ($items as $key => $item)
                        @continue(($item['adminOnly'] ?? false) && ! $canManageStore)
                        <a href="{{ $item['url'] }}" @class(['active' => $active === $key]) @if ($active === $key) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" class="icon-sm" />
                            {{ __($item['label']) }}
                            @if (! empty($item['count']))
                                <span class="nav-count">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="admin-sidebar-foot">
                <a href="{{ route('home') }}" target="_blank" rel="noopener">
                    <x-icon name="home" class="icon-sm" /> {{ __('View Store') }}
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout">
                        <x-icon name="logout" class="icon-sm" /> {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="icon-btn admin-menu-toggle" type="button" data-drawer="admin-sidebar" aria-controls="admin-sidebar" aria-label="{{ __('Open menu') }}">
                    <x-icon name="menu" />
                </button>

                <form class="admin-search" role="search" data-demo="Search will work once the backend is built.">
                    <x-icon name="search" class="icon-sm" />
                    <label for="admin-search" class="visually-hidden">{{ __('Search orders, products, customers') }}</label>
                    <input class="input" type="search" id="admin-search" placeholder="{{ __('Search orders, products, customers…') }}">
                </form>

                <div class="admin-topbar-actions">
                    <x-language-switcher />
                    <a class="icon-btn admin-notify" href="{{ route('admin.orders.index', ['status' => 'pending']) }}" aria-label="{{ trans_choice(':count new order|:count new orders', $pendingOrderCount) }}">
                        <x-icon name="bell" />
                        @if ($pendingOrderCount > 0)
                            <span class="dot"></span>
                        @endif
                    </a>
                    <div class="admin-user">
                        <x-avatar :name="$adminUser->name" color="teal" />
                        <div>
                            <strong>{{ $adminUser->name }}</strong>
                            <span>{{ $adminUser->role->label() }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div class="admin-page-head">
                    <div>
                        @if ($backUrl)
                            <a class="admin-back" href="{{ $backUrl }}">← {{ __($backLabel) }}</a>
                        @endif
                        <h1>{{ __($title) }}</h1>
                        @if ($subtitle)
                            <p class="admin-page-sub">{{ __($subtitle) }}</p>
                        @endif
                    </div>
                    @if ($actions)
                        <div class="admin-page-actions">{{ $actions }}</div>
                    @endif
                </div>

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('overlays')

    <div class="toast-root" id="toast-root" aria-live="polite"></div>
    <x-flash-toasts />
</body>
</html>
