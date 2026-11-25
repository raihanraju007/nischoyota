<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Http\Requests\RegisterRequest;
use App\Modules\Account\Services\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RegisterController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $result = $this->authService->register($request->toData());

        $request->session()->put(PhoneVerificationController::SESSION_KEY, $result->user->id);

        return to_route('phone.verify')->with('info', $result->codeNotice ?? __('We sent a 6-digit code to your phone.'));
    }
}
