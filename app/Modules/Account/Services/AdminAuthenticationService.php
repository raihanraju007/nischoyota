<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Data\AdminLoginData;
use App\Modules\Account\Exceptions\AdminLoginFailedException;
use App\Modules\Account\Models\User;
use App\Modules\Account\Repositories\UserRepository;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Hash;

final class AdminAuthenticationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly StatefulGuard $guard,
    ) {}

    /**
     * @throws AdminLoginFailedException
     */
    public function login(AdminLoginData $data): User
    {
        $user = $this->userRepository->findByLogin($data->login);

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw AdminLoginFailedException::invalidCredentials();
        }

        if (! $user->status->canSignIn()) {
            throw AdminLoginFailedException::accountBlocked();
        }

        if (! $user->role->canAccessAdminPanel()) {
            throw AdminLoginFailedException::notAnAdminUser();
        }

        $this->guard->login($user, $data->shouldRemember);
        $this->userRepository->updateLastLogin($user);

        return $user;
    }

    public function logout(): void
    {
        $this->guard->logout();
    }
}
