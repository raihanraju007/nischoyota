{{--
    <head> contents shared by the admin layouts.
    Props:
        title  Page title (suffixed with "— Nischoyota Admin").
--}}
@props(['title'])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">

<title>{{ __($title) }} — {{ __('Nischoyota Admin') }}</title>
<meta name="theme-color" content="#1b3320">

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
