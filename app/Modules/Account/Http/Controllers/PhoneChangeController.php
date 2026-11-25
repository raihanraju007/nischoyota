<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Http\Requests\ChangePhoneRequest;
use App\Modules\Account\Http\Requests\VerifyCodeRequest;
use App\Modules\Account\Services\ProfileService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Change phone: enter the new number → code by SMS to it → enter the code. The new number waits in the session.
 */
final class PhoneChangeController extends Controller
{
    private const SESSION_KEY = 'account.pending_phone';

    public function __construct(private readonly ProfileService $profileService) {}

    public function show(Request $request): View
    {
        $pendingPhone = $request->session()->get(self::SESSION_KEY);

        return view('account.phone', [
            'pendingPhone' => $pendingPhone,
            'maskedPendingPhone' => $pendingPhone === null ? null : PhoneNumber::mask($pendingPhone),
        ]);
    }

    public function sendCode(ChangePhoneRequest $request): RedirectResponse
    {
        try {
            $this->profileService->sendPhoneChangeCode($request->newPhone(), $request->ip());
        } catch (OtpException $exception) {
            return back()->withInput()->withErrors(['phone' => $exception->getMessage()]);
        }

        $request->session()->put(self::SESSION_KEY, $request->newPhone());

        return to_route('account.phone')->with('info', __('We sent a 6-digit code to your new number.'));
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $pendingPhone = $request->session()->get(self::SESSION_KEY);

        if ($pendingPhone === null) {
            return to_route('account.phone');
        }

        try {
            $this->profileService->confirmPhoneChange($request->user(), $pendingPhone, $request->code());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->forget(self::SESSION_KEY);

        return to_route('account.profile')->with('success', __('Your phone number has been changed.'));
    }
}
