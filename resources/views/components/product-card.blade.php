{{--
    Product card — place inside a <div class="grid-products">.

    Usage:
        <x-product-card name="Ceiling Fan 56&quot;" category="Electrical" image="products/fan.jpg"
            :price="4850" :old-price="5500" :rating="4.7" :reviews="215" badge="best" />

    Real data (F-016+): <x-product-card :card="$productCardData" />  — ProductCardData, money in paisa.
    Prototype pages still pass the props below (taka) until their feature makes them dynamic.

    Props:
        badge       best | new | null (a "-X%" sale badge is added automatically when old-price is set)
        wishlisted  Show the heart as active.
        inStock     false renders the "Out of Stock" note and disables Add to Cart.

    With a card, Add to Cart posts the single variant (quickAddVariantId); products with options link to their page.
    The heart toggles the wishlist (F-022); $wishlistedProductIds comes from WishlistHeartComposer.
    moveFromWishlist  On /wishlist: "Move to Cart" instead of "Add to Cart".
--}}
@props([
    'card' => null,
    'name' => null,
    'category' => null,
    'image' => null,
    'price' => null,
    'oldPrice' => null,
    'rating' => 0,
    'reviews' => 0,
    'badge' => null,
    'alt' => null,
    'wishlisted' => false,
    'inStock' => true,
    'url' => route('shop'),
    'moveFromWishlist' => false,
    'wishlistedProductIds' => [],
])

@php
    $badgeLabels = ['best' => 'BEST SELLER', 'new' => 'NEW'];

    if ($card) {
        $name = $card->name;
        $category = $card->categoryName;
        $url = $card->url;
        $imageUrl = $card->imageUrl ?? asset('images/products/placeholder.svg');
        $rating = $card->rating;
        $reviews = $card->reviewCount;
        $badge = $card->badge;
        $inStock = $card->isAvailable;
        $wishlisted = $wishlisted || in_array($card->productId, $wishlistedProductIds, true);
        $discount = $card->oldPrice ? $card->discountPercent : null;
    } else {
        $imageUrl = asset('images/'.$image);
        $discount = $oldPrice ? (int) round(($oldPrice - $price) / $oldPrice * 100) : null;
    }
@endphp

<article class="product-card">
    <div class="product-media">
        @if ($badge || $discount)
            <span class="card-badges">
                @if ($badge)
                    <span class="badge badge-{{ $badge }}">{{ $badgeLabels[$badge] }}</span>
                @endif
                @if ($discount)
                    <span class="badge badge-sale">-{{ $discount }}%</span>
                @endif
            </span>
        @endif

        @if ($card?->productId)
            <form method="POST" action="{{ route('wishlist.toggle', $card->productId) }}" data-wishlist-form data-login-url="{{ route('login') }}">
                @csrf
                <button type="submit" @class(['wishlist-btn', 'active' => $wishlisted]) data-wishlist aria-pressed="{{ $wishlisted ? 'true' : 'false' }}" aria-label="Save {{ $name }} to wishlist">
                    <x-icon name="heart" class="icon-sm" />
                </button>
            </form>
        @else
            <button @class(['wishlist-btn', 'active' => $wishlisted]) data-wishlist aria-pressed="{{ $wishlisted ? 'true' : 'false' }}" aria-label="{{ $wishlisted ? 'Remove' : 'Add' }} {{ $name }} {{ $wishlisted ? 'from' : 'to' }} wishlist">
                <x-icon name="heart" class="icon-sm" />
            </button>
        @endif
        <a href="{{ $url }}"><img src="{{ $imageUrl }}" alt="{{ $alt ?? $name }}" loading="lazy" width="600" height="600"></a>
        @unless ($card)
            <button class="quick-view-btn" data-modal="modal-quick-view">Quick View</button>
        @endunless
    </div>

    <div class="product-info">
        <p class="product-cat">{{ $category }}</p>
        <h3 class="product-name"><a href="{{ $url }}">{{ $name }}</a></h3>
        <x-rating :rating="$rating" :reviews="$reviews" />
        <div class="price-row">
            @if ($card)
                <span class="price"><x-money :amount="$card->price" /></span>
                @if ($card->unitLabel)
                    <span class="price-unit">/ {{ $card->unitLabel }}</span>
                @endif
                @if ($card->oldPrice)
                    <span class="price-old"><x-money :amount="$card->oldPrice" /></span>
                    <span class="price-off">-{{ $discount }}%</span>
                @endif
            @else
                <span class="price">৳ {{ number_format($price) }}</span>
                @if ($oldPrice)
                    <span class="price-old">৳ {{ number_format($oldPrice) }}</span>
                    <span class="price-off">-{{ $discount }}%</span>
                @endif
            @endif
        </div>

        @unless ($inStock)
            <p class="stock-note stock-out">Out of Stock</p>
        @endunless

        <div class="card-actions">
            @if ($card?->quickAddVariantId && $moveFromWishlist)
                <form method="POST" action="{{ route('wishlist.move', $card->productId) }}">
                    @csrf
                    <button class="add-to-cart-btn" type="submit">
                        <x-icon name="cart" class="icon-sm" />
                        Move to Cart
                    </button>
                </form>
            @elseif ($card?->quickAddVariantId)
                <form method="POST" action="{{ route('cart.items.store') }}" data-cart-add>
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $card->quickAddVariantId }}">
                    <button class="add-to-cart-btn" type="submit" data-add-to-cart>
                        <x-icon name="cart" class="icon-sm" />
                        Add to Cart
                    </button>
                </form>
            @elseif ($card && $inStock)
                <a class="add-to-cart-btn" href="{{ $url }}">Choose Options</a>
            @else
                <button class="add-to-cart-btn" data-add-to-cart @disabled(! $inStock)>
                    <x-icon name="cart" class="icon-sm" />
                    {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                </button>
            @endif
        </div>
    </div>
</article>

@unless ($card)
    @pushOnce('overlays')
        <x-quick-view-modal />
    @endPushOnce
@endunless
