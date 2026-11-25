<?php

namespace App\Modules\Account\Data;

final readonly class AdminLoginData
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $shouldRemember,
    ) {}
}
