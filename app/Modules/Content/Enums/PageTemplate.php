<?php

namespace App\Modules\Content\Enums;

use App\Modules\Content\Models\Page;

/**
 * Which storefront view shows a content page (F-020). Only system pages get a special layout.
 */
enum PageTemplate: string
{
    case Faq = 'faq';
    case About = 'about';
    case Policy = 'policy';

    public static function for(Page $page): self
    {
        return match (true) {
            $page->is_system && $page->slug === 'faq' => self::Faq,
            $page->is_system && $page->slug === 'about' => self::About,
            default => self::Policy,
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Faq => 'pages.faq',
            self::About => 'pages.about',
            self::Policy => 'pages.show',
        };
    }
}
