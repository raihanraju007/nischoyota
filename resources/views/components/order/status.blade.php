{{--
    Order status pill. status: an OrderStatus, or a string on prototype pages
    (pending | confirmed | processing | shipped | delivered | cancelled | returned).
--}}
@props(['status'])

@php
    $isEnum = $status instanceof \App\Modules\Order\Enums\OrderStatus;
    $value = $isEnum ? $status->value : $status;
@endphp

<span class="status status-{{ $value }}">{{ $isEnum ? $status->label() : ucfirst($status) }}</span>
