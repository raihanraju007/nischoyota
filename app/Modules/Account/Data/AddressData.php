<?php

namespace App\Modules\Account\Data;

use App\Modules\Account\Enums\AddressLabel;

final readonly class AddressData
{
    public function __construct(
        public AddressLabel $label,
        public string $recipientName,
        public string $phone,
        public int $divisionId,
        public int $districtId,
        public int $upazilaId,
        public ?string $area,
        public string $addressLine,
        public ?string $postalCode,
    ) {}
}
