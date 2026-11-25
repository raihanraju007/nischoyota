<?php

namespace App\Modules\Order\Enums;

use Carbon\CarbonImmutable;

/**
 * Date filter on the admin order list. Days are Bangladesh days (config('shop.timezone')).
 */
enum OrderDateRange: string
{
    case AllTime = 'all';
    case Today = 'today';
    case Last7Days = '7d';
    case Last30Days = '30d';
    case ThisYear = 'year';

    public function label(): string
    {
        return match ($this) {
            self::AllTime => __('All time'),
            self::Today => __('Today'),
            self::Last7Days => __('Last 7 days'),
            self::Last30Days => __('Last 30 days'),
            self::ThisYear => __('This year'),
        };
    }

    /**
     * Start of the range in UTC, or null for all time.
     */
    public function startsAt(): ?CarbonImmutable
    {
        $today = CarbonImmutable::now(config('shop.timezone'))->startOfDay();

        $start = match ($this) {
            self::AllTime => null,
            self::Today => $today,
            self::Last7Days => $today->subDays(6),
            self::Last30Days => $today->subDays(29),
            self::ThisYear => $today->startOfYear(),
        };

        return $start?->utc();
    }
}
