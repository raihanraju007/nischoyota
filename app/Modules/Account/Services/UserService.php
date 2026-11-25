<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Data\NewAdminData;
use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\User;
use App\Modules\Account\Repositories\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function createAdmin(NewAdminData $data): User
    {
        return $this->userRepository->createAdmin($data);
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $this->userRepository->updatePassword($user, $newPassword);
    }

    /**
     * A blocked account cannot order, signed in or as a guest with the account's phone (F-026).
     */
    public function isBlockedFromOrdering(?User $user, string $phone): bool
    {
        return $user?->status === UserStatus::Blocked || $this->userRepository->isPhoneBlocked($phone);
    }
}
