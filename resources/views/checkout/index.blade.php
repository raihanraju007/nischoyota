{{--
    Checkout (F-023). Data from CheckoutController@show:
    $lines             list<CartLine> (live prices, paisa)
    $totals            CheckoutTotals for the selected address (delivery null until an area is chosen)
    $savedAddresses    signed-in customer's addresses (empty for guests); $selectedAddressId
    $customer          signed-in User or null (pre-fills name / phone / email)
    $paymentMethods    list<PaymentMethod>; $checkoutToken one-time page token
    $divisionOptions / $districtOptions / $upazilaOptions  for <x-address-fields>
--}}
@php
    $items = array_map(fn ($line) => [
        'name' => $line->productName,
        'imageUrl' => $line->imageUrl ?? asset('images/products/placeholder.svg'),
        'variant' => $line->variantLabel,
        'quantity' => $line->quantity,
        'total' => $line->lineTotal(),
    ], $lines);
    $isNewAddress = $selectedAddressId === null;
    $comingSoon = [
        ['title' => 'bKash / Mobile Banking', 'text' => 'Coming soon'],
        ['title' => 'Card Payment', 'text' => 'Coming soon'],
    ];
@endphp

<x-layouts.app title="Checkout" :breadcrumbs="['Cart' => route('cart'), 'Checkout' => null]">
    <div class="container">
        <div class="page-title-bar">
            <h1>Checkout</h1>
        </div>

        <div class="checkout-layout">
            <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" data-checkout
                  data-quote-url="{{ route('checkout.delivery-quote') }}">
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
                <input type="hidden" name="expected_total" value="{{ $totals->total() }}" data-expected-total>

                <div class="checkout-panel">
                    <h2><span class="step-num">1</span> Customer Information</h2>
                    <div class="form-row">
                        <x-form.input name="name" id="co-name" label="Full Name" :value="old('name', $customer?->name)" maxlength="100" required />
                        <x-form.input name="phone" id="co-phone" type="tel" label="Phone Number" :value="old('phone', $customer?->phone)" placeholder="01712-345678"
                            :hint="$customer ? '11-digit Bangladeshi mobile number' : 'We will send a code to confirm this number'" required />
                    </div>
                    <x-form.input name="email" id="co-email" type="email" label="Email" :value="old('email', $customer?->email)" placeholder="you@example.com" optional />
                </div>

                <div class="checkout-panel">
                    <h2><span class="step-num">2</span> Delivery Address</h2>

                    @if ($savedAddresses->isNotEmpty())
                        @foreach ($savedAddresses as $address)
                            <label @class(['payment-option', 'selected' => $selectedAddressId === $address->id])>
                                <input type="radio" name="address_id" value="{{ $address->id }}" data-upazila-id="{{ $address->upazila_id }}" @checked($selectedAddressId === $address->id)>
                                <div>
                                    <strong>{{ $address->label->label() }} — {{ $address->recipient_name }}</strong>
                                    <span>{{ implode(' · ', $address->displayLines()) }}</span>
                                </div>
                                @if ($address->is_default)
                                    <span class="badge badge-muted">Default</span>
                                @endif
                            </label>
                        @endforeach
                        <label @class(['payment-option', 'selected' => $isNewAddress])>
                            <input type="radio" name="address_id" value="" @checked($isNewAddress)>
                            <div>
                                <strong>Use a new address</strong>
                                <span>Fill in the fields below</span>
                            </div>
                        </label>
                    @endif

                    <div class="checkout-new-address" data-new-address>
                        <x-address-fields id-prefix="co" :division-options="$divisionOptions" :district-options="$districtOptions" :upazila-options="$upazilaOptions"
                            :division-id="old('division_id')" :district-id="old('district_id')" :upazila-id="old('upazila_id')" />
                        <x-form.input name="area" id="co-area" label="Area" :value="old('area')" placeholder="e.g. Road 5, Block B" maxlength="100" />
                        <x-form.textarea name="address_line" id="co-address" label="Full Address" placeholder="House number, road, landmark…" maxlength="255" />
                        <div class="form-row">
                            <x-form.input name="postal_code" id="co-postal" label="Postal Code" :value="old('postal_code')" inputmode="numeric" maxlength="4" />
                            @if ($customer)
                                <div class="form-group">
                                    <label class="check check-aligned">
                                        <input type="checkbox" name="save_address" value="1" @checked(old('save_address', true))>
                                        Save this address for next time
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="checkout-panel">
                    <h2><span class="step-num">3</span> Payment Method</h2>
                    @foreach ($paymentMethods as $method)
                        <label @class(['payment-option', 'selected' => $loop->first])>
                            <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($loop->first)>
                            <div>
                                <strong>{{ $method->label() }}</strong>
                                <span>Pay in cash when your order is delivered.</span>
                            </div>
                            <span class="badge badge-stock">Available</span>
                        </label>
                    @endforeach
                    @foreach ($comingSoon as $option)
                        <label class="payment-option disabled">
                            <input type="radio" disabled>
                            <div>
                                <strong>{{ $option['title'] }}</strong>
                                <span>{{ $option['text'] }}</span>
                            </div>
                            <span class="badge badge-muted">Coming Soon</span>
                        </label>
                    @endforeach
                    <label class="check payment-terms">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                        <span>I agree to the <a href="{{ route('terms') }}">Terms &amp; Conditions</a></span>
                    </label>
                    @error('terms')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            <aside class="summary-card" aria-label="Order summary">
                <h2>Order Summary</h2>
                <x-order.items :items="$items" paisa />
                <x-order.totals paisa :subtotal="$totals->subtotal" :discount="$totals->discount()" :discount-label="'Discount ('.$totals->couponCode.')'"
                    :delivery="$totals->deliveryCharge()" :delivery-label="$totals->delivery ? 'Delivery ('.$totals->delivery->zoneName.')' : 'Delivery'"
                    :delivery-at-checkout="$totals->delivery === null" delivery-pending-text="Choose your area"
                    :is-free-delivery="$totals->delivery?->isFree ?? false" :total="$totals->total()" />
                @if ($totals->couponError)
                    <p class="form-error">Coupon {{ $totals->couponCode }}: {{ $totals->couponError }} <a href="{{ route('cart') }}">Remove it in your cart</a>.</p>
                @endif
                <button class="btn btn-primary btn-lg btn-block" type="submit" form="checkout-form">Place Order</button>
                <p class="summary-note mb-0">🔒 Your information is safe with us</p>
            </aside>
        </div>
    </div>
</x-layouts.app>
