<?php

namespace App\Modules\Location\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Location\Exceptions\DeliveryZoneException;
use App\Modules\Location\Http\Requests\Admin\SaveDeliveryZoneRequest;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Services\DeliveryChargeService;
use App\Modules\Location\Services\DeliveryZoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DeliveryZoneController extends Controller
{
    public function __construct(
        private readonly DeliveryZoneService $zoneService,
        private readonly DeliveryChargeService $deliveryChargeService,
    ) {}

    public function index(): View
    {
        return $this->page(editingZone: null);
    }

    public function edit(DeliveryZone $deliveryZone): View
    {
        return $this->page($deliveryZone);
    }

    public function store(SaveDeliveryZoneRequest $request): RedirectResponse
    {
        $zone = $this->zoneService->create($request->toData());

        return to_route('admin.delivery-zones.index')->with('success', __('Zone ":name" created.', ['name' => $zone->name]));
    }

    public function update(SaveDeliveryZoneRequest $request, DeliveryZone $deliveryZone): RedirectResponse
    {
        try {
            $this->zoneService->update($deliveryZone, $request->toData());
        } catch (DeliveryZoneException $exception) {
            return back()->withInput()->withErrors(['is_active' => $exception->getMessage()]);
        }

        return to_route('admin.delivery-zones.index')->with('success', __('Zone ":name" updated.', ['name' => $deliveryZone->name]));
    }

    public function destroy(DeliveryZone $deliveryZone): RedirectResponse
    {
        try {
            $this->zoneService->delete($deliveryZone);
        } catch (DeliveryZoneException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.delivery-zones.index')->with('success', __('Zone ":name" deleted.', ['name' => $deliveryZone->name]));
    }

    private function page(?DeliveryZone $editingZone): View
    {
        return view('admin.delivery.zones', [
            'zones' => $this->zoneService->allWithUsage(),
            'editingZone' => $editingZone,
            'freeDeliveryThreshold' => $this->deliveryChargeService->freeDeliveryThreshold(),
        ]);
    }
}
