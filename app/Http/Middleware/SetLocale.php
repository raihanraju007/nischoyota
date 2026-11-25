<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen language (cookie), falling back to the app default (Bangla).
 * The staff admin panel is English-only.
 */
final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*')) {
            app()->setLocale(Locale::English->value);

            return $next($request);
        }

        $chosenLocale = Locale::tryFrom((string) $request->cookie(Locale::COOKIE_NAME));

        if ($chosenLocale !== null) {
            app()->setLocale($chosenLocale->value);
        }

        return $next($request);
    }
}
