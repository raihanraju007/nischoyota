<?php

namespace App\Modules\Shopping\Data;

use App\Modules\Shopping\Enums\CartLineState;

/**
 * One cart line as shown to the customer. Money in paisa, read live from the variant.
 */
final readonly class CartLine
{
    public function __construct(
        public int $itemId,
        public int $variantId,
        public string $productName,
        public string $productUrl,
        public ?string $variantLabel,
        public ?string $imageUrl,
        public int $unitPrice,
        public int $quantity,
        public CartLineState $state,
        public int $stockQuantity,
        public ?int $previousPrice,
        public int $productId,
        public string $sku,
        public bool $allowsPreorder,
    ) {}

    public function lineTotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function note(): ?string
    {
        return $this->state->note($this->stockQuantity);
    }

    public function hasPriceChanged(): bool
    {
        return $this->previousPrice !== null;
    }
}
