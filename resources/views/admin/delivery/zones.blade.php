{{--
    Delivery zones list + add/edit form (DeliveryZoneController). Admin only.
    $zones                  DeliveryZone collection with districts_count, upazilas_count
    $editingZone            DeliveryZone being edited, or null
    $freeDeliveryThreshold  Paisa, or null for no free delivery
--}}
@use('App\Support\Money')

@php
    $isEditing = $editingZone !== null;
@endphp

<x-layouts.admin title="Delivery Zones" active="delivery" subtitle="Delivery charge and time for each area of Bangladesh.">
    <x-slot:actions>
        <a class="btn btn-outline" href="{{ route('admin.delivery-areas.index') }}"><x-icon name="map-pin" class="icon-sm" /> Areas &amp; zones</a>
    </x-slot:actions>

    <div class="admin-grid">
        <section class="panel panel-flush">
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th class="num">Charge</th>
                            <th>Delivery time</th>
                            <th class="num">Areas</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zones as $zone)
                            <tr>
                                <td class="strong">{{ $zone->name }}</td>
                                <td class="num"><x-money :amount="$zone->charge" /></td>
                                <td>{{ $zone->deliveryTimeLabel() }}</td>
                                <td class="num">
                                    {{ trans_choice(':count district|:count districts', $zone->districts_count) }}
                                    @if ($zone->upazilas_count)<span class="table-sub">+ {{ trans_choice(':count thana|:count thanas', $zone->upazilas_count) }}</span>@endif
                                </td>
                                <td><x-admin.status :status="$zone->is_active ? 'active' : 'hidden'" /></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn icon-btn-sm" href="{{ route('admin.delivery-zones.edit', $zone) }}" aria-label="Edit {{ $zone->name }}">
                                            <x-icon name="edit" class="icon-sm" />
                                        </a>
                                        <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-zone"
                                                data-modal-action="{{ route('admin.delivery-zones.destroy', $zone) }}" aria-label="Delete {{ $zone->name }}">
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
                <span>
                    @if ($freeDeliveryThreshold === null)
                        No free delivery.
                    @else
                        Free delivery on orders of <x-money :amount="$freeDeliveryThreshold" /> or more.
                    @endif
                    <a href="{{ route('admin.settings') }}">Change in Settings</a>
                </span>
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">{{ $isEditing ? 'Edit Zone' : 'Add Zone' }}</h2>
            <form method="POST" action="{{ $isEditing ? route('admin.delivery-zones.update', $editingZone) : route('admin.delivery-zones.store') }}">
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif
                <x-form.input name="name" id="zone-name" label="Name" placeholder="e.g. Inside Dhaka" :value="$editingZone?->name" required />
                @include('admin.products.partials.money-input', ['name' => 'charge', 'label' => 'Delivery Charge', 'value' => $editingZone ? Money::toTakaInput($editingZone->charge) : null, 'required' => true])
                <div class="form-row">
                    <x-form.input name="min_days" id="zone-min" type="number" min="1" max="30" label="Earliest (days)" :value="$editingZone?->min_days ?? 1" required />
                    <x-form.input name="max_days" id="zone-max" type="number" min="1" max="30" label="Latest (days)" :value="$editingZone?->max_days ?? 3" required />
                </div>
                <x-form.input name="sort_order" id="zone-sort" type="number" min="0" label="Sort Order" :value="$editingZone?->sort_order ?? $zones->count()" />
                <label class="switch form-group">
                    <span class="switch-text"><strong>Active</strong><span>Inactive zones cannot receive orders</span></span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingZone?->is_active ?? true))>
                </label>
                @error('is_active')<p class="form-error">{{ $message }}</p>@enderror
                <button class="btn btn-primary btn-block" type="submit">{{ $isEditing ? 'Update Zone' : 'Save Zone' }}</button>
                @if ($isEditing)
                    <a class="btn btn-ghost btn-block" href="{{ route('admin.delivery-zones.index') }}">Cancel</a>
                @endif
            </form>
        </section>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-zone" title="Delete this zone?" confirm-label="Yes, Delete" method="DELETE">
            A zone that districts or thanas still use cannot be deleted. Move them to another zone first.
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
