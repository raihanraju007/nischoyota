{{-- Full-page error state (used by resources/views/errors/*). Slots: default (message), actions. --}}
@props([
    'code',
    'title',
    'actions',
])

<x-layouts.app :title="$title">
    <div class="error-page">
        <p class="error-code">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $slot }}</p>
        <div class="error-actions">
            {{ $actions }}
        </div>
    </div>
</x-layouts.app>
