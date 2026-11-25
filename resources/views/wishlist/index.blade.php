{{--
    Wishlist (F-022). Data from WishlistController@index:
    $cards  list<ProductCardData> — saved products that are still listed, newest saved first
--}}
<x-layouts.app title="My Wishlist" :breadcrumbs="['Wishlist' => null]" bottom-nav="wishlist">
    <div class="container">
        <div class="page-title-bar">
            <h1>My Wishlist</h1>
            <p class="result-count mb-0">{{ trans_choice(':count item|:count items', count($cards)) }}</p>
        </div>

        <section class="section" aria-label="Wishlist items">
            <div class="grid-products" data-wishlist-page>
                @foreach ($cards as $card)
                    <x-product-card :card="$card" move-from-wishlist />
                @endforeach
            </div>
            <p class="text-muted wishlist-hint">Tap the heart on a product to remove it from your wishlist.</p>
        </section>
    </div>
</x-layouts.app>
