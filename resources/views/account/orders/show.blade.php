{{--
    Order detail (F-024). Data from Account\OrderController@show:
    $order     Order with items and statusHistory (snapshots, paisa)
    $timeline  list<TimelineStep>
--}}
@php
    $items = $order->items->map(fn ($item) => [
        'name' => $item->product_name,
        'imageUrl' => $item->image_url ?? asset('images/products/placeholder.svg'),
        'variant' => $item->variant_label,
        'quantity' => $item->quantity,
        'total' => $item->line_total,
    ])->all();
    $isEnded = in_array($order->status, [\App\Modules\Order\Enums\OrderStatus::Delivered, \App\Modules\Order\Enums\OrderStatus::Cancelled, \App\Modules\Order\Enums\OrderStatus::Returned], true);
@endphp

<x-layouts.account :title="'Order #'.$order->order_number" active="orders" :breadcrumbs="['My Orders' => route('account.orders'), '#'.$order->order_number => null]">
    <div class="panel">
        <div class="panel-head">
            <h2>Order Information</h2>
            <x-order.status :status="$order->status" />
        </div>
        <div class="summary-line"><span>Order Date</span><strong>{{ $order->placed_at->timezone(config('shop.timezone'))->format('d M Y') }}</strong></div>
        <div class="summary-line"><span>Payment Method</span><strong>{{ $order->payment_method->label() }}</strong></div>
        @if ($order->courier_name)
            <div class="summary-line"><span>Courier</span><strong>{{ $order->courier_name }}@if ($order->tracking_code) · {{ $order->tracking_code }}@endif</strong></div>
        @endif
        @unless ($isEnded)
            <div class="summary-line"><span>Estimated Delivery</span><strong>{{ $order->estimatedDeliveryLabel() }}</strong></div>
        @endunless
    </div>

    <div class="panel">
        <h2 class="panel-title">Items</h2>
        <x-order.items :items="$items" paisa />
        <hr class="divider-h">
        <x-order.totals paisa :subtotal="$order->subtotal" :discount="$order->discount_total" :discount-label="'Discount ('.$order->coupon_code.')'"
            :delivery="$order->delivery_charge" :delivery-label="'Delivery ('.$order->delivery_zone_name.')'" :is-free-delivery="$order->delivery_charge === 0" :total="$order->total" />
    </div>

    <div class="panel">
        <h2 class="panel-title">Customer &amp; Delivery</h2>
        <div class="form-row">
            <div>
                <strong>Customer</strong>
                <address class="text-muted address-block address-block-spaced">
                    {{ $order->customer_name }}<br>{{ $order->customer_phone }}@if ($order->customer_email)<br>{{ $order->customer_email }}@endif
                </address>
            </div>
            <div>
                <strong>Delivery Address</strong>
                <address class="text-muted address-block address-block-spaced">
                    {{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>
                    {{ $order->upazila_name }}, {{ $order->district_name }} {{ $order->postal_code }}<br>
                    {{ $order->division_name }}, Bangladesh
                </address>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Status Timeline</h2>
        <x-order.timeline :steps="array_map(fn ($step) => $step->toArray(), $timeline)" />
        <div class="cart-footer-actions">
            <form method="POST" action="{{ route('account.orders.buy-again', $order->order_number) }}">
                @csrf
                <button class="btn btn-outline" type="submit">Buy Again</button>
            </form>
            @if ($order->status->isCancellableByCustomer())
                <button class="btn btn-danger-soft" type="button" data-modal="modal-cancel-order">Cancel Order</button>
            @endif
        </div>
    </div>

    @if ($order->status->isCancellableByCustomer())
        @push('overlays')
            <x-confirm-modal id="modal-cancel-order" icon="alert" title="Cancel this order?" cancel-label="Keep Order" confirm-label="Yes, Cancel"
                method="POST" :action="route('account.orders.cancel', $order->order_number)">
                This will cancel order #{{ $order->order_number }}. This action cannot be undone.
            </x-confirm-modal>
        @endpush
    @endif
</x-layouts.account>
