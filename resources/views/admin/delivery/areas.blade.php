{{--
    Zone of every district, grouped by division (DeliveryAreaController@index). Admin only.
    $divisions    Division collection with districts (deliveryZone, upazilas_count)
    $zoneOptions  id => zone name
--}}
<x-layouts.admin title="Areas & Zones" active="delivery" subtitle="Choose the delivery zone of each district. Open a district to set a different zone for single thanas."
                 :back-url="route('admin.delivery-zones.index')" back-label="Delivery zones">
    <form method="POST" action="{{ route('admin.delivery-areas.update') }}">
        @csrf
        @method('PUT')
        <div class="area-divisions">
            @foreach ($divisions as $division)
                <section class="panel panel-flush">
                    <div class="panel-head variants-head">
                        <h2 class="panel-title mb-0">{{ $division->name }} <span class="text-muted">{{ $division->name_bn }}</span></h2>
                    </div>
                    <div class="order-table-wrap">
                        <table class="admin-table">
                            <tbody>
                                @foreach ($division->districts as $district)
                                    <tr>
                                        <td>
                                            <a class="link" href="{{ route('admin.delivery-areas.show', $district) }}"><strong>{{ $district->name }}</strong></a>
                                            <span class="table-sub">{{ $district->name_bn }} · {{ trans_choice(':count thana|:count thanas', $district->upazilas_count) }}</span>
                                        </td>
                                        <td>
                                            <label class="visually-hidden" for="district-{{ $district->id }}">Zone of {{ $district->name }}</label>
                                            <select class="select input-sm" id="district-{{ $district->id }}" name="districts[{{ $district->id }}]">
                                                @foreach ($zoneOptions as $zoneId => $zoneName)
                                                    <option value="{{ $zoneId }}" @selected((int) old("districts.{$district->id}", $district->delivery_zone_id) === $zoneId)>{{ $zoneName }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
        <div class="form-actions sticky-actions">
            <button class="btn btn-primary" type="submit">Save district zones</button>
        </div>
    </form>
</x-layouts.admin>
