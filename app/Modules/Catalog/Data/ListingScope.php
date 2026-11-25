<?php

namespace App\Modules\Catalog\Data;

/**
 * Which listed products a storefront page starts from (before the customer's filters).
 */
final readonly class ListingScope
{
    /**
     * @param  list<int>|null  $categoryIds  null = every category
     * @param  list<string>  $searchWords  every word must match (F-018)
     */
    private function __construct(
        public ?array $categoryIds = null,
        public bool $onlyDiscounted = false,
        public array $searchWords = [],
        public ?string $searchPhrase = null,
    ) {}

    public static function wholeShop(): self
    {
        return new self;
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public static function categories(array $categoryIds): self
    {
        return new self(categoryIds: $categoryIds);
    }

    public static function deals(): self
    {
        return new self(onlyDiscounted: true);
    }

    /**
     * @param  list<string>  $words
     */
    public static function search(string $phrase, array $words): self
    {
        return new self(searchWords: $words, searchPhrase: $phrase);
    }

    public function isSearch(): bool
    {
        return $this->searchWords !== [];
    }
}
