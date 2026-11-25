{{--
    Home page (HomeController@index).
    $home           HomePageData: departments, featured, deals, new arrivals, best sellers, promo links
    $dealHoursLeft  Hours until midnight (Bangladesh time), for the deals countdown
    $customerReviews  Newest approved 4–5 star reviews (HomeReviewsComposer, F-027); the section is hidden when empty
    Hero and trust strip are static.
--}}
<x-layouts.app active-nav="home" bottom-nav="home">
    {{-- Hero --}}
    <section class="hero" aria-labelledby="hero-title">
        <div class="container hero-inner">
            <div class="hero-copy">
                <span class="hero-kicker">100% Natural · Pure · Trusted</span>
                <p class="hero-tagline">প্রাকৃতিক ও খাঁটি পণ্যের নিশ্চয়তা</p>
                <h1 id="hero-title">Pure Honey &amp; Ghee, <span class="hero-accent">Straight from Nature</span></h1>
                <p class="lead">Raw honey from the hives, traditional pure ghee and handpicked clothing — no chemicals, no shortcuts. শুদ্ধতার সাথে সুস্থতার পথে…</p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="{{ route('shop') }}">Shop Now</a>
                    <a class="btn btn-outline btn-lg" href="{{ route('deals') }}">Today's Offers</a>
                </div>
                <ul class="hero-points">
                    @foreach (['100% Natural', 'No Chemicals', 'Cash on Delivery'] as $point)
                        <li><x-icon name="check" class="icon-sm" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="hero-media">
                <img src="{{ asset('images/hero.jpg') }}" alt="Nischoyota pure honey and ghee" width="1100" height="1100">
            </div>
        </div>
    </section>

    {{-- Shop by category --}}
    @if ($home->departments !== [])
        <section class="section" aria-labelledby="categories-title">
            <div class="container">
                <x-section-heading id="categories-title" title="Shop by Category" subtitle="Browse our most popular departments" :link-url="route('shop')" link-label="View All Products →" />
                <div class="grid-categories">
                    @foreach ($home->departments as $department)
                        <x-category-card :name="$department['name']" :image-url="$department['imageUrl']" :caption="trans_choice(':count Product|:count Products', $department['productCount'])" :url="route('category', $department['slug'])" />
                    @endforeach
                    @if ($home->highestDiscount > 0)
                        <x-category-card name="Deals" image="deals.svg" :caption="'Up to '.$home->highestDiscount.'% OFF'" :url="route('deals')" />
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Featured products --}}
    @if ($home->featured !== [])
        <section class="section section-alt" aria-labelledby="featured-title">
            <div class="container">
                <x-section-heading id="featured-title" title="Featured Products" subtitle="Our purest picks — loved by families across Bangladesh" :link-url="route('shop')" />
                <div class="grid-products">
                    @foreach ($home->featured as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Today's deals --}}
    @if ($home->deals !== [])
        <section class="section deals-section" aria-labelledby="deals-title">
            <div class="container">
                <div class="deals-head">
                    <h2 id="deals-title">Today's Deals</h2>
                    <span class="deals-tag">Up to {{ $home->highestDiscount }}% OFF</span>
                    <x-countdown :hours="$dealHoursLeft" />
                    <a class="section-link ml-auto" href="{{ route('deals') }}">View All Deals →</a>
                </div>
                <div class="grid-products">
                    @foreach ($home->deals as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-promo-banners :links="$home->promoLinks" />

    {{-- New arrivals --}}
    @if ($home->newArrivals !== [])
        <section class="section section-alt" aria-labelledby="new-title">
            <div class="container">
                <x-section-heading id="new-title" title="New Arrivals" subtitle="Fresh products just added to the store" :link-url="route('shop', ['sort' => 'newest'])" />
                <div class="grid-products">
                    @foreach ($home->newArrivals as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Best sellers --}}
    @if ($home->bestSellers !== [])
        <section class="section" aria-labelledby="best-title">
            <div class="container">
                <x-section-heading id="best-title" title="Best Sellers" subtitle="Most loved products this month" :link-url="route('shop', ['sort' => 'popular'])" />
                <div class="grid-products">
                    @foreach ($home->bestSellers as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-trust-strip />

    {{-- Customer reviews --}}
    @if ($customerReviews->isNotEmpty())
        <section class="section" aria-labelledby="reviews-title">
            <div class="container">
                <x-section-heading id="reviews-title" title="What Our Customers Say" subtitle="Real reviews from shoppers across Bangladesh" />
                <div class="grid-reviews">
                    @foreach ($customerReviews as $review)
                        <x-review-card :author="$review->user->name" :meta="$review->product->name" :rating="$review->rating">
                            "{{ $review->comment }}"
                        </x-review-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
