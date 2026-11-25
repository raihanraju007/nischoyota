<?php

namespace App\Modules\Order\Data;

/**
 * The delivery address as it is copied onto the order (place names included, D6).
 */
final readonly class ShippingAddress
{
    public function __construct(
        public int $divisionId,
        public string $divisionName,
        public int $districtId,
        public string $districtName,
        public int $upazilaId,
        public string $upazilaName,
        public ?string $area,
        public string $addressLine,
        public ?string $postalCode,
    ) {}
}
