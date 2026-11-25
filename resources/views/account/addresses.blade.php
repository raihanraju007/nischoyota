{{--
    Address book (AddressController@index).
    $addresses   Address collection (default first) with upazila, district
    $canAddMore  false at the 10-address limit
--}}
<x-layouts.account :title="__('My Addresses')" active="addresses" :breadcrumbs="['Addresses' => null]">
    <div class="grid-addresses">
        @foreach ($addresses as $address)
            <div @class(['address-card', 'default' => $address->is_default])>
                <div class="address-type">
                    <x-icon name="map-pin" class="icon-sm" />
                    <h3>{{ $address->label->label() }}</h3>
                    @if ($address->is_default)
                        <span class="badge badge-stock">{{ __('Default') }}</span>
                    @endif
                </div>
                <address>
                    {{ $address->recipient_name }}<br>
                    @foreach ($address->displayLines() as $line)
                        {{ $line }}<br>
                    @endforeach
                    {{ $address->phone }}
                </address>
                <div class="address-actions">
                    <a class="btn btn-sm btn-outline" href="{{ route('account.addresses.edit', $address) }}">{{ __('Edit') }}</a>
                    <button class="btn btn-sm btn-danger-soft" type="button" data-modal="modal-delete-address"
                            data-modal-action="{{ route('account.addresses.destroy', $address) }}">{{ __('Delete') }}</button>
                    @unless ($address->is_default)
                        <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                            @csrf
                            <button class="btn btn-sm btn-ghost" type="submit">{{ __('Set Default') }}</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach

        @if ($canAddMore)
            <a class="add-address-card" href="{{ route('account.addresses.create') }}">
                <x-icon name="plus" class="icon" />
                {{ __('Add New Address') }}
            </a>
        @endif
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-address" :title="__('Delete this address?')" method="DELETE">
            {{ __('This address will be permanently removed from your account.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.account>
