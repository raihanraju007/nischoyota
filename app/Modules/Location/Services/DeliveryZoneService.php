<?php

namespace App\Modules\Location\Services;

use App\Modules\Location\Data\DeliveryZoneData;
use App\Modules\Location\Exceptions\DeliveryZoneException;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Repositories\DeliveryZoneRepository;
use Illuminate\Database\Eloquent\Collection;

final class DeliveryZoneService
{
    public function __construct(private readonly DeliveryZoneRepository $zoneRepository) {}

    /**
     * @return Collection<int, DeliveryZone>
     */
    public function allWithUsage(): Collection
    {
        return $this->zoneRepository->allWithUsage();
    }

    /**
     * @return Collection<int, DeliveryZone>
     */
    public function activeZones(): Collection
    {
        return $this->zoneRepository->activeZones();
    }

    /**
     * @return array<int, string>
     */
    public function optionsForSelect(): array
    {
        return $this->zoneRepository->optionsForSelect();
    }

    public function create(DeliveryZoneData $data): DeliveryZone
    {
        return $this->zoneRepository->create($this->attributesFrom($data));
    }

    /**
     * @throws DeliveryZoneException
     */
    public function update(DeliveryZone $zone, DeliveryZoneData $data): DeliveryZone
    {
        if (! $data->isActive && $this->zoneRepository->activeCountExcept($zone) === 0) {
            throw DeliveryZoneException::lastActiveZone();
        }

        return $this->zoneRepository->update($zone, $this->attributesFrom($data));
    }

    /**
     * @throws DeliveryZoneException
     */
    public function delete(DeliveryZone $zone): void
    {
        if ($this->zoneRepository->isInUse($zone)) {
            throw DeliveryZoneException::inUse();
        }

        if ($zone->is_active && $this->zoneRepository->activeCountExcept($zone) === 0) {
            throw DeliveryZoneException::lastActiveZone();
        }

        $this->zoneRepository->delete($zone);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(DeliveryZoneData $data): array
    {
        return [
            'name' => $data->name,
            'charge' => $data->charge,
            'min_days' => $data->minDays,
            'max_days' => $data->maxDays,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
        ];
    }
}
