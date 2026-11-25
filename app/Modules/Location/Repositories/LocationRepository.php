<?php

namespace App\Modules\Location\Repositories;

use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Models\Upazila;
use Illuminate\Database\Eloquent\Collection;

final class LocationRepository
{
    public function findDivision(int $divisionId): ?Division
    {
        return Division::query()->find($divisionId);
    }

    public function findDistrict(int $districtId): ?District
    {
        return District::query()->find($districtId);
    }

    /**
     * @return Collection<int, Division>
     */
    public function allDivisions(): Collection
    {
        return Division::query()->orderBy('name')->get(['id', 'name', 'name_bn']);
    }

    /**
     * @return Collection<int, District>
     */
    public function districtsOf(Division $division): Collection
    {
        return $division->districts()->orderBy('name')->get(['id', 'name', 'name_bn']);
    }

    /**
     * @return Collection<int, Upazila>
     */
    public function upazilasOf(District $district): Collection
    {
        return $district->upazilas()->orderBy('name')->get(['id', 'name', 'name_bn']);
    }

    public function findUpazilaWithParents(int $upazilaId): ?Upazila
    {
        return Upazila::query()->with('district.division')->find($upazilaId);
    }

    public function findUpazilaWithZones(int $upazilaId): ?Upazila
    {
        return Upazila::query()->with(['deliveryZone', 'district.deliveryZone'])->find($upazilaId);
    }

    /**
     * @return Collection<int, Division>
     */
    public function divisionsWithDistrictZones(): Collection
    {
        return Division::query()
            ->orderBy('name')
            ->with(['districts' => fn ($query) => $query->orderBy('name')->with('deliveryZone')->withCount('upazilas')])
            ->get();
    }

    public function loadUpazilasWithZones(District $district): District
    {
        return $district->load(['division', 'deliveryZone', 'upazilas' => fn ($query) => $query->orderBy('name')->with('deliveryZone')]);
    }

    /**
     * @param  array<int, int>  $zoneIdByDistrictId
     */
    public function updateDistrictZones(array $zoneIdByDistrictId): void
    {
        foreach ($zoneIdByDistrictId as $districtId => $zoneId) {
            District::query()->whereKey($districtId)->update(['delivery_zone_id' => $zoneId]);
        }
    }

    /**
     * @param  array<int, int|null>  $zoneIdByUpazilaId  null = same as district
     */
    public function updateUpazilaZones(District $district, array $zoneIdByUpazilaId): void
    {
        foreach ($zoneIdByUpazilaId as $upazilaId => $zoneId) {
            $district->upazilas()->whereKey($upazilaId)->update(['delivery_zone_id' => $zoneId]);
        }
    }
}
