<?php

namespace App\Modules\Shopping\Data;

use App\Modules\Account\Models\User;

/**
 * Whose cart: a signed-in user, or a guest identified by the token kept in their session.
 */
final readonly class CartOwner
{
    private function __construct(
        public ?User $user,
        public ?string $guestToken,
    ) {}

    public static function user(User $user): self
    {
        return new self($user, null);
    }

    public static function guest(string $guestToken): self
    {
        return new self(null, $guestToken);
    }
}
