<?php

namespace App\Support;

use Illuminate\Container\Container;

/**
 * Languages the storefront can be shown in. Bangla is the default (config/app.php).
 */
enum Locale: string
{
    case Bangla = 'bn';
    case English = 'en';

    public const COOKIE_NAME = 'locale';

    public function nativeName(): string
    {
        return match ($this) {
            self::Bangla => 'বাংলা',
            self::English => 'English',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Bangla => 'বাংলা',
            self::English => 'EN',
        };
    }

    public static function current(): self
    {
        $container = Container::getInstance();

        if (! $container->bound('translator')) {
            return self::English;
        }

        return self::tryFrom($container->make('translator')->getLocale()) ?? self::English;
    }

    public function other(): self
    {
        return match ($this) {
            self::Bangla => self::English,
            self::English => self::Bangla,
        };
    }
}
