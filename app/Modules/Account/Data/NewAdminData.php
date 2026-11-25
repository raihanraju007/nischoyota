<?php

namespace App\Modules\Account\Data;

final readonly class NewAdminData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public string $password,
    ) {}
}
