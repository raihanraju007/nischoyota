<?php

namespace App\Modules\Inventory\Enums;

enum StockMovementReason: string
{
    case OpeningStock = 'opening_stock';
    case Restock = 'restock';
    case Damaged = 'damaged';
    case StockCount = 'stock_count';
    case OrderPlaced = 'order_placed';
    case OrderCancelled = 'order_cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::OpeningStock => __('Opening stock'),
            self::Restock => __('Restock'),
            self::Damaged => __('Damaged / lost'),
            self::StockCount => __('Stock count'),
            self::OrderPlaced => __('Order placed'),
            self::OrderCancelled => __('Order cancelled'),
            self::Returned => __('Returned'),
        };
    }

    /**
     * Help text for the manual reasons in the "Adjust stock" form.
     */
    public function description(): string
    {
        return match ($this) {
            self::Restock => __('Add received items to stock.'),
            self::Damaged => __('Remove broken, lost or used items.'),
            self::StockCount => __('Set the exact number you counted.'),
            default => '',
        };
    }

    /**
     * Reasons staff can choose in the admin "Adjust stock" form.
     *
     * @return list<self>
     */
    public static function manualReasons(): array
    {
        return [self::Restock, self::Damaged, self::StockCount];
    }

    /**
     * How the entered quantity becomes a change: added, removed, or the new exact count.
     */
    public function quantityChangeFor(int $quantity, int $currentStock): int
    {
        return match ($this) {
            self::Damaged, self::OrderPlaced => -$quantity,
            self::StockCount => $quantity - $currentStock,
            default => $quantity,
        };
    }
}
