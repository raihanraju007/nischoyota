<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Shows digits as Bangla numerals (০-৯) while the page language is Bangla. Display only: stored values,
 * form inputs and URLs always stay ASCII.
 */
final class Numerals
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    private const ASCII_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function localize(int|float|string $value): string
    {
        $text = (string) $value;

        if (Locale::current() !== Locale::Bangla) {
            return $text;
        }

        return str_replace(self::ASCII_DIGITS, self::BANGLA_DIGITS, $text);
    }

    /**
     * Turns Bangla digits typed into a form back into ASCII so validation and storage see 0-9.
     */
    public static function toAscii(string $value): string
    {
        return str_replace(self::BANGLA_DIGITS, self::ASCII_DIGITS, $value);
    }

    /**
     * Date for display, with Bangla month names and digits when the page is in Bangla.
     */
    public static function date(\DateTimeInterface $date, string $format = 'd M Y'): string
    {
        return self::localize(Carbon::instance($date)->locale(app()->getLocale())->translatedFormat($format));
    }
}
