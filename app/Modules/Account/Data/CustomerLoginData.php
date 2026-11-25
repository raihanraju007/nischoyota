<?php

namespace App\Modules\Account\Data;

final readonly class CustomerLoginData
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $shouldRemember,
        public ?string $ipAddress,
    ) {}
}
