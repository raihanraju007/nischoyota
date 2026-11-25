<?php

namespace App\Modules\Account\Http\Middleware;

use App\Modules\Account\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias "active". Runs after "auth" on customer pages: a user blocked while signed in is logged out.
 */
final class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->status->canSignIn()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('error', __('This account is blocked. Please contact customer support.'));
    }
}
