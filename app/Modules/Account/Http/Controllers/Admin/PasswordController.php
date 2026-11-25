<?php

namespace App\Modules\Account\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Account\Http\Requests\UpdatePasswordRequest;
use App\Modules\Account\Services\UserService;
use Illuminate\Http\RedirectResponse;

final class PasswordController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->userService->changePassword($request->user(), $request->newPassword());

        return back()->with('success', __('Password updated.'));
    }
}
