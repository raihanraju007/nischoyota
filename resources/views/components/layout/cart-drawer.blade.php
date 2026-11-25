{{--
    Slide-in mini cart, opened by any trigger with data-drawer="cart-drawer".
    Data from CartDrawerComposer: $cartContents (CartContents, paisa). Re-rendered after an add (CartItemController).
--}}
<x-drawer id="cart-drawer" title="Your Cart ({{ $cartContents->lineCount() }})">
    @forelse ($cartContents->lines as $line)
        <div class="cart-drawer-item">
            <img src="{{ $line->imageUrl ?? asset('images/products/placeholder.svg') }}" alt="{{ $line->productName }}" width="64" height="64">
            <div>
                <p class="ci-name"><a href="{{ $line->productUrl }}">{{ $line->productName }}</a></p>
                @if ($line->variantLabel)
                    <p class="ci-variant">{{ $line->variantLabel }}</p>
                @endif
                @if ($line->note())
                    <p @class(['stock-note', 'mt-0', 'stock-low' => $line->state->isBuyable(), 'stock-out' => ! $line->state->isBuyable()])>{{ $line->note() }}</p>
                @endif
                <form method="POST" action="{{ route('cart.items.update', $line->itemId) }}" data-qty-submit>
                    @csrf
                    @method('PATCH')
                    <x-qty-control name="quantity" :value="$line->quantity" :label="'Quantity of '.$line->productName" small />
                </form>
            </div>
            <div class="text-right">
                <p class="ci-price"><x-money :amount="$line->lineTotal()" /></p>
                <form method="POST" action="{{ route('cart.items.destroy', $line->itemId) }}">
                    @csrf
                    @method('DELETE')
                    <button class="remove-link" type="submit" aria-label="Remove {{ $line->productName }} from cart">{{ __('Remove') }}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-muted">{{ __('Your cart is empty.') }}</p>
    @endforelse

    @unless ($cartContents->isEmpty())
        <x-slot:footer>
            <div class="drawer-subtotal">
                <span>{{ __('Subtotal') }}</span>
                <span><x-money :amount="$cartContents->subtotal()" /></span>
            </div>
            <div class="drawer-actions">
                <a class="btn btn-outline" href="{{ route('cart') }}">{{ __('View Cart') }}</a>
                @if ($cartContents->canCheckout())
                    <a class="btn btn-primary" href="{{ route('checkout') }}">{{ __('Checkout') }}</a>
                @else
                    <span class="btn btn-primary is-disabled" aria-disabled="true">{{ __('Checkout') }}</span>
                @endif
            </div>
        </x-slot:footer>
    @endunless
</x-drawer>
