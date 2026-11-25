<?php

namespace App\Modules\Location\Services;

use App\Modules\Content\Services\SettingService;
use App\Modules\Location\Data\DeliveryQuote;
use App\Modules\Location\Repositories\LocationRepository;

/**
 * The single place that prices delivery. Phase 2 (per-vendor shipments) extends this service.
 */
final class DeliveryChargeService
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly SettingService $settingService,
    ) {}

    /**
     * Zone = the upazila's override, else its district's zone. Null when the area is unknown or its zone is inactive.
     *
     * @param  int  $subtotal  Order subtotal in paisa (decides free delivery)
     */
    public function quoteFor(int $upazilaId, int $subtotal): ?DeliveryQuote
    {
        $upazila = $this->locationRepository->findUpazilaWithZones($upazilaId);
        $zone = $upazila?->deliveryZone ?? $upazila?->district->deliveryZone;

        if ($zone === null || ! $zone->is_active) {
            return null;
        }

        $threshold = $this->freeDeliveryThreshold();
        $isFree = $threshold !== null && $subtotal >= $threshold;

        return new DeliveryQuote(
            deliveryZoneId: $zone->id,
            zoneName: $zone->name,
            charge: $isFree ? 0 : $zone->charge,
            isFree: $isFree,
            minDays: $zone->min_days,
            maxDays: $zone->max_days,
        );
    }

    /**
     * Paisa; null = no free delivery. Store setting (F-010); config/shop.php is the default.
     */
    public function freeDeliveryThreshold(): ?int
    {
        return $this->settingService->current()->freeDeliveryThreshold;
    }
}
