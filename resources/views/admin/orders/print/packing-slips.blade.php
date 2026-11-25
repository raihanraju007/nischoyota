{{--
    Packing slips / courier labels, two per A4 page (F-025b). Data from Admin\OrderPrintController:
    $orders   iterable<Order> with items
    $weights  order id => parcel weight in grams, or null when unknown
    $store    StoreSettings (sender)
--}}
@use('App\Modules\Order\Enums\PaymentStatus')
@use('App\Support\Money')

<x-layouts.print :title="count($orders) === 1 ? 'Packing slip' : 'Packing slips ('.count($orders).')'" :back-url="route('admin.orders.index')">
    @foreach (collect($orders)->chunk(2) as $pageOrders)
        <section class="print-sheet print-sheet-slips">
            @foreach ($pageOrders as $order)
                @php
                    $grams = $weights[$order->id] ?? null;
                @endphp
                <article class="packing-slip">
                    <header class="slip-head">
                        <div>
                            <span class="slip-label">From</span>
                            <strong>{{ $store->storeName }}</strong>
                            <span>{{ $store->supportPhone }}</span>
                            @if ($store->storeAddress)
                                <span>{{ $store->storeAddress }}</span>
                            @endif
                        </div>
                        <div class="slip-number">
                            <span class="slip-label">Order</span>
                            <strong>{{ $order->order_number }}</strong>
                            <span>{{ $order->placed_at->timezone(config('shop.timezone'))->format('d M Y') }}</span>
                        </div>
                    </header>

                    <div class="slip-body">
                        <div class="slip-to">
                            <span class="slip-label">To</span>
                            <strong class="slip-name">{{ $order->customer_name }}</strong>
                            <strong class="slip-phone">{{ $order->customer_phone }}</strong>
                            <address>
                                {{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>
                                {{ $order->upazila_name }}, {{ $order->district_name }} {{ $order->postal_code }}<br>
                                {{ $order->division_name }}
                            </address>
                        </div>

                        <div class="slip-cod">
                            @if ($order->status->isFinal())
                                <span class="slip-label">{{ $order->status->label() }}</span>
                                <strong>DO NOT SHIP</strong>
                            @elseif ($order->payment_status === PaymentStatus::Paid)
                                <span class="slip-label">Payment</span>
                                <strong>PAID</strong>
                            @else
                                <span class="slip-label">Collect (COD)</span>
                                <strong>{{ Money::format($order->total) }}</strong>
                            @endif
                        </div>
                    </div>

                    <dl class="slip-facts">
                        <div><dt>Items</dt><dd>{{ $order->items->sum('quantity') }}</dd></div>
                        <div><dt>Weight</dt><dd>{{ $grams === null ? '—' : number_format($grams / 1000, 2).' kg' }}</dd></div>
                        <div><dt>Zone</dt><dd>{{ $order->delivery_zone_name }}</dd></div>
                        <div><dt>Courier</dt><dd>{{ $order->courier_name ?? '—' }}@if ($order->tracking_code) · {{ $order->tracking_code }}@endif</dd></div>
                    </dl>
                    <p class="slip-note">Please call the receiver before delivery. Handle with care.</p>
                </article>
            @endforeach
        </section>
    @endforeach
</x-layouts.print>
