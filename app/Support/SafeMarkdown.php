<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Admin-written Markdown → HTML with raw HTML stripped and unsafe links (javascript:, data:…) removed.
 */
final class SafeMarkdown
{
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
