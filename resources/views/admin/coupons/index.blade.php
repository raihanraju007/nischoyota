{{--
    Coupon list (CouponController@index). Admin only.
    $coupons  LengthAwarePaginator<Coupon>
    $now      Current time, for the status
--}}
@php
    $timezone = config('shop.timezone');
@endphp

<x-layouts.admin :title="__('Coupons')" active="coupons" :subtitle="__('Discount codes customers enter at checkout.')">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('admin.coupons.create') }}"><x-icon name="plus" class="icon-sm" /> {{ __('Create Coupon') }}</a>
    </x-slot:actions>

    <section class="panel panel-flush">
        @if ($coupons->isEmpty())
            <x-empty-state icon="ticket" :title="__('No coupons yet')" small :action-url="route('admin.coupons.create')" action-label="Create Coupon">
                {{ __('Create a code such as SAVE120 for customers to use at checkout.') }}
            </x-empty-state>
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th class="num">{{ __('Min. Order') }}</th>
                            <th class="num">{{ __('Used') }}</th>
                            <th>{{ __('Valid') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            @php($status = $coupon->statusAt($now))
                            <tr>
                                <td class="strong">{{ $coupon->code }}</td>
                                <td>
                                    {{ $coupon->discountLabel() }}
                                    @if ($coupon->usage_limit_per_user)
                                        <span class="table-sub">{{ trans_choice(':count use per customer|:count uses per customer', $coupon->usage_limit_per_user) }}</span>
                                    @endif
                                </td>
                                <td class="num">@if ($coupon->min_order_amount)<x-money :amount="$coupon->min_order_amount" />@else — @endif</td>
                                <td class="num">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                                <td class="text-light">
                                    @if ($coupon->starts_at)
                                        {{ __('From :date', ['date' => \App\Support\Numerals::date($coupon->starts_at->timezone($timezone), 'd M Y')]) }}<br>
                                    @endif
                                    {{ $coupon->expires_at ? __('Until :date', ['date' => \App\Support\Numerals::date($coupon->expires_at->timezone($timezone))]) : __('No end date') }}
                                </td>
                                <td><x-admin.status :status="$status->value" /></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn icon-btn-sm" href="{{ route('admin.coupons.edit', $coupon) }}" aria-label="Edit {{ $coupon->code }}">
                                            <x-icon name="edit" class="icon-sm" />
                                        </a>
                                        <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-coupon"
                                                data-modal-action="{{ route('admin.coupons.destroy', $coupon) }}" aria-label="Delete {{ $coupon->code }}">
                                            <x-icon name="trash" class="icon-sm" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="panel-foot">
                <span>{{ trans_choice(':count coupon|:count coupons', $coupons->total()) }}</span>
                {{ $coupons->links() }}
            </div>
        @endif
    </section>

    @push('overlays')
        <x-confirm-modal id="modal-delete-coupon" :title="__('Delete this coupon?')" confirm-label="Yes, Delete" method="DELETE">
            {{ __('Customers will no longer be able to use this code. Past orders keep their discount.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
