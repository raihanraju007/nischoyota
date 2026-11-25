{{--
    Cart page (F-021). Data from CartController@index:
    $contents  CartContents (lines with live prices and states, coupon code)
    $totals    CartTotals (subtotal, coupon discount or error, delivery quote, free-delivery hint) — paisa
--}}
@php
    $deliveryLabel = $totals->delivery ? 'Delivery ('.$totals->delivery->zoneName.')' : 'Delivery';
@endphp

<x-layouts.app title="Shopping Cart" :breadcrumbs="['Cart' => null]" bottom-nav="cart">
    <div class="container">
        <div class="page-title-bar">
            <h1>Shopping Cart</h1>
            <p class="result-count mb-0">{{ trans_choice(':count item|:count items', $contents->lineCount()) }}</p>
        </div>

        <div class="cart-layout">
            <div>
                <div class="cart-panel">
                    <div class="cart-panel-head">
                        <span>Product</span>
                        <span>Price</span>
                        <span>Quantity</span>
                        <span>Subtotal</span>
                        <span></span>
                    </div>

                    @foreach ($contents->lines as $line)
                        <div class="cart-row">
                            <div class="cart-product">
                                <img src="{{ $line->imageUrl ?? asset('images/products/placeholder.svg') }}" alt="{{ $line->productName }}" width="76" height="76">
                                <div>
                                    <h3><a href="{{ $line->productUrl }}">{{ $line->productName }}</a></h3>
                                    @if ($line->variantLabel)
                                        <p class="cart-variant mb-0">{{ $line->variantLabel }}</p>
                                    @endif
                                    @if ($line->hasPriceChanged())
                                        <p class="cart-variant mb-0">Price changed from <x-money :amount="$line->previousPrice" /></p>
                                    @endif
                                    @if ($line->note())
                                        <p @class(['stock-note', 'mt-0', 'stock-low' => $line->state->isBuyable(), 'stock-out' => ! $line->state->isBuyable()])>{{ $line->note() }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="cart-price"><x-money :amount="$line->unitPrice" /></span>
                            <form class="cart-qty" method="POST" action="{{ route('cart.items.update', $line->itemId) }}" data-qty-submit>
                                @csrf
                                @method('PATCH')
                                <x-qty-control name="quantity" :value="$line->quantity" :label="'Quantity of '.$line->productName" small />
                            </form>
                            <span class="cart-subtotal"><x-money :amount="$line->lineTotal()" /></span>
                            <form method="POST" action="{{ route('cart.items.destroy', $line->itemId) }}">
                                @csrf
                                @method('DELETE')
                                <button class="cart-remove" type="submit" aria-label="Remove {{ $line->productName }} from cart">
                                    <x-icon name="trash" class="icon-sm" />
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="cart-footer-actions">
                    <a class="btn btn-outline" href="{{ route('shop') }}">← Continue Shopping</a>
                    <button class="btn btn-ghost" type="button" data-modal="modal-confirm-clear">Clear Cart</button>
                </div>
            </div>

            <aside class="summary-card" aria-label="Order summary">
                <h2>Order Summary</h2>

                @if ($totals->couponCode)
                    <div class="summary-line">
                        <span>Coupon <strong>{{ $totals->couponCode }}</strong></span>
                        <form method="POST" action="{{ route('cart.coupon.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
                        </form>
                    </div>
                    @if ($totals->couponError)
                        <p class="form-error mt-0">{{ $totals->couponError }}</p>
                    @endif
                @else
                    <form class="coupon-row" method="POST" action="{{ route('cart.coupon.store') }}">
                        @csrf
                        <label class="visually-hidden" for="coupon-code">Coupon code</label>
                        <input @class(['input', 'is-invalid' => $errors->has('code')]) type="text" id="coupon-code" name="code" value="{{ is_string(old('code')) ? old('code') : '' }}" placeholder="Coupon code" maxlength="30" @error('code') aria-describedby="coupon-code-error" @enderror>
                        <button class="btn btn-outline-primary" type="submit">Apply</button>
                    </form>
                    @error('code')
                        <p class="form-error mt-0" id="coupon-code-error">{{ $message }}</p>
                    @enderror
                @endif

                <x-order.totals paisa :subtotal="$totals->subtotal" :discount="$totals->discount" :discount-label="'Discount ('.$totals->couponCode.')'"
                    :delivery="$totals->deliveryCharge()" :delivery-label="$deliveryLabel" :delivery-at-checkout="$totals->delivery === null"
                    :is-free-delivery="$totals->delivery?->isFree ?? false" :total="$totals->total()" />

                @if ($totals->amountToFreeDelivery)
                    <p class="summary-note">
                        <x-icon name="truck" class="icon-sm" />
                        Add {{ \App\Support\Money::format($totals->amountToFreeDelivery) }} more for free delivery
                    </p>
                @endif

                @if ($contents->canCheckout())
                    <a class="btn btn-primary btn-lg btn-block" href="{{ route('checkout') }}">Proceed to Checkout</a>
                @else
                    <span class="btn btn-primary btn-lg btn-block is-disabled" aria-disabled="true">Proceed to Checkout</span>
                @endif
                @if ($contents->hasIssues())
                    <p class="stock-note stock-out">Please fix or remove the items marked above to continue.</p>
                @endif
                <p class="summary-note mb-0">
                    <x-icon name="lock" class="icon-sm" />
                    Secure checkout · Cash on Delivery available
                </p>
            </aside>
        </div>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-confirm-clear" title="Clear your cart?" confirm-label="Yes, Clear Cart" method="DELETE" :action="route('cart.clear')">
            This will remove all {{ trans_choice(':count item|:count items', $contents->lineCount()) }} from your cart. You can't undo this action.
        </x-confirm-modal>
    @endpush
</x-layouts.app>
