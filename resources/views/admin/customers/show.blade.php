{{--
    Admin customer detail (F-026). Data from Admin\CustomerController@show:
    $customer   User (role customer)
    $stats      CustomerOrderStats
    $orders     LengthAwarePaginator<Order> with item_count
    $addresses  Collection<Address> with upazila, district
--}}
@use('App\Modules\Account\Enums\UserStatus')
@php
    $timezone = config('shop.timezone');
    $isBlocked = $customer->status === UserStatus::Blocked;
@endphp

<x-layouts.admin :title="$customer->name" active="customers" :subtitle="__('Customer since :date', ['date' => \App\Support\Numerals::date($customer->created_at->timezone($timezone), 'd M Y')])"
    :back-url="route('admin.customers.index')" back-label="Customers">
    <x-slot:actions>
        @if ($isBlocked)
            <form method="POST" action="{{ route('admin.customers.unblock', $customer->id) }}">
                @csrf
                <button class="btn btn-outline" type="submit">{{ __('Unblock Customer') }}</button>
            </form>
        @else
            <button class="btn btn-danger-soft" type="button" data-modal="modal-block-customer">{{ __('Block Customer') }}</button>
        @endif
    </x-slot:actions>

    <div class="admin-grid">
        <div>
            <section class="panel panel-flush">
                <div class="panel-head">
                    <h2>Orders ({{ $stats->orderCount }})</h2>
                </div>
                @if ($orders->isEmpty())
                    <x-empty-state icon="cart" :title="__('No orders yet')" small>{{ __('This customer has not ordered anything.') }}</x-empty-state>
                @else
                    <div class="order-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Order') }}</th>
                                    <th>{{ __('Items') }}</th>
                                    <th class="num">{{ __('Total') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td>
                                            <a class="link" href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a>
                                            <span class="table-sub">{{ \App\Support\Numerals::date($order->placed_at->timezone($timezone), 'd M Y, h:i A') }}</span>
                                        </td>
                                        <td>{{ (int) $order->item_count }}</td>
                                        <td class="num strong"><x-money :amount="$order->total" /></td>
                                        <td><x-order.status :status="$order->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($orders->hasPages())
                        <div class="panel-foot">
                            <span>{{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($orders->firstItem()), 'last' => \App\Support\Numerals::localize($orders->lastItem()), 'total' => \App\Support\Numerals::localize($orders->total()), 'products' => __('orders')]) }}</span>
                            {{ $orders->links() }}
                        </div>
                    @endif
                @endif
            </section>

            <section class="panel">
                <h2 class="panel-title">Saved Addresses ({{ $addresses->count() }})</h2>
                @forelse ($addresses as $address)
                    <address class="address-block text-light form-group">
                        <strong>{{ $address->recipient_name }}</strong> · {{ $address->label->label() }}@if ($address->is_default) · Default @endif<br>
                        {{ $address->phone }}<br>
                        @foreach ($address->displayLines() as $line)
                            {{ $line }}<br>
                        @endforeach
                    </address>
                @empty
                    <p class="text-muted mb-0">{{ __('No saved addresses.') }}</p>
                @endforelse
            </section>
        </div>

        <div>
            <section class="panel">
                <h2 class="panel-title">{{ __('Profile') }}</h2>
                <div class="table-person form-group">
                    <x-avatar :name="$customer->name" />
                    <div>
                        <strong>{{ $customer->name }}</strong>
                        <span class="table-sub"><x-admin.status :status="strtolower($customer->status->label())" /></span>
                    </div>
                </div>
                <dl class="info-list">
                    <div><dt>{{ __('Phone') }}</dt><dd><a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>@if (! $customer->phone_verified_at) (not verified)@endif</dd></div>
                    <div><dt>{{ __('Email') }}</dt><dd>{{ $customer->email ?? '—' }}</dd></div>
                    <div><dt>{{ __('Joined') }}</dt><dd>{{ \App\Support\Numerals::date($customer->created_at->timezone($timezone), 'd M Y') }}</dd></div>
                    <div><dt>{{ __('Last login') }}</dt><dd>{{ $customer->last_login_at?->timezone($timezone)->format('d M Y, h:i A') ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="panel">
                <h2 class="panel-title">{{ __('Order Summary') }}</h2>
                <dl class="info-list">
                    <div><dt>{{ __('Orders') }}</dt><dd>{{ $stats->orderCount }}</dd></div>
                    <div><dt>{{ __('Spent (delivered)') }}</dt><dd><x-money :amount="$stats->deliveredTotal" /></dd></div>
                    <div><dt>{{ __('Cancelled') }}</dt><dd>{{ $stats->cancelledCount }}</dd></div>
                </dl>
            </section>
        </div>
    </div>

    @unless ($isBlocked)
        @push('overlays')
            <x-confirm-modal id="modal-block-customer" icon="lock" :title="__('Block this customer?')" confirm-label="Yes, Block" method="POST" :action="route('admin.customers.block', $customer->id)">
                {{ $customer->name }} will be signed out and cannot sign in or place new orders, also not as a guest with {{ $customer->phone }}.
            </x-confirm-modal>
        @endpush
    @endunless
</x-layouts.admin>
