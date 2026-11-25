{{--
    Static pagination bar (swap for {{ $paginator->links() }} once real data exists).
    Shows the first three pages, an ellipsis and the last page.
--}}
@props([
    'current' => 1,
    'last',
    'label' => 'Pagination',
])

@php
    $pages = range(1, min(3, $last));
@endphp

<nav class="pagination" aria-label="{{ $label }}">
    @if ($current > 1)
        <a href="#" aria-label="{{ __('Previous page') }}">{{ __('‹ Prev') }}</a>
    @endif

    @foreach ($pages as $page)
        @if ($page === $current)
            <span class="current" aria-current="page">{{ $page }}</span>
        @else
            <a href="#">{{ $page }}</a>
        @endif
    @endforeach

    @if ($last > 3)
        <span class="dots">…</span>
        <a href="#">{{ $last }}</a>
    @endif

    @if ($current < $last)
        <a href="#" aria-label="{{ __('Next page') }}">{{ __('Next ›') }}</a>
    @endif
</nav>
