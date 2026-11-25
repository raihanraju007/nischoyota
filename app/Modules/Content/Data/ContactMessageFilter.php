<?php

namespace App\Modules\Content\Data;

use App\Modules\Content\Enums\ContactMessageStatus;

/**
 * Admin inbox filter (F-026). A null status is the "All" tab.
 */
final readonly class ContactMessageFilter
{
    public const ALL = 'all';

    public function __construct(
        public ?ContactMessageStatus $status,
        public ?string $search,
    ) {}

    /**
     * @return array<string, string>
     */
    public function queryFor(?ContactMessageStatus $status): array
    {
        return array_filter([
            'status' => $status === ContactMessageStatus::New ? null : ($status?->value ?? self::ALL),
            'search' => $this->search,
        ]);
    }
}
