<?php

namespace Database\Seeders;

use App\Modules\Content\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The six system pages, with the text from the original storefront views (database/data/pages/*.md).
 */
class PageSeeder extends Seeder
{
    /**
     * @var array<string, array{0: string, 1: string}> slug => [title, subtitle]
     */
    public const SYSTEM_PAGES = [
        'about' => ['About Nischoyota', 'প্রাকৃতিক ও খাঁটি পণ্যের নিশ্চয়তা — the assurance of natural, pure products.'],
        'faq' => ['Frequently Asked Questions', 'Quick answers about ordering, delivery, payment and returns.'],
        'privacy-policy' => ['Privacy Policy', "Your privacy matters to us. Here's how we handle your information."],
        'terms' => ['Terms & Conditions', 'Please read these terms carefully before using our website.'],
        'shipping-policy' => ['Shipping Policy', 'How and when your order reaches you.'],
        'return-policy' => ['Return & Refund Policy', "Not happy with your purchase? Here's how returns work."],
    ];

    public function run(): void
    {
        $banglaContent = require database_path('data/catalog_bn.php');

        foreach (self::SYSTEM_PAGES as $slug => [$title, $subtitle]) {
            [$banglaTitle, $banglaSubtitle] = $banglaContent['pages'][$slug];

            $page = new Page([
                'slug' => $slug,
                'title' => $title,
                'title_bn' => $banglaTitle,
                'subtitle' => $subtitle,
                'subtitle_bn' => $banglaSubtitle,
                'body' => file_get_contents(database_path("data/pages/{$slug}.md")),
                'body_bn' => file_get_contents(database_path("data/pages/bn/{$slug}.md")),
                'is_published' => true,
            ]);
            $page->forceFill(['is_system' => true])->save();
        }
    }
}
