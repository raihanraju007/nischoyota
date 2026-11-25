{{--
    Create / edit a coupon (CouponController@create / @edit). Admin only.
    $coupon       Coupon or null
    $typeOptions  value => label
    $timezone     Store timezone for the date fields
--}}
@use('App\Modules\Promotion\Enums\CouponType')
@use('App\Support\Money')

@php
    $isEditing = $coupon !== null;
    $isFixed = $coupon === null || $coupon->type === CouponType::Fixed;
    $takaValue = fn (?int $paisa) => $paisa === null ? null : Money::toTakaInput($paisa);
    $storeDate = fn ($moment) => $moment?->timezone($timezone)->format('Y-m-d');
@endphp

<x-layouts.admin :title="$isEditing ? __('Edit Coupon :code', ['code' => $coupon->code]) : __('Create Coupon')" active="coupons"
                 subtitle="Discount codes customers enter at checkout." :back-url="route('admin.coupons.index')" back-label="Coupons">
    <form method="POST" action="{{ $isEditing ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="admin-grid">
            <div>
                <section class="panel">
                    <h2 class="panel-title">{{ __('Discount') }}</h2>
                    <x-form.input name="code" id="coupon-code" :label="__('Coupon Code')" :placeholder="__('SAVE120')" maxlength="30" :value="$coupon?->code" required
                                  hint="Capital letters, numbers and dashes. Customers can type it in any case." />
                    <div class="form-row">
                        <x-form.select name="type" id="coupon-type" :label="__('Type')" :options="$typeOptions" :selected="$coupon?->type->value ?? 'fixed'" required />
                        <x-form.input name="value" id="coupon-value" type="number" min="0" step="0.01" :label="__('Value')" placeholder="120"
                                      :value="$coupon ? ($isFixed ? $takaValue($coupon->value) : $coupon->value) : null" required
                                      hint="Taka for a fixed amount; 1–100 for a percentage." />
                    </div>
                    <div class="form-row">
                        @include('admin.products.partials.money-input', ['name' => 'min_order_amount', 'label' => 'Min. Order', 'value' => $takaValue($coupon?->min_order_amount)])
                        @include('admin.products.partials.money-input', ['name' => 'max_discount_amount', 'label' => __('Max. Discount'), 'value' => $takaValue($coupon?->max_discount_amount), 'hint' => 'Only for percentage coupons.'])
                    </div>
                </section>

                <section class="panel">
                    <h2 class="panel-title">{{ __('Limits & Dates') }}</h2>
                    <div class="form-row">
                        <x-form.input name="usage_limit" id="coupon-limit" type="number" min="1" :label="__('Total Uses')" :value="$coupon?->usage_limit" optional hint="Empty = unlimited." />
                        <x-form.input name="usage_limit_per_user" id="coupon-limit-user" type="number" min="1" :label="__('Uses per Customer')" :value="$coupon?->usage_limit_per_user" optional hint="Empty = unlimited." />
                    </div>
                    <div class="form-row">
                        <x-form.input name="starts_at" id="coupon-starts" type="date" :label="__('Start Date')" :value="$storeDate($coupon?->starts_at)" optional />
                        <x-form.input name="expires_at" id="coupon-expires" type="date" :label="__('End Date')" :value="$storeDate($coupon?->expires_at)" optional hint="Works until the end of this day (Bangladesh time)." />
                    </div>
                </section>
            </div>

            <div>
                <section class="panel">
                    <h2 class="panel-title">{{ __('Publish') }}</h2>
                    <label class="switch">
                        <span class="switch-text"><strong>{{ __('Active') }}</strong><span>{{ __('Customers can use this code') }}</span></span>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon?->is_active ?? true))>
                    </label>
                    @if ($isEditing)
                        <p class="form-hint mb-0">Used {{ trans_choice(':count time|:count times', $coupon->used_count) }} so far.</p>
                    @endif
                </section>
                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('admin.coupons.index') }}">{{ __('Cancel') }}</a>
                    <button class="btn btn-primary" type="submit">{{ $isEditing ? __('Update Coupon') : __('Save Coupon') }}</button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
