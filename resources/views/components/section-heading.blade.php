{{-- Section title row with optional subtitle and "View All" link. id is used for aria-labelledby. --}}
@props([
    'id',
    'title',
    'subtitle' => null,
    'linkUrl' => null,
    'linkLabel' => 'View All →',
])

<div class="section-head">
    <div>
        <h2 id="{{ $id }}">{{ $title }}</h2>
        @if ($subtitle)
            <p class="section-sub">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($linkUrl)
        <a class="section-link" href="{{ $linkUrl }}">{{ $linkLabel }}</a>
    @endif
</div>
