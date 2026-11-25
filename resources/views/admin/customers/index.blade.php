{{--
    Admin customer list (F-026). Data from Admin\CustomerController@index:
    $customers     LengthAwarePaginator<User> (role customer)
    $orderStats    user id => CustomerOrderStats
    $statusCounts  'all' and status value => count (search applied)
    $filter        CustomerFilter (search, status)
    $statuses      UserStatus cases
--}}
@use('App\Modules\Account\Enums\UserStatus')

<x-layouts.admin title="Customers" active="customers" :subtitle="trans_choice(':count registered customer.|:count registered customers.', $statusCounts['all'])">
    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="Filter by status">
            <a href="{{ route('admin.customers.index', $filter->queryFor(null)) }}" @class(['active' => $filter->status === null]) @if ($filter->status === null) aria-current="page" @endif>
                All <span class="tab-count">{{ $statusCounts['all'] }}</span>
            </a>
            @foreach ($statuses as $status)
                <a href="{{ route('admin.customers.index', $filter->queryFor($status)) }}" @class(['active' => $filter->status === $status]) @if ($filter->status === $status) aria-current="page" @endif>
                    {{ $status->label() }} <span class="tab-count">{{ $statusCounts[$status->value] }}</span>
                </a>
            @endforeach
        </nav>

        <form class="admin-toolbar" method="GET" action="{{ route('admin.customers.index') }}" role="search">
            @if ($filter->status)
                <input type="hidden" name="status" value="{{ $filter->status->value }}">
            @endif
            <div class="admin-search">
                <x-icon name="search" class="icon-sm" />
                <label for="customer-search" class="visually-hidden">Search customers</label>
                <input class="input" type="search" id="customer-search" name="search" value="{{ $filter->search }}" placeholder="Name, phone or email…">
            </div>
            <div class="toolbar-end">
                <button class="btn btn-outline btn-sm" type="submit"><x-icon name="search" class="icon-sm" /> Search</button>
            </div>
        </form>

        @if ($customers->isEmpty())
            <x-empty-state icon="users" title="No customers here" small>
                @if ($filter->search || $filter->status)
                    <a href="{{ route('admin.customers.index') }}">Show all customers</a>
                @else
                    Customers appear here when they register.
                @endif
            </x-empty-state>
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th class="num">Orders</th>
                            <th class="num">Total Spent</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            @php
                                $stats = $orderStats[$customer->id];
                                $isBlocked = $customer->status === UserStatus::Blocked;
                            @endphp
                            <tr>
                                <td>
                                    <div class="table-person">
                                        <x-avatar :name="$customer->name" />
                                        <div>
                                            <a class="link" href="{{ route('admin.customers.show', $customer->id) }}"><strong>{{ $customer->name }}</strong></a>
                                            <span class="table-sub">{{ $customer->email ?? 'No email' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $customer->phone }}</td>
                                <td class="num">{{ $stats->orderCount }}</td>
                                <td class="num strong"><x-money :amount="$stats->deliveredTotal" /></td>
                                <td class="text-light">{{ $customer->created_at->timezone(config('shop.timezone'))->format('d M Y') }}</td>
                                <td><x-admin.status :status="strtolower($customer->status->label())" /></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn icon-btn-sm" href="{{ route('admin.customers.show', $customer->id) }}" aria-label="View {{ $customer->name }}">
                                            <x-icon name="eye" class="icon-sm" />
                                        </a>
                                        @if ($isBlocked)
                                            <form method="POST" action="{{ route('admin.customers.unblock', $customer->id) }}">
                                                @csrf
                                                <button class="icon-btn icon-btn-sm" type="submit" aria-label="Unblock {{ $customer->name }}">
                                                    <x-icon name="check-circle" class="icon-sm" />
                                                </button>
                                            </form>
                                        @else
                                            <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-block-customer"
                                                data-modal-action="{{ route('admin.customers.block', $customer->id) }}" aria-label="Block {{ $customer->name }}">
                                                <x-icon name="lock" class="icon-sm" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel-foot">
                <span>Showing {{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ $customers->total() }} customers</span>
                {{ $customers->links() }}
            </div>
        @endif
    </section>

    @push('overlays')
        <x-confirm-modal id="modal-block-customer" icon="lock" title="Block this customer?" confirm-label="Yes, Block" method="POST">
            Blocked customers cannot sign in or place new orders, also not as a guest with the same phone. Use this for fake or abusive orders.
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
