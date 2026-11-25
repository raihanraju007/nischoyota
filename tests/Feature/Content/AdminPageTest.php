<?php

use App\Modules\Content\Models\Page;
use App\Modules\Content\Services\PageService;
use Database\Seeders\PageSeeder;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pageInput(array $overrides = []): array
{
    return ['title' => 'Warranty', 'slug' => '', 'subtitle' => 'How warranty works.', 'body' => "## Cover\n\nTwo years.", 'is_published' => '1', ...$overrides];
}

describe('seeded pages', function () {
    it('creates the six store pages from the old storefront text', function () {
        $this->seed(PageSeeder::class);

        expect(Page::query()->where('is_system', true)->pluck('slug')->sort()->values()->all())
            ->toBe(['about', 'faq', 'privacy-policy', 'return-policy', 'shipping-policy', 'terms'])
            ->and(Page::query()->where('slug', 'return-policy')->value('body'))->toContain('## 7-Day Easy Returns')
            ->and(Page::query()->where('slug', 'faq')->value('body'))->toContain('## What is Cash on Delivery?');
    });
});

describe('rendering', function () {
    it('renders Markdown', function () {
        $page = Page::factory()->make(['body' => "## Returns\n\n**7 days**, see [policy](/return-policy).\n\n- one\n- two"]);

        $html = app(PageService::class)->renderBody($page);

        expect($html)->toContain('<h2>Returns</h2>')
            ->toContain('<strong>7 days</strong>')
            ->toContain('<a href="/return-policy">policy</a>')
            ->toContain('<li>one</li>');
    });

    it('strips HTML and unsafe links', function () {
        $page = Page::factory()->make(['body' => "<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>\n\n[click](javascript:alert(1))"]);

        $html = app(PageService::class)->renderBody($page);

        expect($html)->not->toContain('<script')
            ->not->toContain('onerror')
            ->not->toContain('javascript:');
    });
});

describe('admin', function () {
    it('lists pages with store pages first', function () {
        Page::factory()->create(['title' => 'Warranty']);
        Page::factory()->system()->create(['title' => 'Terms', 'slug' => 'terms']);

        actingAsAdmin()->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSeeInOrder(['Terms', 'store page', 'Warranty']);
    });

    it('creates a page with a generated slug and normalised line endings', function () {
        actingAsAdmin()->post(route('admin.pages.store'), pageInput(['body' => "## Cover\r\n\r\nTwo years."]))
            ->assertSessionHasNoErrors();

        $page = Page::query()->sole();
        expect($page->slug)->toBe('warranty')
            ->and($page->body)->toBe("## Cover\n\nTwo years.")
            ->and($page->is_system)->toBeFalse();
    });

    it('validates a page', function (array $input, string $errorField) {
        Page::factory()->create(['slug' => 'taken']);

        actingAsAdmin()->post(route('admin.pages.store'), pageInput($input))->assertSessionHasErrors($errorField);
    })->with([
        'title missing' => [['title' => ''], 'title'],
        'body missing' => [['body' => ''], 'body'],
        'slug taken' => [['slug' => 'taken'], 'slug'],
        'slug with spaces' => [['slug' => 'my page'], 'slug'],
        'body too long' => [['body' => str_repeat('a', 50001)], 'body'],
    ]);

    it('edits a store page but keeps its slug', function () {
        $page = Page::factory()->system()->create(['slug' => 'return-policy', 'title' => 'Returns']);

        actingAsAdmin()->get(route('admin.pages.edit', $page))->assertOk()->assertSee('cannot change');

        actingAsAdmin()->put(route('admin.pages.update', $page), pageInput(['title' => 'Return Policy', 'slug' => 'hacked', 'is_published' => '0']))
            ->assertRedirect(route('admin.pages.edit', $page));

        expect($page->fresh())
            ->title->toBe('Return Policy')
            ->slug->toBe('return-policy')
            ->is_published->toBeFalse();
    });

    it('does not delete a store page', function () {
        $page = Page::factory()->system()->create();

        actingAsAdmin()->delete(route('admin.pages.destroy', $page))
            ->assertSessionHas('error', 'This page is part of the store and cannot be deleted. Unpublish it instead.');

        $this->assertModelExists($page);
    });

    it('deletes an extra page', function () {
        $page = Page::factory()->create();

        actingAsAdmin()->delete(route('admin.pages.destroy', $page))->assertRedirect(route('admin.pages.index'));

        $this->assertModelMissing($page);
    });

    it('is admin only', function (string $method, string $routeName, bool $needsPage) {
        $page = Page::factory()->create();
        $url = $needsPage ? route($routeName, $page) : route($routeName);

        $this->{$method}($url)->assertRedirect(route('admin.login'));
        actingAsCustomer()->{$method}($url)->assertForbidden();
        actingAsStaff()->{$method}($url)->assertForbidden();
    })->with([
        ['get', 'admin.pages.index', false],
        ['get', 'admin.pages.create', false],
        ['post', 'admin.pages.store', false],
        ['get', 'admin.pages.edit', true],
        ['put', 'admin.pages.update', true],
        ['delete', 'admin.pages.destroy', true],
    ]);
});
