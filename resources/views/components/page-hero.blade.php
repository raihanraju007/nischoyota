{{-- Title band for content pages (About, FAQ, policies). Slot = subtitle. --}}
@props(['title'])

<section class="page-hero">
    <div class="container">
        <h1>{{ $title }}</h1>
        <p>{{ $slot }}</p>
    </div>
</section>
