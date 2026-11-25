<?php

namespace App\Modules\Location\Data;

final readonly class DeliveryZoneData
{
    public function __construct(
        public string $name,
        public int $charge,
        public int $minDays,
        public int $maxDays,
        public bool $isActive,
        public int $sortOrder,
    ) {}
}
