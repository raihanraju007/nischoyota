<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Exceptions\CustomerLoginFailedException;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Exceptions\PhoneNotVerifiedException;
use App\Modules\Account\Http\Requests\LoginRequest;
use App\Modules\Account\Services\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $this->authService->login($request->toData());
        } catch (CustomerLoginFailedException $exception) {
            $request->failLogin($exception->getMessage());
        } catch (PhoneNotVerifiedException $exception) {
            $request->clearRateLimit();

            return $this->sendToPhoneVerification($request, $exception);
        }

        $request->clearRateLimit();
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('success', __('You have been logged out.'));
    }

    private function sendToPhoneVerification(LoginRequest $request, PhoneNotVerifiedException $exception): RedirectResponse
    {
        $request->session()->put(PhoneVerificationController::SESSION_KEY, $exception->user->id);

        try {
            $this->authService->sendPhoneVerificationCode($exception->user, $request->ip());
        } catch (OtpException $otpException) {
            return to_route('phone.verify')->with('info', $otpException->getMessage());
        }

        return to_route('phone.verify')->with('info', $exception->getMessage());
    }
}
