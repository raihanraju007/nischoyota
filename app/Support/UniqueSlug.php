<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

final class UniqueSlug
{
    /**
     * "Fans" → "fans", or "fans-2", "fans-3"… while $isTaken returns true.
     *
     * @param  Closure(string): bool  $isTaken
     */
    public static function generate(string $name, Closure $isTaken, string $fallback): string
    {
        $baseSlug = Str::slug($name) ?: $fallback;
        $slug = $baseSlug;
        $suffix = 2;

        while ($isTaken($slug)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
