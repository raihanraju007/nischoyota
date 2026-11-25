{{-- Star meter with a numeric label, e.g. "4.7 (215)". --}}
@props([
    'rating',
    'reviews' => null,
    'reviewsSuffix' => '',
])

<div class="rating">
    <x-stars :rating="$rating" />
    <span class="rating-count">{{ \App\Support\Numerals::localize(number_format($rating, 1)) }}@if ($reviews !== null) ({{ \App\Support\Numerals::localize($reviews) }}{{ $reviewsSuffix }})@endif</span>
</div>
