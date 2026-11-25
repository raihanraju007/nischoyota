<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Http\Requests\ForgotPasswordRequest;
use App\Modules\Account\Http\Requests\ResetPasswordRequest;
use App\Modules\Account\Services\CustomerAuthService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Forgot password: a code goes to the phone (SMS) or email; the recipient is kept in the session for the reset step.
 */
final class PasswordResetController extends Controller
{
    private const SESSION_KEY = 'auth.password_reset_recipient';

    public function __construct(private readonly CustomerAuthService $authService) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(ForgotPasswordRequest $request): RedirectResponse
    {
        try {
            $recipient = $this->authService->sendPasswordResetCode($request->login(), $request->ip());
        } catch (OtpException $exception) {
            return back()->withInput()->withErrors(['login' => $exception->getMessage()]);
        }

        $request->session()->put(self::SESSION_KEY, $recipient);

        return to_route('password.reset')->with('info', __("If an account exists, we've sent a code. It is valid for 5 minutes."));
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $recipient = $request->session()->get(self::SESSION_KEY);

        if ($recipient === null) {
            return to_route('password.request');
        }

        return view('auth.reset-password', ['maskedRecipient' => $this->mask($recipient)]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $recipient = $request->session()->get(self::SESSION_KEY);

        if ($recipient === null) {
            return to_route('password.request');
        }

        try {
            $this->authService->resetPasswordAndLogin($recipient, $request->code(), $request->newPassword());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();

        return to_route('account.dashboard')->with('success', __('Your password has been changed.'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $recipient = $request->session()->get(self::SESSION_KEY);

        if ($recipient === null) {
            return to_route('password.request');
        }

        try {
            $this->authService->sendPasswordResetCode($recipient, $request->ip());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('success', __('If an account exists, a new code is on its way.'));
    }

    /**
     * 01712345678 → 017•••••678; rahim@example.com → ra•••@example.com
     */
    private function mask(string $recipient): string
    {
        if (! str_contains($recipient, '@')) {
            return PhoneNumber::mask($recipient);
        }

        [$name, $domain] = explode('@', $recipient, 2);

        return mb_substr($name, 0, 2).'•••@'.$domain;
    }
}
