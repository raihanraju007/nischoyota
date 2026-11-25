<?php

namespace App\Modules\Content\Data;

use App\Modules\Content\Enums\ContactSubject;

/**
 * A validated contact form message. Phone is normalised (01XXXXXXXXX).
 */
final readonly class ContactMessageData
{
    public function __construct(
        public ?int $userId,
        public string $name,
        public string $phone,
        public ?string $email,
        public ContactSubject $subject,
        public string $message,
    ) {}
}
