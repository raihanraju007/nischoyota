<?php

namespace App\Modules\Account\Data;

final readonly class RegistrationData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public string $password,
        public ?string $ipAddress,
    ) {}
}
