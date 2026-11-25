<?php

namespace App\Modules\Account\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Blocked => __('Blocked'),
        };
    }

    public function canSignIn(): bool
    {
        return $this === self::Active;
    }
}
