<?php

namespace App\Modules\Account\Data;

use App\Modules\Account\Models\User;

/**
 * The saved (unverified) customer; $codeNotice is set when the code could not be sent yet (rate limit).
 */
final readonly class RegistrationResult
{
    public function __construct(
        public User $user,
        public ?string $codeNotice,
    ) {}
}
