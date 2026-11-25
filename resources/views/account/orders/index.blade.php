{{--
    My Orders (F-024). Data from Account\OrderController@index:
    $orders          LengthAwarePaginator<Order> (newest first, 10 per page)
    $activeStatus    OrderStatus or null (All); $statusFilters list<OrderStatus>
--}}
<x-layouts.account title="My Orders" active="orders" :breadcrumbs="['My Orders' => null]">
    @if ($orders->total() === 0 && $activeStatus === null)
        <div class="panel">
            <x-empty-state icon="cart" title="No orders yet" :action-url="route('shop')" action-label="Start Shopping" small>
                When you place your first order it will show up here.
            </x-empty-state>
        </div>
    @else
        <div class="subcategory-chips">
            <a href="{{ route('account.orders') }}" @class(['active' => $activeStatus === null])>All</a>
            @foreach ($statusFilters as $filter)
                <a href="{{ route('account.orders', ['status' => $filter->value]) }}" @class(['active' => $activeStatus === $filter])>{{ $filter->label() }}</a>
            @endforeach
        </div>

        <div class="panel">
            @if ($orders->isEmpty())
                <p class="mb-0 text-light">No {{ strtolower($activeStatus->label()) }} orders.</p>
            @else
                <x-account.order-table :orders="$orders" />
                {{ $orders->links() }}
            @endif
        </div>
    @endif
</x-layouts.account>
