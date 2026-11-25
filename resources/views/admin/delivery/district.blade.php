{{--
    Zone override per upazila/thana of one district (DeliveryAreaController@show). Admin only.
    $district     District with division, deliveryZone, upazilas (deliveryZone)
    $zoneOptions  id => zone name
--}}
<x-layouts.admin :title="$district->name.' — Thanas'" active="delivery"
                 :subtitle="$district->division->name.' division · district zone: '.$district->deliveryZone->name"
                 :back-url="route('admin.delivery-areas.index')" back-label="Areas & zones">
    <section class="panel panel-flush">
        <form method="POST" action="{{ route('admin.delivery-areas.update-district', $district) }}">
            @csrf
            @method('PUT')
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>{{ __('Upazila / Thana') }}</th><th>{{ __('Zone') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($district->upazilas as $upazila)
                            <tr>
                                <td><strong>{{ $upazila->name }}</strong> <span class="table-sub">{{ $upazila->name_bn }}</span></td>
                                <td>
                                    <label class="visually-hidden" for="upazila-{{ $upazila->id }}">Zone of {{ $upazila->name }}</label>
                                    <select class="select input-sm" id="upazila-{{ $upazila->id }}" name="upazilas[{{ $upazila->id }}]">
                                        <option value="">Same as district ({{ $district->deliveryZone->name }})</option>
                                        @foreach ($zoneOptions as $zoneId => $zoneName)
                                            <option value="{{ $zoneId }}" @selected((int) old("upazilas.{$upazila->id}", $upazila->delivery_zone_id) === $zoneId)>{{ $zoneName }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="panel-foot">
                <span>{{ trans_choice(':count upazila/thana|:count upazilas/thanas', $district->upazilas->count()) }}</span>
                <button class="btn btn-primary btn-sm" type="submit">{{ __('Save thana zones') }}</button>
            </div>
        </form>
    </section>
</x-layouts.admin>
