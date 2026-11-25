<?php

namespace App\Modules\Shopping\Data;

/**
 * The lines of a cart and the coupon code saved on it.
 */
final readonly class CartContents
{
    /**
     * @param  list<CartLine>  $lines
     */
    public function __construct(
        public array $lines,
        public ?string $couponCode,
    ) {}

    public static function empty(): self
    {
        return new self([], null);
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * Number of lines (not the sum of quantities): the header badge (F-021, rule 11).
     */
    public function lineCount(): int
    {
        return count($this->lines);
    }

    /**
     * Sum of the buyable lines only (paisa).
     */
    public function subtotal(): int
    {
        return array_sum(array_map(
            fn (CartLine $line): int => $line->state->isBuyable() ? $line->lineTotal() : 0,
            $this->lines,
        ));
    }

    public function hasIssues(): bool
    {
        foreach ($this->lines as $line) {
            if (! $line->state->isBuyable()) {
                return true;
            }
        }

        return false;
    }

    public function canCheckout(): bool
    {
        return ! $this->isEmpty() && ! $this->hasIssues();
    }
}
