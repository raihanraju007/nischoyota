{{--
    Admin order detail (F-025). Data from Admin\OrderController@show:
    $order               Order with items, statusHistory.user, payments, user
    $history             list<TimelineStep> (who + note per change)
    $customerOrderCount  orders on the customer's account, null for guests
--}}
@use('App\Modules\Order\Enums\OrderStatus')
@use('App\Modules\Order\Enums\PaymentStatus')
@php
    $timezone = config('shop.timezone');
    $items = $order->items->map(fn ($item) => [
        'name' => $item->product_name.($item->is_preorder ? ' (pre-order)' : ''),
        'imageUrl' => $item->image_url ?? asset('images/products/placeholder.svg'),
        'variant' => trim(($item->variant_label ? $item->variant_label.' · ' : '').$item->sku),
        'quantity' => $item->quantity,
        'total' => $item->line_total,
    ])->all();
    $nextStatuses = collect($order->status->allowedNextStatuses())
        ->reject(fn ($status) => $status === OrderStatus::Cancelled)
        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
        ->all();
    $canCancel = $order->status->canTransitionTo(OrderStatus::Cancelled);
    $canMarkPaid = $order->payment_status === PaymentStatus::Pending && ! $order->status->isFinal();
@endphp

<x-layouts.admin :title="'Order #'.$order->order_number" active="orders"
    :subtitle="'Placed on '.$order->placed_at->timezone($timezone)->format('d M Y \a\t h:i A')" :back-url="route('admin.orders.index')" back-label="Orders">
    <x-slot:actions>
        <a class="btn btn-outline" href="{{ route('admin.orders.packing-slip', $order) }}" target="_blank">Packing Slip</a>
        <a class="btn btn-outline" href="{{ route('admin.orders.invoice', $order) }}" target="_blank">Order Copy</a>
        @if ($canCancel)
            <button class="btn btn-danger-soft" type="button" data-modal="modal-cancel-order">Cancel Order</button>
        @endif
    </x-slot:actions>

    <div class="admin-grid">
        <div>
            <section class="panel">
                <div class="panel-head">
                    <h2>Items ({{ $order->items->sum('quantity') }})</h2>
                    <x-order.status :status="$order->status" />
                </div>
                <x-order.items :items="$items" paisa />
                <hr class="divider-h">
                <x-order.totals paisa :subtotal="$order->subtotal" :discount="$order->discount_total" :discount-label="'Discount ('.$order->coupon_code.')'"
                    :delivery="$order->delivery_charge" :delivery-label="'Delivery ('.$order->delivery_zone_name.')'" :is-free-delivery="$order->delivery_charge === 0" :total="$order->total" />
            </section>

            <section class="panel">
                <h2 class="panel-title">Status History</h2>
                <x-order.timeline :steps="array_map(fn ($step) => $step->toArray(), $history)" />
            </section>

            <section class="panel">
                <h2 class="panel-title">Internal Note</h2>
                <form method="POST" action="{{ route('admin.orders.note', $order) }}">
                    @csrf
                    @method('PUT')
                    <x-form.textarea name="admin_note" id="admin-note" label="Only staff can see this" rows="3" :value="$order->admin_note" placeholder="e.g. Customer asked for delivery after 5 PM" />
                    <button class="btn btn-outline" type="submit">Save Note</button>
                </form>
            </section>
        </div>

        <div>
            @if ($nextStatuses !== [])
                <section class="panel">
                    <h2 class="panel-title">Update Status</h2>
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf
                        <x-form.select name="status" id="order-status" label="Move to" :options="$nextStatuses" required />
                        <x-form.input name="courier_name" id="courier-name" label="Courier" :value="$order->courier_name" list="courier-names" maxlength="50"
                            hint="Needed to mark the order shipped." placeholder="Pathao, Steadfast, RedX…" />
                        <datalist id="courier-names">
                            @foreach (['Pathao', 'Steadfast', 'RedX', 'Sundarban Courier', 'eCourier', 'Paperfly'] as $courier)
                                <option value="{{ $courier }}">
                            @endforeach
                        </datalist>
                        <x-form.input name="tracking_code" id="tracking-code" label="Tracking Code" :value="$order->tracking_code" maxlength="50" optional />
                        <x-form.input name="note" id="status-note" label="Note for the history" maxlength="255" optional placeholder="e.g. Confirmed by phone call" />
                        <button class="btn btn-primary btn-block" type="submit">Save Status</button>
                    </form>
                </section>
            @elseif ($order->courier_name)
                <section class="panel">
                    <h2 class="panel-title">Shipping</h2>
                    <dl class="info-list">
                        <div><dt>Courier</dt><dd>{{ $order->courier_name }}</dd></div>
                        @if ($order->tracking_code)
                            <div><dt>Tracking</dt><dd>{{ $order->tracking_code }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif

            <section class="panel">
                <h2 class="panel-title">Customer</h2>
                <div class="table-person form-group">
                    <x-avatar :name="$order->customer_name" />
                    <div>
                        <strong>{{ $order->customer_name }}</strong>
                        <span class="table-sub">
                            @if ($order->user)
                                {{ trans_choice(':count order|:count orders', $customerOrderCount) }} · since {{ $order->user->created_at->format('M Y') }}
                            @else
                                Guest order
                            @endif
                        </span>
                    </div>
                </div>
                <dl class="info-list">
                    <div><dt>Phone</dt><dd><a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></dd></div>
                    @if ($order->customer_email)
                        <div><dt>Email</dt><dd>{{ $order->customer_email }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="panel">
                <h2 class="panel-title">Delivery Address</h2>
                <address class="address-block text-light">
                    <strong>{{ $order->customer_name }}</strong><br>
                    {{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>
                    {{ $order->upazila_name }}, {{ $order->district_name }} {{ $order->postal_code }}<br>
                    {{ $order->division_name }}, Bangladesh
                </address>
            </section>

            <section class="panel">
                <h2 class="panel-title">Payment</h2>
                <dl class="info-list">
                    <div><dt>Method</dt><dd>{{ $order->payment_method->label() }}</dd></div>
                    <div><dt>Status</dt><dd><x-admin.status :status="strtolower($order->payment_status->label())" /></dd></div>
                    <div><dt>{{ match ($order->payment_status) {
                        PaymentStatus::Pending => 'Amount due',
                        PaymentStatus::Paid => 'Amount paid',
                        PaymentStatus::Refunded => 'Amount refunded',
                        PaymentStatus::Cancelled => 'Not collected',
                    } }}</dt><dd><x-money :amount="$order->total" /></dd></div>
                </dl>
                @if ($canMarkPaid)
                    <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}">
                        @csrf
                        <button class="btn btn-outline btn-block" type="submit">Mark as Paid</button>
                    </form>
                @endif
            </section>
        </div>
    </div>

    @if ($canCancel)
        @push('overlays')
            <x-modal id="modal-cancel-order" centered>
                <div class="confirm-icon"><x-icon name="alert" class="icon" /></div>
                <h2 id="modal-cancel-order-title">Cancel this order?</h2>
                <p class="mb-0">Stock for all items goes back to inventory and the coupon use is released.</p>
                <x-slot:footer>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keep Order</button>
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <input type="hidden" name="note" value="Cancelled by staff">
                        <button class="btn btn-danger" type="submit">Yes, Cancel Order</button>
                    </form>
                </x-slot:footer>
            </x-modal>
        @endpush
    @endif
</x-layouts.admin>
