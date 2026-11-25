<?php

namespace App\Modules\Location\Exceptions;

use RuntimeException;

final class DeliveryZoneException extends RuntimeException
{
    public static function inUse(): self
    {
        return new self(__('This zone is used by districts or thanas. Move them to another zone first.'));
    }

    public static function lastActiveZone(): self
    {
        return new self(__('At least one delivery zone must stay active.'));
    }
}
