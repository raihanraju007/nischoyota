{{-- Category tile — place inside a <div class="grid-categories">. image: path under public/images; imageUrl: full URL (wins over image). --}}
@props([
    'name',
    'image' => 'logo-mark.png',
    'imageUrl' => null,
    'caption',
    'url' => route('category'),
])

<a class="category-card" href="{{ $url }}">
    <img src="{{ $imageUrl ?? asset('images/'.$image) }}" alt="" width="72" height="72">
    <h3>{{ $name }}</h3>
    <p class="cat-count">{{ $caption }}</p>
</a>
