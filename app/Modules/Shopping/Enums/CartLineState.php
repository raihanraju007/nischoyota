<?php

namespace App\Modules\Shopping\Enums;

/**
 * Whether a cart line can be bought right now. Worked out on every read from the live variant (F-021).
 */
enum CartLineState: string
{
    case Available = 'available';
    case Preorder = 'preorder';
    case NotEnoughStock = 'not_enough_stock';
    case OutOfStock = 'out_of_stock';
    case Unavailable = 'unavailable';

    /**
     * Buyable lines count in the subtotal; any other line blocks checkout.
     */
    public function isBuyable(): bool
    {
        return in_array($this, [self::Available, self::Preorder], true);
    }

    /**
     * Note shown under the line; null when there is nothing to say.
     */
    public function note(int $stockQuantity): ?string
    {
        return match ($this) {
            self::Available => null,
            self::Preorder => __('Pre-order — ships when back in stock'),
            self::NotEnoughStock => __('Only :count left — please lower the quantity', ['count' => $stockQuantity]),
            self::OutOfStock => __('Out of stock — please remove it'),
            self::Unavailable => __('No longer available — please remove it'),
        };
    }
}
