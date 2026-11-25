<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

final class LocaleController extends Controller
{
    private const COOKIE_MINUTES = 60 * 24 * 365;

    public function update(Locale $locale): RedirectResponse
    {
        Cookie::queue(Locale::COOKIE_NAME, $locale->value, self::COOKIE_MINUTES);

        return back();
    }
}
