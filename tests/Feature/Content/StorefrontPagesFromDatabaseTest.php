<?php

use App\Modules\Content\Models\Page;
use Database\Seeders\PageSeeder;

it('renders every seeded system page on its own URL', function (string $routeName, string $title) {
    $this->seed(PageSeeder::class);

    $this->get(route($routeName))->assertOk()->assertSee($title, escape: false);
})->with([
    ['about', 'About Nischoyota'],
    ['faq', 'Frequently Asked Questions'],
    ['privacy', 'Privacy Policy'],
    ['terms', 'Terms &amp; Conditions'],
    ['shipping', 'Shipping Policy'],
    ['returns', 'Return &amp; Refund Policy'],
]);

it('shows the edited text, rendered safely, with the last updated date', function () {
    Page::factory()->system()->create([
        'slug' => 'terms',
        'title' => 'Terms & Conditions',
        'subtitle' => 'Read these first.',
        'body' => "## Orders\n\nWe confirm **every** order by phone.\n\n<script>alert(1)</script>",
        'meta_title' => 'Store Terms',
        'meta_description' => 'Our terms of sale.',
    ]);
    $this->travelTo(now());

    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('<h2>Orders</h2>', escape: false)
        ->assertSee('<strong>every</strong>', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertSee('Last updated: '.now()->format('j F Y'))
        ->assertSee('<title>Store Terms', escape: false)
        ->assertSee('content="Our terms of sale."', escape: false);
});

it('builds the FAQ accordion from the ## headings', function () {
    Page::factory()->system()->create([
        'slug' => 'faq',
        'body' => "Quick answers below.\n\n## How do I pay?\n\nCash on **delivery**.\n\n## Can I return?\n\nWithin 7 days.",
    ]);

    $this->get(route('faq'))
        ->assertOk()
        ->assertSee('Quick answers below.')
        ->assertSeeInOrder(['How do I pay?', 'Cash on <strong>delivery</strong>.', 'Can I return?', 'Within 7 days.'], escape: false)
        ->assertDontSee('<h2>How do I pay?</h2>', escape: false);
});

it('serves extra pages at /page/{slug} only', function () {
    Page::factory()->create(['slug' => 'warranty', 'title' => 'Warranty Info']);
    Page::factory()->system()->create(['slug' => 'about']);

    $this->get(route('page', 'warranty'))->assertOk()->assertSee('Warranty Info');
    $this->get('/warranty')->assertNotFound();
    $this->get(route('page', 'about'))->assertNotFound();
});

it('hides unpublished and missing pages', function () {
    Page::factory()->system()->draft()->create(['slug' => 'shipping-policy']);
    Page::factory()->draft()->create(['slug' => 'draft-page']);

    $this->get(route('shipping'))->assertNotFound();
    $this->get(route('page', 'draft-page'))->assertNotFound();
    $this->get(route('page', 'nothing-here'))->assertNotFound();
    $this->get(route('about'))->assertNotFound();
});
