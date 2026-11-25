<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Http\Requests\VerifyCodeRequest;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\CustomerAuthService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Code step after register / login with an unverified phone. The pending user id is kept in the session.
 */
final class PhoneVerificationController extends Controller
{
    public const SESSION_KEY = 'auth.pending_phone_verification';

    public function __construct(private readonly CustomerAuthService $authService) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return to_route('login');
        }

        return view('auth.verify-phone', ['maskedPhone' => PhoneNumber::mask($user->phone)]);
    }

    public function store(VerifyCodeRequest $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return to_route('login');
        }

        try {
            $this->authService->verifyPhoneAndLogin($user, $request->code());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'))->with('success', __('Your phone is verified. Welcome to Nischoyota!'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return to_route('login');
        }

        try {
            $this->authService->sendPhoneVerificationCode($user, $request->ip());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('success', __('A new code is on its way.'));
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get(self::SESSION_KEY);

        return $userId === null ? null : $this->authService->findPendingCustomer((int) $userId);
    }
}
