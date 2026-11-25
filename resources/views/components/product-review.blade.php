{{-- Customer review on the product detail page. Slot = review text. --}}
@props([
    'author',
    'date',
    'location' => null,
    'rating',
    'avatarColor' => null,
    'verified' => true,
])

<article class="review-item">
    <div class="review-item-head">
        <div class="reviewer">
            <x-avatar :name="$author" :color="$avatarColor" />
            <div>
                <p class="reviewer-name mb-0">
                    {{ $author }}
                    @if ($verified)<span class="verified">{{ __('✔ Verified Purchase') }}</span>@endif
                </p>
                @if ($location)
                    <p class="reviewer-meta mb-0">{{ $location }}</p>
                @endif
            </div>
        </div>
        <span class="review-date">{{ $date }}</span>
    </div>
    <x-rating :rating="$rating" />
    <p>{{ $slot }}</p>
</article>
