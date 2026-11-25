<?php

namespace App\Modules\Account\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Account\Exceptions\AdminLoginFailedException;
use App\Modules\Account\Http\Requests\Admin\AdminLoginRequest;
use App\Modules\Account\Services\AdminAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function __construct(private readonly AdminAuthenticationService $authenticationService) {}

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $this->authenticationService->login($request->toData());
        } catch (AdminLoginFailedException $exception) {
            $request->failLogin($exception->getMessage());
        }

        $request->clearRateLimit();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authenticationService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login')->with('success', __('You have been signed out.'));
    }
}
