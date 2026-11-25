<?php

namespace App\Modules\Location\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Location\Http\Requests\Admin\UpdateDistrictZonesRequest;
use App\Modules\Location\Http\Requests\Admin\UpdateUpazilaZonesRequest;
use App\Modules\Location\Models\District;
use App\Modules\Location\Services\DeliveryZoneService;
use App\Modules\Location\Services\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DeliveryAreaController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly DeliveryZoneService $zoneService,
    ) {}

    public function index(): View
    {
        return view('admin.delivery.areas', [
            'divisions' => $this->locationService->divisionsWithDistrictZones(),
            'zoneOptions' => $this->zoneService->optionsForSelect(),
        ]);
    }

    public function update(UpdateDistrictZonesRequest $request): RedirectResponse
    {
        $this->locationService->assignDistrictZones($request->zoneIdByDistrictId());

        return to_route('admin.delivery-areas.index')->with('success', __('District zones saved.'));
    }

    public function show(District $district): View
    {
        return view('admin.delivery.district', [
            'district' => $this->locationService->loadUpazilasWithZones($district),
            'zoneOptions' => $this->zoneService->optionsForSelect(),
        ]);
    }

    public function updateDistrict(UpdateUpazilaZonesRequest $request, District $district): RedirectResponse
    {
        $this->locationService->assignUpazilaZones($district, $request->zoneIdByUpazilaId());

        return to_route('admin.delivery-areas.show', $district)->with('success', __(':district zones saved.', ['district' => $district->name]));
    }
}
