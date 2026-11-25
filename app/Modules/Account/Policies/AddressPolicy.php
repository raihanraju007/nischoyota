<?php

namespace App\Modules\Account\Policies;

use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Customers only touch their own addresses; others get 404 so ids do not leak.
 */
final class AddressPolicy
{
    public function manage(User $user, Address $address): Response
    {
        return $address->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
