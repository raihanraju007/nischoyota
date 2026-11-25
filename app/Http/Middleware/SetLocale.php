<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen language (cookie), falling back to the app default (Bangla).
 * Printed order documents stay English: they are handed to couriers and need no Bangla font.
 */
final class SetLocale
{
    private const ENGLISH_ONLY_ROUTES = ['admin.orders.invoice', 'admin.orders.packing-slip', 'admin.orders.packing-slips'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::ENGLISH_ONLY_ROUTES)) {
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
