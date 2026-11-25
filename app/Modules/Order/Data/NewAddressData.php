<?php

namespace App\Modules\Order\Data;

/**
 * An address typed on the checkout page (validated: the district is in the division, the upazila in the district).
 */
final readonly class NewAddressData
{
    public function __construct(
        public int $divisionId,
        public int $districtId,
        public int $upazilaId,
        public ?string $area,
        public string $addressLine,
        public ?string $postalCode,
    ) {}
}
