<?php

namespace App\Modules\Location\Services;

use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Models\Upazila;
use App\Modules\Location\Repositories\LocationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class LocationService
{
    public function __construct(private readonly LocationRepository $locationRepository) {}

    /**
     * Upazila with its district and division, for address snapshots (F-023).
     */
    public function findUpazilaWithParents(int $upazilaId): ?Upazila
    {
        return $this->locationRepository->findUpazilaWithParents($upazilaId);
    }

    /**
     * @return array<int, string> id => name, for selects
     */
    public function divisionOptions(): array
    {
        return $this->locationRepository->allDivisions()->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string> id => name; empty when no division is chosen
     */
    public function districtOptions(?int $divisionId): array
    {
        $division = $divisionId === null ? null : $this->locationRepository->findDivision($divisionId);

        return $division === null ? [] : $this->locationRepository->districtsOf($division)->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string> id => name; empty when no district is chosen
     */
    public function upazilaOptions(?int $districtId): array
    {
        $district = $districtId === null ? null : $this->locationRepository->findDistrict($districtId);

        return $district === null ? [] : $this->locationRepository->upazilasOf($district)->pluck('name', 'id')->all();
    }

    /**
     * @return Collection<int, District>
     */
    public function districtsOf(Division $division): Collection
    {
        return $this->locationRepository->districtsOf($division);
    }

    /**
     * @return Collection<int, Upazila>
     */
    public function upazilasOf(District $district): Collection
    {
        return $this->locationRepository->upazilasOf($district);
    }

    /**
     * @return Collection<int, Division>
     */
    public function divisionsWithDistrictZones(): Collection
    {
        return $this->locationRepository->divisionsWithDistrictZones();
    }

    public function loadUpazilasWithZones(District $district): District
    {
        return $this->locationRepository->loadUpazilasWithZones($district);
    }

    /**
     * @param  array<int, int>  $zoneIdByDistrictId
     */
    public function assignDistrictZones(array $zoneIdByDistrictId): void
    {
        DB::transaction(fn () => $this->locationRepository->updateDistrictZones($zoneIdByDistrictId));
    }

    /**
     * @param  array<int, int|null>  $zoneIdByUpazilaId  null = same as district
     */
    public function assignUpazilaZones(District $district, array $zoneIdByUpazilaId): void
    {
        DB::transaction(fn () => $this->locationRepository->updateUpazilaZones($district, $zoneIdByUpazilaId));
    }
}
