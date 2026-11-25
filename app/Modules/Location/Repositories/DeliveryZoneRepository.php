<?php

namespace App\Modules\Location\Repositories;

use App\Modules\Location\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Collection;

final class DeliveryZoneRepository
{
    /**
     * @return Collection<int, DeliveryZone>
     */
    public function allWithUsage(): Collection
    {
        return DeliveryZone::query()->ordered()->withCount(['districts', 'upazilas'])->get();
    }

    /**
     * @return Collection<int, DeliveryZone>
     */
    public function activeZones(): Collection
    {
        return DeliveryZone::query()->where('is_active', true)->ordered()->get();
    }

    /**
     * @return array<int, string> id => name
     */
    public function optionsForSelect(): array
    {
        return DeliveryZone::query()->ordered()->pluck('name', 'id')->all();
    }

    public function isInUse(DeliveryZone $zone): bool
    {
        return $zone->districts()->exists() || $zone->upazilas()->exists();
    }

    public function activeCountExcept(DeliveryZone $zone): int
    {
        return DeliveryZone::query()->where('is_active', true)->whereKeyNot($zone->getKey())->count();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): DeliveryZone
    {
        return DeliveryZone::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(DeliveryZone $zone, array $attributes): DeliveryZone
    {
        $zone->update($attributes);

        return $zone;
    }

    public function delete(DeliveryZone $zone): void
    {
        $zone->delete();
    }
}
