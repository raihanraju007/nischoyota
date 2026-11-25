<?php

namespace App\Modules\Content\Data;

/**
 * Current store settings. Money in paisa; $freeDeliveryThreshold null = never free.
 */
final readonly class StoreSettings
{
    public function __construct(
        public string $storeName,
        public string $supportPhone,
        public string $supportEmail,
        public ?string $storeAddress,
        public ?string $supportHours,
        public ?int $freeDeliveryThreshold,
        public int $lowStockThreshold,
    ) {}
}
