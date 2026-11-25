<?php

namespace App\Modules\Account\Enums;

enum AddressLabel: string
{
    case Home = 'home';
    case Office = 'office';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Home => __('Home'),
            self::Office => __('Office'),
            self::Other => __('Other'),
        };
    }
}
