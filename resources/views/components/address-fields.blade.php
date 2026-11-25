{{--
    Division → District → Upazila/Thana selects (account address form, checkout).
    Without JS the options come from the server for the current selection; with JS (storefront.js,
    data-location-*) changing a select loads the next list from the /locations JSON endpoints.

    Props: idPrefix, divisionOptions / districtOptions / upazilaOptions (id => name), divisionId, districtId, upazilaId
--}}
@props([
    'idPrefix' => 'addr',
    'divisionOptions' => [],
    'districtOptions' => [],
    'upazilaOptions' => [],
    'divisionId' => null,
    'districtId' => null,
    'upazilaId' => null,
])

<div class="form-row" data-location-fields
     data-districts-url="{{ route('locations.districts', '__ID__') }}"
     data-upazilas-url="{{ route('locations.upazilas', '__ID__') }}">
    <x-form.select name="division_id" :id="$idPrefix.'-division'" :label="__('Division')" :placeholder="__('Choose division')"
                   :options="$divisionOptions" :selected="$divisionId" data-location-division required />
    <x-form.select name="district_id" :id="$idPrefix.'-district'" :label="__('District')" :placeholder="__('Choose district')"
                   :options="$districtOptions" :selected="$districtId" data-location-district required />
</div>
<x-form.select name="upazila_id" :id="$idPrefix.'-upazila'" :label="__('Upazila / Thana')" :placeholder="__('Choose upazila / thana')"
               :options="$upazilaOptions" :selected="$upazilaId" data-location-upazila required />
