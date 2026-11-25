{{--
    No products for a search (SearchController@index).
    $query        What the customer typed
    $suggestions  ProductCardData list (best sellers)
--}}
<x-layouts.app :title="'No results for “'.$query.'”'" :breadcrumbs="['Search' => null]" bottom-nav="search">
    <div class="container">
        <div class="page-title-bar">
            <h1>Search results for &ldquo;{{ $query }}&rdquo;</h1>
            <p class="result-count">0 products found</p>
        </div>

        <x-empty-state icon="search-off" title="No products found" :action-url="route('shop')" action-label="Browse All Products">
            Try another search or explore our categories. Check your spelling or use more general keywords.
        </x-empty-state>
    </div>

    @if ($suggestions !== [])
        <section class="section section-alt" aria-labelledby="suggest-title">
            <div class="container">
                <x-section-heading id="suggest-title" title="You may like these" subtitle="Popular products our customers love" :link-url="route('shop')" />
                <div class="grid-products">
                    @foreach ($suggestions as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
