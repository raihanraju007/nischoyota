{{--
    Customer orders table (F-024).
    orders: iterable of Order with item_count (withSum of item quantities)
--}}
@props(['orders'])

<div class="order-table-wrap">
    <table class="order-table">
        <thead>
            <tr><th>{{ __('Order #') }}</th><th>{{ __('Date') }}</th><th>{{ __('Items') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td class="order-id">#{{ $order->order_number }}</td>
                    <td>{{ \App\Support\Numerals::date($order->placed_at->timezone(config('shop.timezone'))) }}</td>
                    <td>{{ trans_choice('{1} :count item|[2,*] :count items', (int) $order->item_count) }}</td>
                    <td><x-money :amount="$order->total" /></td>
                    <td><x-order.status :status="$order->status" /></td>
                    <td><a class="btn btn-sm btn-outline" href="{{ route('account.orders.show', $order->order_number) }}">{{ __('View') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
