{{--
    Admin dashboard (F-028). Data from Admin\DashboardController@show:
    $stats             DashboardStats (cached sales numbers, chart, status counts, new customers; paisa)
    $period            ChartPeriod of the sales chart, $periods all of them
    $recentOrders      Collection<Order>, newest first
    $lowStockVariants  Collection<ProductVariant> with product.coverImage, attributeValues.attribute
    $lowStockCount     Variants at or below the low-stock threshold
--}}
@use('App\Modules\Order\Enums\OrderStatus')
@use('App\Support\Money')

<x-layouts.admin title="Dashboard" active="dashboard" subtitle="Here's what's happening in your store.">
    <x-slot:actions>
        <a class="btn btn-outline" href="{{ route('admin.orders.index') }}">View Orders</a>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}"><x-icon name="plus" class="icon-sm" /> Add Product</a>
    </x-slot:actions>

    <div class="stat-cards">
        <x-admin.stat-card icon="trending-up" label="Sales today" :value="Money::format($stats->salesToday)"
            :note="trans_choice(':count order|:count orders', $stats->ordersToday)" />
        <x-admin.stat-card icon="cart" color="blue" label="Sales this month" :value="Money::format($stats->salesThisMonth)"
            :note="trans_choice(':count order|:count orders', $stats->ordersThisMonth)" />
        <x-admin.stat-card icon="clock" color="amber" label="Orders waiting" :value="$stats->ordersPerStatus[OrderStatus::Pending->value]" />
        <x-admin.stat-card icon="users" color="rose" label="New customers this month" :value="$stats->newCustomersThisMonth" />
    </div>

    <div class="admin-grid">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Sales Overview</h2>
                    <p class="admin-page-sub">{{ $period->label() }} · {{ Money::format($stats->chartTotal()) }} total</p>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" data-auto-submit>
                    <label for="chart-period" class="visually-hidden">Chart period</label>
                    <select class="select admin-toolbar-select" id="chart-period" name="period">
                        @foreach ($periods as $option)
                            <option value="{{ $option->value }}" @selected($option === $period)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-outline btn-sm" type="submit">Show</button></noscript>
                </form>
            </div>
            <x-admin.sales-chart :days="$stats->salesChart" />
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Orders by Status</h2>
                <a class="section-link" href="{{ route('admin.orders.index') }}">All orders</a>
            </div>
            <ul class="admin-list">
                @foreach (OrderStatus::cases() as $status)
                    <li>
                        <span class="grow"><a href="{{ route('admin.orders.index', ['status' => $status->value]) }}"><x-order.status :status="$status" /></a></span>
                        <span class="end">{{ $stats->ordersPerStatus[$status->value] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="admin-grid">
        <section class="panel panel-flush">
            <div class="panel-head">
                <h2>Recent Orders</h2>
                <a class="section-link" href="{{ route('admin.orders.index') }}">View all</a>
            </div>
            @if ($recentOrders->isEmpty())
                <x-empty-state icon="cart" title="No orders yet" small>New orders will appear here.</x-empty-state>
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Order</th><th>Customer</th><th>Date</th><th class="num">Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td><a class="link" href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a></td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td class="text-light">{{ $order->placed_at->timezone(config('shop.timezone'))->format('d M, h:i A') }}</td>
                                    <td class="num strong"><x-money :amount="$order->total" /></td>
                                    <td><x-order.status :status="$order->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Low Stock ({{ $lowStockCount }})</h2>
                <a class="section-link" href="{{ route('admin.inventory.index', ['tab' => 'low']) }}">Manage</a>
            </div>
            @if ($lowStockVariants->isEmpty())
                <p class="text-muted mb-0">Everything is well stocked.</p>
            @else
                <ul class="admin-list">
                    @foreach ($lowStockVariants as $variant)
                        <li>
                            <img src="{{ $variant->product->coverImage?->url() ?? asset('images/products/placeholder.svg') }}" alt="" width="42" height="42">
                            <div class="grow">
                                <a href="{{ route('admin.inventory.show', $variant) }}"><strong>{{ $variant->product->name }}</strong></a>
                                <span class="table-sub">{{ $variant->sku }}@if ($variant->attributeValues->isNotEmpty()) · {{ $variant->label() }}@endif</span>
                            </div>
                            <span @class(['end', 'stock-out' => $variant->isOutOfStock(), 'stock-low' => ! $variant->isOutOfStock()])>
                                {{ $variant->isOutOfStock() ? 'Out' : $variant->stock_quantity.' left' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.admin>
