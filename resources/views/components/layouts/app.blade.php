{{--
    Storefront page shell.

    Usage:
        <x-layouts.app title="Shopping Cart" :breadcrumbs="['Cart' => null]" active-nav="shop">
            …page content…
        </x-layouts.app>

    Props:
        title        Page title (suffixed with "— Nischoyota"); null = homepage title.
        description  Meta description (null = store default).
        breadcrumbs  [label => url|null]; "Home" is prepended automatically. Omit for no breadcrumb bar.
        activeNav    Highlighted desktop/mobile nav item (home | shop | electrical | … | deals).
        bottomNav    Highlighted mobile bottom-nav item (home | search | wishlist | cart | account).
        mainClass    Extra class for <main>.

    Page-specific modals and drawers: @push('overlays') … @endpush
--}}
@props([
    'title' => null,
    'description' => null,
    'breadcrumbs' => [],
    'activeNav' => null,
    'bottomNav' => null,
    'mainClass' => null,
])

@php
    $description ??= 'Nischoyota (নিশ্চয়তা) — pure raw honey, traditional ghee, natural foods and clothing. Cash on Delivery across Bangladesh.';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — Nischoyota' : 'Nischoyota — Pure Honey, Ghee & Natural Products' }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#2f6b3a">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-layout.header :active="$activeNav" />

    @if ($breadcrumbs)
        <x-breadcrumb :items="$breadcrumbs" />
    @endif

    <main @class([$mainClass])>
        {{ $slot }}
    </main>

    <x-layout.footer />
    <x-layout.bottom-nav :active="$bottomNav" />
    <x-layout.mobile-menu :active="$activeNav" />
    <x-layout.cart-drawer />

    @stack('overlays')

    <div class="toast-root" id="toast-root" aria-live="polite"></div>
    <x-flash-toasts />
</body>
</html>
