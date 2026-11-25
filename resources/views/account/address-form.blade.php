{{--
    Add / edit address (AddressController@create / @edit).
    $address  Address or null
    $labelOptions, $divisionOptions, $districtOptions, $upazilaOptions  id => name (district/upazila follow the selection)
--}}
@php
    $isEditing = $address !== null;
@endphp

<x-layouts.account :title="$isEditing ? 'Edit Address' : 'Add New Address'" active="addresses"
                   :breadcrumbs="['Addresses' => route('account.addresses'), ($isEditing ? 'Edit' : 'New') => null]">
    <div class="panel">
        <form method="POST" action="{{ $isEditing ? route('account.addresses.update', $address) : route('account.addresses.store') }}">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif
            <div class="form-row">
                <x-form.select name="label" id="aa-label" :label="__('Address Label')" :options="$labelOptions" :selected="$address?->label->value ?? 'home'" required />
                <x-form.input name="recipient_name" id="aa-name" :label="__('Full Name')" :placeholder="__('e.g. Rahim Uddin')" maxlength="100"
                              :value="$address?->recipient_name ?? auth()->user()->name" required />
            </div>
            <x-form.input name="phone" id="aa-phone" type="tel" :label="__('Phone Number')" placeholder="01712-345678"
                          :value="$address?->phone ?? auth()->user()->phone" required />

            <x-address-fields id-prefix="aa"
                              :division-options="$divisionOptions" :district-options="$districtOptions" :upazila-options="$upazilaOptions"
                              :division-id="$address?->division_id" :district-id="$address?->district_id" :upazila-id="$address?->upazila_id" />

            <x-form.input name="area" id="aa-area" :label="__('Area')" :placeholder="__('e.g. Dhanmondi 27')" maxlength="100" :value="$address?->area" optional />
            <x-form.textarea name="address_line" id="aa-addr" :label="__('Full Address')" rows="2" :placeholder="__('House number, road, landmark…')" :value="$address?->address_line" required />
            <x-form.input name="postal_code" id="aa-post" :label="__('Postal Code')" placeholder="1205" inputmode="numeric" maxlength="4" :value="$address?->postal_code" optional />

            <div class="form-actions">
                <a class="btn btn-ghost" href="{{ route('account.addresses') }}">{{ __('Cancel') }}</a>
                <button class="btn btn-primary" type="submit">{{ __('Save Address') }}</button>
            </div>
        </form>
    </div>
</x-layouts.account>
