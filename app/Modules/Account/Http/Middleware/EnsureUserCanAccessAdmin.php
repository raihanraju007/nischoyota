<?php

namespace App\Modules\Account\Http\Middleware;

use App\Modules\Account\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias "admin". Runs after "auth": lets only active staff/admin users through.
 * A user blocked while signed in is signed out on the next request.
 */
final class EnsureUserCanAccessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->status->canSignIn()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('admin.login')->with('error', __('This account is blocked. Please contact the store owner.'));
        }

        abort_unless($user->role->canAccessAdminPanel(), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
