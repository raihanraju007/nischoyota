{{--
    Status pill for admin records (products, customers, reviews, coupons, payments).
    Order statuses use <x-order.status> instead.

    Usage: <x-admin.status status="active" />
--}}
@props(['status'])

@php
    $tone = match ($status) {
        'active', 'approved', 'paid', 'in stock', 'replied' => 'success',
        'pending', 'low stock', 'unpaid', 'scheduled', 'new' => 'warning',
        'blocked', 'rejected', 'out of stock', 'expired', 'used up' => 'danger',
        default => 'muted',
    };
@endphp

<span class="status status-{{ $tone }}">{{ ucfirst($status) }}</span>
