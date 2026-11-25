<?php

namespace App\Modules\Catalog\Data;

/**
 * The home page sections (F-019). Stored in the cache as an array (toArray / fromArray).
 */
final readonly class HomePageData
{
    /**
     * @param  list<array{name: string, slug: string, imageUrl: string|null, productCount: int}>  $departments
     * @param  list<ProductCardData>  $featured
     * @param  list<ProductCardData>  $deals
     * @param  list<ProductCardData>  $newArrivals
     * @param  list<ProductCardData>  $bestSellers
     * @param  array<string, string>  $promoLinks  department slug => URL
     */
    public function __construct(
        public array $departments,
        public int $highestDiscount,
        public array $featured,
        public array $deals,
        public array $newArrivals,
        public array $bestSellers,
        public array $promoLinks,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $cards = fn (array $cards): array => array_map(fn (ProductCardData $card): array => $card->toArray(), $cards);

        return [
            'departments' => $this->departments,
            'highestDiscount' => $this->highestDiscount,
            'featured' => $cards($this->featured),
            'deals' => $cards($this->deals),
            'newArrivals' => $cards($this->newArrivals),
            'bestSellers' => $cards($this->bestSellers),
            'promoLinks' => $this->promoLinks,
        ];
    }

    /**
     * @param  array<string, mixed>  $sections  From toArray()
     */
    public static function fromArray(array $sections): self
    {
        $cards = fn (array $cards): array => array_map(ProductCardData::fromArray(...), $cards);

        return new self(
            departments: $sections['departments'],
            highestDiscount: $sections['highestDiscount'],
            featured: $cards($sections['featured']),
            deals: $cards($sections['deals']),
            newArrivals: $cards($sections['newArrivals']),
            bestSellers: $cards($sections['bestSellers']),
            promoLinks: $sections['promoLinks'],
        );
    }
}
