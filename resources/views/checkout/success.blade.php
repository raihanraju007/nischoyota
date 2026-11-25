{{-- Order placed (F-023). $order: the order placed in this session. --}}
@php
    $orderDetails = [
        'Order Number' => '#'.$order->order_number,
        'Order Date' => \App\Support\Numerals::date($order->placed_at->timezone(config('shop.timezone'))),
        'Payment Method' => $order->payment_method->label(),
        'Order Total' => \App\Support\Money::format($order->total),
        'Estimated Delivery' => $order->deliveryTimeLabel(),
    ];
@endphp

<x-layouts.app :title="__('Order Placed Successfully')" main-class="order-success">
    <div class="success-wrap">
        <div class="success-icon">
            <x-icon name="check" stroke-width="2.5" />
        </div>
        <h1>{{ __('Order Placed Successfully!') }}</h1>
        <p>{{ __('Thank you for shopping with us. We\'ve received your order and will call you shortly to confirm.') }}</p>

        <ul class="success-details">
            @foreach ($orderDetails as $label => $value)
                <li><span class="sd-label">{{ $label }}</span><span class="sd-value">{{ $value }}</span></li>
            @endforeach
        </ul>

        <div class="success-actions">
            <a class="btn btn-primary" href="{{ route('orders.track') }}">{{ __('Track Order') }}</a>
            <a class="btn btn-outline" href="{{ route('shop') }}">{{ __('Continue Shopping') }}</a>
        </div>
    </div>
</x-layouts.app>
