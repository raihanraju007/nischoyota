<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers are stored as 11 digits: 01XXXXXXXXX (ADR-007, D1).
 */
final class PhoneNumber
{
    public const PATTERN = '/^01[3-9]\d{8}$/';

    private const COUNTRY_CODE = '880';

    /**
     * "+880 1712-345678", "8801712345678" and "01712 345678" all become "01712345678".
     * Returns null when the input is not a valid Bangladeshi mobile number.
     */
    public static function normalize(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = '0'.substr($digits, strlen(self::COUNTRY_CODE));
        }

        return preg_match(self::PATTERN, $digits) === 1 ? $digits : null;
    }

    /**
     * 01712345678 → 017•••••678 (shown on "we sent a code" screens).
     */
    public static function mask(string $phone): string
    {
        return substr($phone, 0, 3).str_repeat('•', max(0, strlen($phone) - 6)).substr($phone, -3);
    }
}
