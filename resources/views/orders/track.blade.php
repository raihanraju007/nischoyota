{{--
    Guest order tracking (F-024). Data from TrackOrderController:
    $order     Order with items and statusHistory, or null before a lookup
    $timeline  list<TimelineStep>
--}}
<x-layouts.app :title="__('Track Your Order')" :breadcrumbs="['Track Order' => null]">
    <div class="container">
        <div class="page-title-bar">
            <h1>{{ __('Track Your Order') }}</h1>
        </div>

        <div class="checkout-panel track-form-panel">
            <form method="POST" action="{{ route('orders.track.lookup') }}">
                @csrf
                <div class="form-row">
                    <x-form.input name="order_number" id="track-order" :label="__('Order Number')" :value="old('order_number', $order?->order_number)" placeholder="ORD-10025" required />
                    <x-form.input name="phone" id="track-phone" type="tel" :label="__('Phone Number')" :value="old('phone')" placeholder="01712-345678" :hint="__('The phone number used on the order')" required />
                </div>
                <button class="btn btn-primary" type="submit">{{ __('Track') }}</button>
            </form>
        </div>

        @if ($order)
            <div class="checkout-layout track-layout">
                <div class="checkout-panel">
                    <h2>Order Status — #{{ $order->order_number }} <x-order.status :status="$order->status" /></h2>
                    <x-order.timeline :steps="array_map(fn ($step) => $step->toArray(), $timeline)" />
                </div>

                <aside aria-label="{{ __('Order details') }}">
                    <div class="summary-card summary-card-static">
                        <h2>{{ __('Order Details') }}</h2>
                        <x-order.items paisa :items="$order->items->map(fn ($item) => ['name' => $item->product_name, 'imageUrl' => $item->image_url ?? asset('images/products/placeholder.svg'), 'variant' => $item->variant_label, 'quantity' => $item->quantity, 'total' => $item->line_total])->all()" />
                        <x-order.totals paisa :subtotal="$order->subtotal" :discount="$order->discount_total" :delivery="$order->delivery_charge"
                            :delivery-label="__('Delivery (:zone)', ['zone' => __($order->delivery_zone_name)])" :is-free-delivery="$order->delivery_charge === 0" :total="$order->total" />
                    </div>
                    <div class="summary-card summary-card-static">
                        <h2>{{ __('Delivery Address') }}</h2>
                        <address class="text-muted mb-0 address-block">
                            {{ $order->customer_name }}<br>
                            {{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif,<br>
                            {{ $order->upazila_name }}, {{ $order->district_name }} {{ $order->postal_code }}
                        </address>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.app>
