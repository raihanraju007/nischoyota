{{-- Testimonial card for the homepage. Slot = review text. --}}
@props([
    'author',
    'meta',
    'rating',
    'avatarColor' => null,
])

<article class="review-card">
    <x-rating :rating="$rating" />
    <p class="review-text">{{ $slot }}</p>
    <div class="reviewer">
        <x-avatar :name="$author" :color="$avatarColor" />
        <div>
            <p class="reviewer-name mb-0">{{ $author }}</p>
            <p class="reviewer-meta mb-0">{{ $meta }}</p>
        </div>
    </div>
</article>
