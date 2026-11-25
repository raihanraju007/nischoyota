{{--
    Empty cart (F-021). Data from CartController@index:
    $popularCards  list<ProductCardData> — best sellers
--}}
<x-layouts.app :title="__('Your Cart is Empty')" :breadcrumbs="['Cart' => null]" bottom-nav="cart">
    <x-empty-state icon="cart" heading="h1" :title="__('Your cart is empty')" :action-url="route('shop')" action-label="Start Shopping" action-class="btn-primary btn-lg">
        {{ __('Looks like you haven\'t added anything yet. Explore our products and find something you love.') }}
    </x-empty-state>

    @if ($popularCards)
        <section class="section section-alt" aria-labelledby="popular-title">
            <div class="container">
                <x-section-heading id="popular-title" :title="__('Popular right now')" :subtitle="__('Customer favourites you might like')" :link-url="route('shop')" />
                <div class="grid-products">
                    @foreach ($popularCards as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
