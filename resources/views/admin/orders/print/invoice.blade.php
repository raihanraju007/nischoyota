{{--
    Order copy for inside the parcel, one A4 page (F-025b). Data from Admin\OrderPrintController@invoice:
    $order  Order with items
    $store  StoreSettings
--}}
@use('App\Support\Money')

<x-layouts.print :title="'Order copy '.$order->order_number" :back-url="route('admin.orders.show', $order)">
    <section class="print-sheet invoice">
        <header class="invoice-head">
            <div>
                <img src="{{ asset('images/logo-mark.png') }}" alt="" width="40" height="40">
                <div>
                    <strong class="invoice-store">{{ $store->storeName }}</strong>
                    @if ($store->storeAddress)
                        <span>{{ $store->storeAddress }}</span>
                    @endif
                    <span>{{ $store->supportPhone }} · {{ $store->supportEmail }}</span>
                </div>
            </div>
            <div class="invoice-title">
                <h1>Order Copy</h1>
                <span>#{{ $order->order_number }}</span>
                <span>{{ $order->placed_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}</span>
            </div>
        </header>

        <div class="invoice-parties">
            <div>
                <span class="slip-label">Customer</span>
                <strong>{{ $order->customer_name }}</strong>
                <span>{{ $order->customer_phone }}</span>
                @if ($order->customer_email)
                    <span>{{ $order->customer_email }}</span>
                @endif
            </div>
            <div>
                <span class="slip-label">Ship to</span>
                <address>
                    {{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>
                    {{ $order->upazila_name }}, {{ $order->district_name }} {{ $order->postal_code }}<br>
                    {{ $order->division_name }}, Bangladesh
                </address>
            </div>
            <div>
                <span class="slip-label">Payment</span>
                <strong>{{ $order->payment_method->label() }}</strong>
                <span>{{ $order->payment_status->label() }}</span>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if ($item->variant_label)
                                <span class="table-sub">{{ $item->variant_label }}</span>
                            @endif
                        </td>
                        <td>{{ $item->sku }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ Money::format($item->unit_price) }}</td>
                        <td class="num">{{ Money::format($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="invoice-totals">
            <div><dt>Subtotal</dt><dd>{{ Money::format($order->subtotal) }}</dd></div>
            @if ($order->discount_total > 0)
                <div><dt>Discount ({{ $order->coupon_code }})</dt><dd>− {{ Money::format($order->discount_total) }}</dd></div>
            @endif
            <div><dt>Delivery ({{ $order->delivery_zone_name }})</dt><dd>{{ $order->delivery_charge === 0 ? 'FREE' : Money::format($order->delivery_charge) }}</dd></div>
            <div class="invoice-grand"><dt>Total</dt><dd>{{ Money::format($order->total) }}</dd></div>
        </dl>

        <footer class="invoice-foot">
            <p><strong>Thank you for shopping with {{ $store->storeName }}!</strong></p>
            <p>Returns and exchanges: see our return policy at {{ route('returns') }} or call {{ $store->supportPhone }}.</p>
        </footer>
    </section>
</x-layouts.print>
