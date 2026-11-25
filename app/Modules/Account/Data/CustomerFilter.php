<?php

namespace App\Modules\Account\Data;

use App\Modules\Account\Enums\UserStatus;

/**
 * Admin customer list filter (F-026). A null status shows all customers.
 */
final readonly class CustomerFilter
{
    public function __construct(
        public ?string $search,
        public ?UserStatus $status,
    ) {}

    /**
     * @return array<string, string>
     */
    public function queryFor(?UserStatus $status): array
    {
        return array_filter(['search' => $this->search, 'status' => $status?->value]);
    }
}
