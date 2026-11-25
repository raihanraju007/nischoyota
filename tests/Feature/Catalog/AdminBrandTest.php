<?php

use App\Modules\Catalog\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function brandInput(array $overrides = []): array
{
    return ['name' => 'Walton', 'slug' => '', 'is_active' => '1', ...$overrides];
}

describe('list', function () {
    it('lists brands by name', function () {
        Brand::factory()->create(['name' => 'Vision']);
        Brand::factory()->inactive()->create(['name' => 'Klick']);
        Brand::factory()->create(['name' => 'Walton']);

        actingAsStaff()->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSeeInOrder(['Klick', 'Hidden', 'Vision', 'Walton']);
    });

    it('searches by name', function () {
        Brand::factory()->create(['name' => 'Walton']);
        Brand::factory()->create(['name' => 'Vision']);

        actingAsAdmin()->get(route('admin.brands.index', ['search' => 'walt']))
            ->assertSee('Walton')
            ->assertDontSee('/vision');
    });

    it('treats % and _ in the search as plain text', function () {
        Brand::factory()->create(['name' => 'Walton']);

        actingAsAdmin()->get(route('admin.brands.index', ['search' => '%']))
            ->assertSee('No brands match');
    });

    it('paginates 20 per page and keeps the search in page links', function () {
        Brand::factory()->count(21)->sequence(fn ($sequence) => ['name' => sprintf('Brand %02d', $sequence->index + 1)])->create();

        actingAsAdmin()->get(route('admin.brands.index', ['search' => 'Brand']))
            ->assertSee('Showing 1–20 of 21 brands')
            ->assertSee('Brand 20')
            ->assertDontSee('Brand 21')
            ->assertSee('search=Brand&amp;page=2', escape: false);

        actingAsAdmin()->get(route('admin.brands.index', ['search' => 'Brand', 'page' => 2]))->assertSee('Brand 21');
    });

    it('shows empty states', function () {
        actingAsAdmin()->get(route('admin.brands.index'))->assertSee('No brands yet');
    });

    it('fills the form when editing', function () {
        $brand = Brand::factory()->create(['name' => 'Superstar']);

        actingAsAdmin()->get(route('admin.brands.edit', $brand))
            ->assertSee('Edit Brand')
            ->assertSee('value="Superstar"', escape: false);
    });

    it('links to brands from the sidebar', function () {
        actingAsStaff()->get(route('admin.dashboard'))->assertSee(route('admin.brands.index'));
    });
});

describe('save', function () {
    it('creates a brand with a generated slug and a logo', function () {
        actingAsStaff()->post(route('admin.brands.store'), brandInput([
            'name' => 'Deshi Basics',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]))->assertRedirect(route('admin.brands.index'))->assertSessionHas('success');

        $brand = Brand::query()->sole();
        expect($brand->slug)->toBe('deshi-basics')->and($brand->is_active)->toBeTrue();
        Storage::disk('public')->assertExists($brand->logo);
    });

    it('validates the input', function (array $input, string $errorField) {
        Brand::factory()->create(['name' => 'Walton', 'slug' => 'walton']);

        actingAsAdmin()->post(route('admin.brands.store'), brandInput($input))->assertSessionHasErrors($errorField);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'name taken, other case' => [['name' => 'walton'], 'name'],
        'slug taken' => [['name' => 'Walton BD', 'slug' => 'walton'], 'slug'],
        'invalid slug' => [['name' => 'Vision', 'slug' => 'vision!'], 'slug'],
        'svg logo' => [['name' => 'Vision', 'logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')], 'logo'],
        'logo too large' => [['name' => 'Vision', 'logo' => UploadedFile::fake()->image('logo.png')->size(1025)], 'logo'],
    ]);

    it('updates a brand and keeps its own name and slug valid', function () {
        $brand = Brand::factory()->create(['name' => 'Walton', 'slug' => 'walton']);

        actingAsAdmin()->put(route('admin.brands.update', $brand), brandInput(['slug' => 'walton', 'is_active' => '0']))
            ->assertSessionHasNoErrors();

        expect($brand->fresh()->is_active)->toBeFalse();
    });

    it('replaces and removes the logo file', function () {
        Storage::disk('public')->put('brands/old.png', 'old');
        $brand = Brand::factory()->withLogo('brands/old.png')->create(['name' => 'Walton']);

        actingAsAdmin()->put(route('admin.brands.update', $brand), brandInput(['logo' => UploadedFile::fake()->image('new.png')]));
        $newLogo = $brand->fresh()->logo;
        Storage::disk('public')->assertMissing('brands/old.png');
        Storage::disk('public')->assertExists($newLogo);

        actingAsAdmin()->put(route('admin.brands.update', $brand), brandInput(['remove_logo' => '1']));
        expect($brand->fresh()->logo)->toBeNull();
        Storage::disk('public')->assertMissing($newLogo);
    });

    it('deletes a brand and its logo', function () {
        Storage::disk('public')->put('brands/walton.png', 'logo');
        $brand = Brand::factory()->withLogo('brands/walton.png')->create();

        actingAsAdmin()->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($brand);
        Storage::disk('public')->assertMissing('brands/walton.png');
    });
});

describe('authorization', function () {
    it('keeps guests out and forbids customers', function (string $method, string $routeName) {
        $brand = Brand::factory()->create();

        $this->{$method}(route($routeName, $brand))->assertRedirect(route('admin.login'));
        actingAsCustomer()->{$method}(route($routeName, $brand))->assertForbidden();
    })->with([
        ['get', 'admin.brands.index'],
        ['get', 'admin.brands.edit'],
        ['post', 'admin.brands.store'],
        ['put', 'admin.brands.update'],
        ['delete', 'admin.brands.destroy'],
    ]);
});
