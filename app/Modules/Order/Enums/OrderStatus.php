<?php

namespace App\Modules\Order\Enums;

/**
 * Order lifecycle. Staff move orders along allowedNextStatuses() (F-025).
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Confirmed => __('Confirmed'),
            self::Processing => __('Processing'),
            self::Shipped => __('Shipped'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
            self::Returned => __('Returned'),
        };
    }

    /**
     * Customers may cancel until the order is being packed (F-024).
     */
    public function isCancellableByCustomer(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /**
     * The normal path an order takes, for the timeline.
     *
     * @return list<self>
     */
    public static function progressSteps(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped, self::Delivered];
    }

    /**
     * Timeline step title: the first step reads "Order Placed".
     */
    public function stepTitle(): string
    {
        return $this === self::Pending ? __('Order Placed') : $this->label();
    }

    /**
     * Status chips on "My Orders".
     *
     * @return list<self>
     */
    public static function customerFilters(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped, self::Delivered, self::Cancelled];
    }

    /**
     * Where staff may move an order from this status (F-025). No skipping steps.
     *
     * @return list<self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Returned],
            self::Delivered => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $nextStatus): bool
    {
        return in_array($nextStatus, $this->allowedNextStatuses(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedNextStatuses() === [];
    }

    /**
     * Status tabs on the admin order list (after "All").
     *
     * @return list<self>
     */
    public static function adminFilters(): array
    {
        return self::cases();
    }
}
