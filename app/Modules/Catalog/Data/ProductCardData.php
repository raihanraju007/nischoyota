<?php

namespace App\Modules\Catalog\Data;

/**
 * Everything a storefront product card shows. Money in paisa.
 */
final readonly class ProductCardData
{
    /**
     * @param  'new'|'best'|null  $badge
     * @param  ?int  $quickAddVariantId  Set for an available single-variant product: the card adds it to the cart.
     *                                   Null for products with options (the card links to the product page).
     * @param  ?int  $productId  For the wishlist heart (F-022).
     * @param  ?string  $unitLabel  What the price buys: "500 g", "pc".
     */
    public function __construct(
        public string $name,
        public string $url,
        public ?string $imageUrl,
        public string $categoryName,
        public int $price,
        public ?int $oldPrice,
        public int $discountPercent,
        public float $rating,
        public int $reviewCount,
        public ?string $badge,
        public bool $isAvailable,
        public ?int $quickAddVariantId = null,
        public ?int $productId = null,
        public ?string $unitLabel = null,
    ) {}

    /**
     * For caching: the cache store does not unserialize objects.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $card  From toArray()
     */
    public static function fromArray(array $card): self
    {
        return new self(...$card);
    }
}
