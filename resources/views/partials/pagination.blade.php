{{--
    Default Laravel pagination view (set in AppServiceProvider), styled like <x-pagination>.
    Use: {{ $paginator->links() }}
--}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @unless ($paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹ Prev</a>
        @endunless

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="dots">…</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">Next ›</a>
        @endif
    </nav>
@endif
