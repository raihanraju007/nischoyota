<?php

namespace App\Modules\Location\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Services\LocationService;
use Illuminate\Http\JsonResponse;

/**
 * Public JSON for the Division → District → Upazila dropdowns.
 */
final class LocationController extends Controller
{
    public function __construct(private readonly LocationService $locationService) {}

    public function districts(Division $division): JsonResponse
    {
        return response()->json($this->locationService->districtsOf($division));
    }

    public function upazilas(District $district): JsonResponse
    {
        return response()->json($this->locationService->upazilasOf($district));
    }
}
