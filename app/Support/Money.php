<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money is stored as integer paisa (ADR-007, D4). This class converts between paisa and taka
 * and formats amounts for display.
 */
final class Money
{
    private const PAISA_PER_TAKA = 100;

    private const CURRENCY_SYMBOL = '৳';

    /**
     * Format paisa for display: 485000 → "৳ 4,850", 485050 → "৳ 4,850.50".
     */
    public static function format(int $paisa): string
    {
        return self::CURRENCY_SYMBOL.' '.Numerals::localize(self::formatNumber($paisa, thousandsSeparator: ','));
    }

    /**
     * Convert a taka amount from form input to paisa, rounded to the nearest paisa.
     */
    public static function fromTaka(int|float|string $taka): int
    {
        if (! is_numeric($taka)) {
            throw new InvalidArgumentException("[{$taka}] is not a valid taka amount.");
        }

        $paisa = (int) round((float) $taka * self::PAISA_PER_TAKA);

        if ($paisa < 0) {
            throw new InvalidArgumentException('Money amounts cannot be negative.');
        }

        return $paisa;
    }

    /**
     * Convert paisa to a plain taka value for form inputs: 485000 → "4850", 485050 → "4850.50".
     */
    public static function toTakaInput(int $paisa): string
    {
        return self::formatNumber($paisa, thousandsSeparator: '');
    }

    private static function formatNumber(int $paisa, string $thousandsSeparator): string
    {
        $hasPaisa = $paisa % self::PAISA_PER_TAKA !== 0;

        return number_format(
            $paisa / self::PAISA_PER_TAKA,
            decimals: $hasPaisa ? 2 : 0,
            thousands_separator: $thousandsSeparator,
        );
    }
}
