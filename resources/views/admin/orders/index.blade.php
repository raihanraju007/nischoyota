{{--
    Admin order list (F-025). Data from Admin\OrderController@index:
    $orders      LengthAwarePaginator<Order> with item_count
    $tabCounts   'all' and status value => count (search + date applied)
    $filter      AdminOrderFilter (status, search, dateRange)
    $statuses    OrderStatus tabs
    $dateRanges  OrderDateRange cases
--}}
@php
    $isFiltered = $filter->status !== null || $filter->search !== null || $filter->dateRange->value !== 'all';
@endphp

<x-layouts.admin :title="__('Orders')" active="orders" :subtitle="__('Confirm, pack and ship customer orders.')">
    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="{{ __('Filter by status') }}">
            <a href="{{ route('admin.orders.index', $filter->queryFor(null)) }}" @class(['active' => $filter->status === null]) @if ($filter->status === null) aria-current="page" @endif>
                {{ __('All') }} <span class="tab-count">{{ $tabCounts['all'] }}</span>
            </a>
            @foreach ($statuses as $status)
                <a href="{{ route('admin.orders.index', $filter->queryFor($status)) }}" @class(['active' => $filter->status === $status]) @if ($filter->status === $status) aria-current="page" @endif>
                    {{ $status->label() }} <span class="tab-count">{{ $tabCounts[$status->value] }}</span>
                </a>
            @endforeach
        </nav>

        <form class="admin-toolbar" method="GET" action="{{ route('admin.orders.index') }}" role="search">
            @if ($filter->status)
                <input type="hidden" name="status" value="{{ $filter->status->value }}">
            @endif
            <div class="admin-search">
                <x-icon name="search" class="icon-sm" />
                <label for="order-search" class="visually-hidden">{{ __('Search orders') }}</label>
                <input class="input" type="search" id="order-search" name="search" value="{{ $filter->search }}" placeholder="{{ __('Order no, name or phone…') }}">
            </div>
            <label for="order-date" class="visually-hidden">{{ __('Date range') }}</label>
            <select class="select" id="order-date" name="date">
                @foreach ($dateRanges as $range)
                    <option value="{{ $range->value }}" @selected($filter->dateRange === $range)>{{ $range->label() }}</option>
                @endforeach
            </select>
            <div class="toolbar-end">
                <button class="btn btn-outline btn-sm" type="submit"><x-icon name="filter" class="icon-sm" /> {{ __('Filter') }}</button>
            </div>
        </form>

        @if ($orders->isEmpty())
            <x-empty-state icon="cart" :title="__('No orders here')" small>
                @if ($isFiltered)
                    <a href="{{ route('admin.orders.index') }}">{{ __('Show all orders') }}</a>
                @else
                    New orders will appear here.
                @endif
            </x-empty-state>
        @else
            <form id="bulk-print-form" class="admin-toolbar bulk-bar" method="POST" action="{{ route('admin.orders.packing-slips') }}" target="_blank">
                @csrf
                <label class="check"><input type="checkbox" name="move_to_processing" value="1"> {{ __('Move confirmed orders to Processing') }}</label>
                <button class="btn btn-outline btn-sm" type="submit">{{ __('Print packing slips') }}</button>
                @error('ids')<span class="form-error mb-0">{{ $message }}</span>@enderror
            </form>

            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" data-check-all="ids[]" aria-label="{{ __('Select all orders on this page') }}"></th>
                            <th>{{ __('Order') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Items') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th class="num">{{ __('Total') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="col-check"><input type="checkbox" name="ids[]" value="{{ $order->id }}" form="bulk-print-form" aria-label="Select #{{ $order->order_number }}"></td>
                                <td>
                                    <a class="link" href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a>
                                    <span class="table-sub">{{ \App\Support\Numerals::date($order->placed_at->timezone(config('shop.timezone')), 'd M Y, h:i A') }}</span>
                                </td>
                                <td>
                                    {{ $order->customer_name }}
                                    <span class="table-sub">{{ $order->customer_phone }}@if ($order->user_id === null) · Guest @endif</span>
                                </td>
                                <td>{{ (int) $order->item_count }}</td>
                                <td>
                                    {{ $order->payment_method->label() }}
                                    <span class="table-sub"><x-admin.status :status="strtolower($order->payment_status->label())" /></span>
                                </td>
                                <td class="num strong"><x-money :amount="$order->total" /></td>
                                <td><x-order.status :status="$order->status" /></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn icon-btn-sm" href="{{ route('admin.orders.show', $order) }}" aria-label="View #{{ $order->order_number }}">
                                            <x-icon name="eye" class="icon-sm" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel-foot">
                <span>{{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($orders->firstItem()), 'last' => \App\Support\Numerals::localize($orders->lastItem()), 'total' => \App\Support\Numerals::localize($orders->total()), 'products' => __('orders')]) }}</span>
                {{ $orders->links() }}
            </div>
        @endif
    </section>
</x-layouts.admin>
