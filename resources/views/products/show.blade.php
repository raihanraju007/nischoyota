{{--
    Product page (ProductPageController@show).
    $product          Product with category.parent, brand, images, active variants (attributeValues.attribute, image)
    $badge            'new' | 'best' | null
    $descriptionHtml  Safe HTML from the Markdown description
    $optionGroups     [['attributeId', 'name', 'values' => [['id', 'label', 'hex']]]]
    $pickerVariants   Per variant: id, valueIds, sku, price, oldPrice, discount, stockText, stockClass, canBuy, imageUrl, isDefault
    $delivery         ['minDays', 'maxDays', 'zones' => [['name', 'charge', 'days']], 'freeFrom']
    $relatedCards     ProductCardData list
    From ProductReviewsComposer (F-027): $reviewSummary, $approvedReviews, $ownReview, $canWriteReview
--}}
@php
    $department = $product->category->parent ?? $product->category;
    $breadcrumbs = $product->category->parent
        ? [$department->name => route('category', $department->slug), $product->category->name => route('category', $product->category->slug), $product->name => null]
        : [$department->name => route('category', $department->slug), $product->name => null];
    $selected = collect($pickerVariants)->firstWhere('isDefault', true) ?? $pickerVariants[0] ?? null;
    $selectedValueIds = $selected['valueIds'] ?? [];
    $images = $product->images;
    $mainImageUrl = $selected['imageUrl'] ?? $images->first()?->url() ?? asset('images/products/placeholder.svg');
    $specifications = $product->specifications ?? [];
    $badgeLabels = ['best' => 'BEST SELLER', 'new' => 'NEW'];
    $tabs = array_filter([
        'description' => $descriptionHtml !== '' ? 'Description' : null,
        'specs' => $specifications !== [] ? 'Specifications' : null,
        'reviews' => 'Reviews ('.$reviewSummary->count.')',
        'shipping' => 'Shipping & Returns',
    ]);
@endphp

