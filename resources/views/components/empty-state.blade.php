{{--
    Empty state (empty cart, no results, no orders…).

    Props:
        icon         x-icon name (omit for no icon).
        heading      h1 when it is the page's only heading, otherwise h2.
        actionUrl / actionLabel / actionClass   Call-to-action button.
        small        Compact in-panel version.
--}}
@props([
    'title',
    'icon' => null,
    'heading' => 'h2',
    'actionUrl' => null,
    'actionLabel' => null,
    'actionClass' => 'btn-primary',
    'small' => false,
])

<div @class(['empty-state', 'empty-state-sm' => $small])>
    @if ($icon)
        <div class="empty-icon">
            <x-icon :name="$icon" stroke-width="1.6" />
        </div>
    @endif
    <{{ $heading }}>{{ $title }}</{{ $heading }}>
    <p>{{ $slot }}</p>
    @if ($actionUrl)
        <a class="btn {{ $actionClass }}" href="{{ $actionUrl }}">{{ __($actionLabel) }}</a>
    @endif
</div>
