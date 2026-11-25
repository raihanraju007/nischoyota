{{--
    Admin customer list (F-026). Data from Admin\CustomerController@index:
    $customers     LengthAwarePaginator<User> (role customer)
    $orderStats    user id => CustomerOrderStats
    $statusCounts  'all' and status value => count (search applied)
    $filter        CustomerFilter (search, status)
    $statuses      UserStatus cases
--}}
@use('App\Modules\Account\Enums\UserStatus')

<x-layouts.admin :title="__('Customers')" active="customers" :subtitle="trans_choice(':count registered customer.|:count registered customers.', $statusCounts['all'])">
    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="{{ __('Filter by status') }}">
            <a href="{{ route('admin.customers.index', $filter->queryFor(null)) }}" @class(['active' => $filter->status === null]) @if ($filter->status === null) aria-current="page" @endif>
                {{ __('All') }} <span class="tab-count">{{ $statusCounts['all'] }}</span>
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
                <label for="customer-search" class="visually-hidden">{{ __('Search customers') }}</label>
                <input class="input" type="search" id="customer-search" name="search" value="{{ $filter->search }}" placeholder="{{ __('Name, phone or email…') }}">
            </div>
            <div class="toolbar-end">
                <button class="btn btn-outline btn-sm" type="submit"><x-icon name="search" class="icon-sm" /> {{ __('Search') }}</button>
            </div>
        </form>

        @if ($customers->isEmpty())
            <x-empty-state icon="users" :title="__('No customers here')" small>
                @if ($filter->search || $filter->status)
                    <a href="{{ route('admin.customers.index') }}">{{ __('Show all customers') }}</a>
                @else
                    Customers appear here when they register.
                @endif
            </x-empty-state>
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th class="num">{{ __('Orders') }}</th>
                            <th class="num">{{ __('Total Spent') }}</th>
                            <th>{{ __('Joined') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
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
                                            <span class="table-sub">{{ $customer->email ?? __('No email') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $customer->phone }}</td>
                                <td class="num">{{ $stats->orderCount }}</td>
                                <td class="num strong"><x-money :amount="$stats->deliveredTotal" /></td>
                                <td class="text-light">{{ \App\Support\Numerals::date($customer->created_at->timezone(config('shop.timezone')), 'd M Y') }}</td>
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
                <span>{{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($customers->firstItem()), 'last' => \App\Support\Numerals::localize($customers->lastItem()), 'total' => \App\Support\Numerals::localize($customers->total()), 'products' => __('customers')]) }}</span>
                {{ $customers->links() }}
            </div>
        @endif
    </section>

    @push('overlays')
        <x-confirm-modal id="modal-block-customer" icon="lock" :title="__('Block this customer?')" confirm-label="Yes, Block" method="POST">
            {{ __('Blocked customers cannot sign in or place new orders, also not as a guest with the same phone. Use this for fake or abusive orders.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