<x-layouts.app :title="$product->meta_title ?? $product->name" :description="$product->meta_description ?? $product->short_description"
               :breadcrumbs="$breadcrumbs" :active-nav="$department->slug">
    <div class="container product-page">
        <div class="product-top">
            {{-- Gallery --}}
            <div class="product-gallery">
                <div class="gallery-main">
                    <img src="{{ $mainImageUrl }}" alt="{{ $product->name }}" width="600" height="600" data-gallery-main>
                </div>
                @if ($images->count() > 1)
                    <div class="gallery-thumbs">
                        @foreach ($images as $image)
                            <button type="button" @class(['active' => $image->url() === $mainImageUrl]) aria-label="Show photo {{ $loop->iteration }}">
                                <img src="{{ $image->url() }}" alt="{{ $product->name }} — photo {{ $loop->iteration }}" width="120" height="120" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Detail --}}
            <div class="product-detail" data-variant-picker>
                @if ($badge)
                    <p class="detail-badges"><span class="badge badge-{{ $badge }}">{{ $badgeLabels[$badge] }}</span></p>
                @endif
                <h1>{{ $product->name }}</h1>

                <div class="product-meta-row">
                    @if ($product->brand)
                        <span>Brand: <a href="{{ route('shop', ['brand' => [$product->brand->slug]]) }}">{{ $product->brand->name }}</a></span>
                        <span class="divider" aria-hidden="true">·</span>
                    @endif
                    <a href="#tab-reviews">
                        <x-stars :rating="(float) $product->rating_average" />
                        {{ number_format((float) $product->rating_average, 1) }} ({{ trans_choice(':count review|:count reviews', $product->rating_count) }})
                    </a>
                    @if ($selected)
                        <span class="divider" aria-hidden="true">·</span>
                        <span>SKU: <span data-variant-sku>{{ $selected['sku'] }}</span></span>
                    @endif
                </div>

                @if ($selected)
                    <div class="detail-price-row">
                        <span class="detail-price" data-variant-price>{{ $selected['price'] }}</span>
                        <span class="price-unit">/ {{ $product->unitLabel() }}</span>
                        <span class="detail-price-old" data-variant-old-price @if (! $selected['oldPrice']) hidden @endif>{{ $selected['oldPrice'] }}</span>
                        <span class="badge badge-sale" data-variant-discount @if (! $selected['discount']) hidden @endif>-{{ $selected['discount'] }}%</span>
                    </div>

                    <p class="stock-note {{ $selected['stockClass'] }}" data-variant-stock>{{ $selected['stockText'] }}</p>
                @endif

                @if ($product->short_description)
                    <p class="detail-short-desc">{{ $product->short_description }}</p>
                @endif

                @foreach ($optionGroups as $group)
                    <div class="variant-group">
                        <h3>{{ $group['name'] }}:
                            <span id="sel-attr-{{ $group['attributeId'] }}">{{ collect($group['values'])->firstWhere(fn ($value) => in_array($value['id'], $selectedValueIds, true))['label'] ?? '' }}</span>
                        </h3>
                        <div class="variant-options" data-label="sel-attr-{{ $group['attributeId'] }}" data-variant-group>
                            @foreach ($group['values'] as $value)
                                <button type="button" @class(['variant-btn', 'active' => in_array($value['id'], $selectedValueIds, true)])
                                        data-value="{{ $value['label'] }}" data-value-id="{{ $value['id'] }}">
                                    @if ($value['hex'])<span class="swatch swatch-sm" style="background: {{ $value['hex'] }}" aria-hidden="true"></span>@endif
                                    {{ $value['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <p class="stock-note stock-out" data-variant-unavailable hidden>This combination is not available. Please choose another option.</p>

                <form class="buy-row" method="POST" action="{{ route('cart.items.store') }}" data-buy-form data-cart-add>
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $selected['id'] ?? '' }}" data-variant-id>
                    @if (count($pickerVariants) > 1)
                        <noscript>
                            <label class="visually-hidden" for="variant-select">Option</label>
                            <select class="select" id="variant-select" name="variant_id">
                                @foreach ($pickerVariants as $variant)
                                    <option value="{{ $variant['id'] }}" @selected($variant['isDefault']) @disabled(! $variant['canBuy'])>{{ $variant['label'] }} — {{ $variant['price'] }}</option>
                                @endforeach
                            </select>
                        </noscript>
                    @endif
                    <x-qty-control name="quantity" />
                    <button class="btn btn-primary" type="submit" data-add-to-cart data-variant-buy @disabled(! ($selected['canBuy'] ?? false))>
                        <x-icon name="cart" class="icon-sm" />
                        Add to Cart
                    </button>
                    <button class="btn btn-accent" type="submit" name="buy_now" value="1" data-variant-buy @disabled(! ($selected['canBuy'] ?? false))>Buy Now</button>
                    @php($isWishlisted = in_array($product->id, $wishlistedProductIds, true))
                    <button @class(['icon-btn-outline', 'active' => $isWishlisted]) type="submit" form="wishlist-form" data-wishlist aria-pressed="{{ $isWishlisted ? 'true' : 'false' }}" aria-label="Save {{ $product->name }} to wishlist">
                        <x-icon name="heart" class="icon-sm" />
                    </button>
                </form>
                <form id="wishlist-form" method="POST" action="{{ route('wishlist.toggle', $product->id) }}" data-wishlist-form data-login-url="{{ route('login') }}" hidden>
                    @csrf
                </form>

                <ul class="delivery-box">
                    <li>
                        <x-icon name="truck" class="icon-sm" />
                        <div><strong>Cash on Delivery Available</strong><span>Pay when your order arrives.</span></div>
                    </li>
                    <li>
                        <x-icon name="map-pin" class="icon-sm" />
                        <div><strong>Delivery across Bangladesh</strong><span>All 64 districts.</span></div>
                    </li>
                    @if ($delivery['zones'] !== [])
                        <li>
                            <x-icon name="shield" class="icon-sm" />
                            <div>
                                <strong>Estimated delivery: {{ $delivery['minDays'] === $delivery['maxDays'] ? $delivery['minDays'] : $delivery['minDays'].'–'.$delivery['maxDays'] }} days</strong>
                                <span>{{ collect($delivery['zones'])->map(fn ($zone) => $zone['name'].' '.$zone['charge'])->implode(' · ') }}</span>
                            </div>
                        </li>
                    @endif
                    <li>
                        <x-icon name="return" class="icon-sm" />
                        <div><strong>7-day easy returns</strong><span><a href="{{ route('returns') }}">See the return policy</a></span></div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="tabs">
            <div class="tab-list" role="tablist" aria-label="Product information">
                @foreach ($tabs as $key => $label)
                    <button @class(['tab-btn', 'active' => $loop->first]) type="button" role="tab" id="tabbtn-{{ $key }}" aria-controls="tab-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>

            @foreach ($tabs as $key => $label)
                <div @class(['tab-panel', 'active' => $loop->first]) id="tab-{{ $key }}" role="tabpanel" aria-labelledby="tabbtn-{{ $key }}">
                    @switch($key)
                        @case('description')
                            {!! $descriptionHtml !!}
                            @break

                        @case('specs')
                            <table class="spec-table">
                                <tbody>
                                    @foreach ($specifications as $specification)
                                        <tr><th scope="row">{{ $specification['label'] }}</th><td>{{ $specification['value'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @break

                        @case('reviews')
                            @if ($reviewSummary->count > 0)
                                <div class="reviews-summary">
                                    <div class="rating-big">
                                        <span class="rating-num">{{ number_format($reviewSummary->average, 1) }}</span> <span class="rating-of">/ 5</span>
                                        <div><x-stars :rating="$reviewSummary->average" /></div>
                                        <p>Based on {{ trans_choice(':count review|:count reviews', $reviewSummary->count) }}</p>
                                    </div>
                                    <ul class="rating-bars">
                                        @foreach ($reviewSummary->countPerRating as $stars => $count)
                                            <li><span>{{ $stars }}★</span><span class="bar"><span style="width: {{ $reviewSummary->percentFor($stars) }}%"></span></span><span>({{ $count }})</span></li>
                                        @endforeach
                                    </ul>
                                </div>

                                @foreach ($approvedReviews as $review)
                                    <x-product-review :author="$review->user->name" :date="$review->created_at->timezone(config('shop.timezone'))->format('d M Y')"
                                        :rating="$review->rating" :verified="$review->is_verified_purchase">{{ $review->comment }}</x-product-review>
                                @endforeach
                            @else
                                <p class="text-light">No reviews yet. Be the first to review this product.</p>
                            @endif

                            <div class="review-form-wrap" id="write-review">
                                @if (! auth()->check())
                                    <a class="btn btn-outline" href="{{ route('login') }}">Sign in to write a review</a>
                                @elseif ($canWriteReview)
                                    <h3>{{ $ownReview ? 'Update your review' : 'Write a review' }}</h3>
                                    @if ($ownReview)
                                        <p class="form-hint">
                                            @switch($ownReview->status->value)
                                                @case('pending') Your review is waiting for approval. @break
                                                @case('approved') Your review is published. Changing it sends it for approval again. @break
                                                @default Your review was not published. You can change it and send it again.
                                            @endswitch
                                        </p>
                                    @endif
                                    <form method="POST" action="{{ route('products.reviews.store', $product->slug) }}" class="review-form">
                                        @csrf
                                        <fieldset class="rating-input">
                                            <legend>Your rating <span class="required">*</span></legend>
                                            <div class="rating-stars">
                                            @foreach ([5, 4, 3, 2, 1] as $stars)
                                                <input type="radio" id="rating-{{ $stars }}" name="rating" value="{{ $stars }}" @checked((int) old('rating', $ownReview?->rating) === $stars)>
                                                <label for="rating-{{ $stars }}" title="{{ trans_choice(':count star|:count stars', $stars) }}">★<span class="visually-hidden">{{ trans_choice(':count star|:count stars', $stars) }}</span></label>
                                            @endforeach
                                            </div>
                                        </fieldset>
                                        @error('rating')<p class="form-error">{{ $message }}</p>@enderror
                                        <x-form.textarea name="comment" id="review-comment" label="Your review" rows="4" required maxlength="1000"
                                            :value="$ownReview?->comment" placeholder="What did you like or dislike? How was the quality?" />
                                        <button class="btn btn-primary" type="submit">{{ $ownReview ? 'Update Review' : 'Submit Review' }}</button>
                                    </form>
                                @endif
                            </div>
                            @break

                        @case('shipping')
                            <h3>Delivery</h3>
                            <p>We deliver across Bangladesh. Cash on Delivery is available everywhere — you pay when your order arrives.</p>
                            @if ($delivery['zones'] !== [])
                                <ul>
                                    @foreach ($delivery['zones'] as $zone)
                                        <li><strong>{{ $zone['name'] }}:</strong> {{ $zone['charge'] }}, {{ $zone['days'] }}</li>
                                    @endforeach
                                    @if ($delivery['freeFrom'])
                                        <li><strong>Free delivery</strong> on orders of {{ $delivery['freeFrom'] }} or more</li>
                                    @endif
                                </ul>
                            @endif
                            <h3>Returns</h3>
                            <p>You can return this product within 7 days of delivery if it is unused and in its original packaging. See the <a href="{{ route('returns') }}">Return &amp; Refund Policy</a> for details.</p>
                            @break
                    @endswitch
                </div>
            @endforeach
        </div>
    </div>

    @if ($relatedCards !== [])
        <section class="section" aria-labelledby="related-title">
            <div class="container">
                <x-section-heading id="related-title" title="You May Also Like" :subtitle="'More products from '.$department->name" :link-url="route('category', $department->slug)" />
                <div class="grid-products">
                    @foreach ($relatedCards as $card)
                        <x-product-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <script type="application/json" id="variant-data">@json($pickerVariants)</script>
</x-layouts.app>
