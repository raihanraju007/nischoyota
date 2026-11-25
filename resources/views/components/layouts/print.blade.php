{{--
    Print-only page for the browser's "Print / Save as PDF" (F-025b): no admin chrome, A4 paper.

    Usage:
        <x-layouts.print title="Packing slips" :back-url="route('admin.orders.index')">…sheets…</x-layouts.print>

    Props:
        title    Page title.
        backUrl  "← Back" link in the screen-only toolbar.
--}}
@props(['title', 'backUrl'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-admin.head :title="$title" />
</head>
<body class="print-body">
    <div class="print-toolbar">
        <a class="btn btn-ghost btn-sm" href="{{ $backUrl }}">{{ __('← Back') }}</a>
        <strong>{{ $title }}</strong>
        <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">
            <x-icon name="download" class="icon-sm" /> {{ __('Print / Save as PDF') }}
        </button>
    </div>

    {{ $slot }}
</body>
</html>
