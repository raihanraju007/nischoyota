<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Http\Requests\UpdatePasswordRequest;
use App\Modules\Account\Http\Requests\UpdateProfileRequest;
use App\Modules\Account\Services\ProfileService;
use App\Modules\Account\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly UserService $userService,
    ) {}

    public function show(): View
    {
        return view('account.profile');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profileService->updateProfile($request->user(), $request->name(), $request->email());

        return to_route('account.profile')->with('success', __('Profile saved.'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->userService->changePassword($request->user(), $request->newPassword());

        return to_route('account.profile')->with('success', __('Password updated.'));
    }
}
