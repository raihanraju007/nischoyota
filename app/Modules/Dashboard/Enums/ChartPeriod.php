<?php

namespace App\Modules\Dashboard\Enums;

/**
 * Days shown in the dashboard sales chart (F-028).
 */
enum ChartPeriod: int
{
    case Last7Days = 7;
    case Last30Days = 30;

    public function label(): string
    {
        return match ($this) {
            self::Last7Days => __('Last 7 days'),
            self::Last30Days => __('Last 30 days'),
        };
    }

    /**
     * Bar label format: weekday for a week, day of month for a month.
     */
    public function barLabelFormat(): string
    {
        return $this === self::Last7Days ? 'D' : 'j';
    }
}
