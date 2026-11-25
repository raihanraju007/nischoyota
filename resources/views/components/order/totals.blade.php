{{--
    Subtotal / discount / delivery / total lines used by cart, checkout and order pages.
    Prototype pages pass taka; real data passes paisa with :paisa="true" (F-021+).
    delivery-at-checkout shows delivery-pending-text; is-free-delivery shows "FREE".
    data-summary-* hooks let the checkout script update the delivery line and total.
--}}
@props([
    'subtotal',
    'total',
    'discount' => 0,
    'discountLabel' => 'Discount',
    'delivery' => 0,
    'deliveryLabel' => 'Delivery',
    'isFreeDelivery' => false,
    'deliveryAtCheckout' => false,
    'deliveryPendingText' => 'Calculated at checkout',
    'paisa' => false,
])

@php
    $format = fn (int $amount): string => $paisa ? \App\Support\Money::format($amount) : '৳ '.number_format($amount);
@endphp

<div class="summary-line"><span>Subtotal</span><strong>{{ $format($subtotal) }}</strong></div>
@if ($discount)
    <div class="summary-line"><span>{{ $discountLabel }}</span><span class="discount-val">− {{ $format($discount) }}</span></div>
@endif
<div class="summary-line">
    <span data-summary-delivery-label>{{ $deliveryLabel }}</span>
    @if ($deliveryAtCheckout)
        <span data-summary-delivery>{{ $deliveryPendingText }}</span>
    @elseif ($isFreeDelivery)
        <span class="free" data-summary-delivery>FREE</span>
    @else
        <strong data-summary-delivery>{{ $format($delivery) }}</strong>
    @endif
</div>
<div class="summary-total">
    <span class="label">Total</span>
    <span class="amount" data-summary-total>{{ $format($total) }}</span>
</div>
