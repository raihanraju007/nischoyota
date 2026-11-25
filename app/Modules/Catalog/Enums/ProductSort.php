<?php

namespace App\Modules\Catalog\Enums;

enum ProductSort: string
{
    case BestMatch = 'relevance';
    case Popular = 'popular';
    case Newest = 'newest';
    case PriceLowToHigh = 'price-asc';
    case PriceHighToLow = 'price-desc';
    case HighestRated = 'rating';

    public function label(): string
    {
        return match ($this) {
            self::BestMatch => __('Best match'),
            self::Popular => __('Popular'),
            self::Newest => __('Newest'),
            self::PriceLowToHigh => __('Price: Low to High'),
            self::PriceHighToLow => __('Price: High to Low'),
            self::HighestRated => __('Highest Rated'),
        };
    }

    /**
     * Sorts offered on a page: Best match only for search results.
     *
     * @return list<self>
     */
    public static function optionsFor(bool $isSearch): array
    {
        return array_values(array_filter(self::cases(), fn (self $sort): bool => $isSearch || $sort !== self::BestMatch));
    }
}
