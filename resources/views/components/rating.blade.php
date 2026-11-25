{{-- Star meter with a numeric label, e.g. "4.7 (215)". --}}
@props([
    'rating',
    'reviews' => null,
    'reviewsSuffix' => '',
])

<div class="rating">
    <x-stars :rating="$rating" />
    <span class="rating-count">{{ number_format($rating, 1) }}@if ($reviews !== null) ({{ $reviews }}{{ $reviewsSuffix }})@endif</span>
</div>
