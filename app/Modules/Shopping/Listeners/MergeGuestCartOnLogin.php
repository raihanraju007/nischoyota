<?php

namespace App\Modules\Shopping\Listeners;

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Auth\Events\Login;

/**
 * Every sign-in (login, verify after register, password reset, remember-me) keeps the guest's cart (F-021, rule 7).
 */
final class MergeGuestCartOnLogin
{
    public function __construct(
        private readonly CurrentCart $currentCart,
        private readonly CartService $cartService,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $guestToken = $this->currentCart->pullGuestToken();

        if ($guestToken !== null) {
            $this->cartService->mergeGuestCart($guestToken, $event->user);
        }
    }
}
