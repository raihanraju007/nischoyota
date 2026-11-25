<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Enums\AddressLabel;
use App\Modules\Account\Exceptions\AddressLimitReachedException;
use App\Modules\Account\Http\Requests\SaveAddressRequest;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Services\AddressService;
use App\Modules\Location\Services\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The customer's address book. Ownership is checked by AddressPolicy on the routes (404 for others).
 */
final class AddressController extends Controller
{
    public function __construct(
        private readonly AddressService $addressService,
        private readonly LocationService $locationService,
    ) {}

    public function index(Request $request): View
    {
        $addresses = $this->addressService->forUser($request->user());

        return view('account.addresses', [
            'addresses' => $addresses,
            'canAddMore' => $addresses->count() < AddressService::MAX_ADDRESSES,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, address: null);
    }

    public function edit(Request $request, Address $address): View
    {
        return $this->form($request, $address);
    }

    public function store(SaveAddressRequest $request): RedirectResponse
    {
        try {
            $this->addressService->create($request->user(), $request->toData());
        } catch (AddressLimitReachedException $exception) {
            return to_route('account.addresses')->with('error', $exception->getMessage());
        }

        return to_route('account.addresses')->with('success', __('Address saved.'));
    }

    public function update(SaveAddressRequest $request, Address $address): RedirectResponse
    {
        $this->addressService->update($address, $request->toData());

        return to_route('account.addresses')->with('success', __('Address updated.'));
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->addressService->delete($request->user(), $address);

        return to_route('account.addresses')->with('success', __('Address deleted.'));
    }

    public function makeDefault(Request $request, Address $address): RedirectResponse
    {
        $this->addressService->makeDefault($request->user(), $address);

        return to_route('account.addresses')->with('success', __('Default address updated.'));
    }

    /**
     * District/upazila options follow the chosen (or previously entered) division/district, so the form works without JS.
     */
    private function form(Request $request, ?Address $address): View
    {
        $divisionId = $request->old('division_id', $address?->division_id);
        $districtId = $request->old('district_id', $address?->district_id);

        return view('account.address-form', [
            'address' => $address,
            'labelOptions' => collect(AddressLabel::cases())->mapWithKeys(fn (AddressLabel $label): array => [$label->value => $label->label()])->all(),
            'divisionOptions' => $this->locationService->divisionOptions(),
            'districtOptions' => $this->locationService->districtOptions($divisionId === null ? null : (int) $divisionId),
            'upazilaOptions' => $this->locationService->upazilaOptions($districtId === null ? null : (int) $districtId),
        ]);
    }
}
