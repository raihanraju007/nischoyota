<?php

namespace App\Modules\Content\Enums;

enum ContactSubject: string
{
    case OrderIssue = 'order_issue';
    case ProductQuestion = 'product_question';
    case ReturnRequest = 'return_request';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OrderIssue => __('Order Issue'),
            self::ProductQuestion => __('Product Question'),
            self::ReturnRequest => __('Return Request'),
            self::Other => __('Other'),
        };
    }

    /**
     * @return array<string, string> value => label, for the contact form select
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $subject): array => [$subject->value, $subject->label()], self::cases()), 1, 0);
    }
}
