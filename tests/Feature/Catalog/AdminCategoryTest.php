<?php

use App\Modules\Catalog\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function categoryInput(array $overrides = []): array
{
    return [
        'name' => 'Electrical',
        'slug' => '',
        'parent_id' => '',
        'sort_order' => 0,
        'is_active' => '1',
        ...$overrides,
    ];
}

describe('list', function () {
    it('shows subcategories under their parent in sort order', function () {
        $electrical = Category::factory()->create(['name' => 'Electrical', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Fashion', 'sort_order' => 2]);
        Category::factory()->childOf($electrical)->create(['name' => 'Tube Lights', 'sort_order' => 2]);
        Category::factory()->childOf($electrical)->create(['name' => 'Fans', 'sort_order' => 1]);

        actingAsStaff()->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['Electrical', '— Fans', '— Tube Lights', 'Fashion']);
    });

    it('shows an empty state without categories', function () {
        actingAsAdmin()->get(route('admin.categories.index'))->assertSee('No categories yet');
    });

    it('fills the form when editing', function () {
        $category = Category::factory()->create(['name' => 'Gadgets', 'meta_title' => 'Buy gadgets']);

        actingAsAdmin()->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Edit Category')
            ->assertSee('value="Gadgets"', escape: false)
            ->assertSee('value="Buy gadgets"', escape: false);
    });

    it('does not offer the edited category or subcategories as a parent', function () {
        $electrical = Category::factory()->create(['name' => 'Electrical']);
        Category::factory()->childOf($electrical)->create(['name' => 'Fans']);
        $fashion = Category::factory()->create(['name' => 'Fashion']);

        actingAsAdmin()->get(route('admin.categories.edit', $fashion))
            ->assertSee('value="'.$electrical->id.'"', escape: false)
            ->assertSee('>Electrical</option>', escape: false)
            ->assertDontSee('>Fashion</option>', escape: false)
            ->assertDontSee('>Fans</option>', escape: false);
    });
});

describe('create', function () {
    it('creates a top-level category and generates the slug', function () {
        actingAsStaff()->post(route('admin.categories.store'), categoryInput([
            'name' => 'Home & Kitchen',
            'sort_order' => 3,
            'meta_title' => 'Kitchen items',
        ]))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $category = Category::query()->sole();
        expect($category->slug)->toBe('home-kitchen')
            ->and($category->parent_id)->toBeNull()
            ->and($category->sort_order)->toBe(3)
            ->and($category->is_active)->toBeTrue()
            ->and($category->meta_title)->toBe('Kitchen items');
    });

    it('creates a subcategory', function () {
        $parent = Category::factory()->create();

        actingAsAdmin()->post(route('admin.categories.store'), categoryInput(['name' => 'Fans', 'parent_id' => $parent->id]));

        expect(Category::query()->where('slug', 'fans')->sole()->parent_id)->toBe($parent->id);
    });

    it('creates an inactive category', function () {
        actingAsAdmin()->post(route('admin.categories.store'), categoryInput(['is_active' => '0']));

        expect(Category::query()->sole()->is_active)->toBeFalse();
    });

    it('adds a number to a generated slug that is taken', function () {
        Category::factory()->create(['slug' => 'fans']);
        Category::factory()->create(['slug' => 'fans-2']);

        actingAsAdmin()->post(route('admin.categories.store'), categoryInput(['name' => 'Fans']));

        expect(Category::query()->latest('id')->first()->slug)->toBe('fans-3');
    });

    it('lowercases a typed slug', function () {
        actingAsAdmin()->post(route('admin.categories.store'), categoryInput(['slug' => 'LED-Bulbs']));

        expect(Category::query()->sole()->slug)->toBe('led-bulbs');
    });

    it('stores an uploaded image', function () {
        actingAsAdmin()->post(route('admin.categories.store'), categoryInput([
            'image' => UploadedFile::fake()->image('fan.png', 200, 200),
        ]));

        $category = Category::query()->sole();
        expect($category->image)->toStartWith('categories/');
        Storage::disk('public')->assertExists($category->image);
    });

    it('validates the input', function (array $input, string $errorField) {
        Category::factory()->create(['slug' => 'taken']);

        actingAsAdmin()->post(route('admin.categories.store'), categoryInput($input))
            ->assertSessionHasErrors($errorField);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'name too long' => [['name' => str_repeat('a', 101)], 'name'],
        'slug taken' => [['slug' => 'taken'], 'slug'],
        'slug with spaces' => [['slug' => 'led bulbs'], 'slug'],
        'unknown parent' => [['parent_id' => 999], 'parent_id'],
        'negative sort order' => [['sort_order' => -1], 'sort_order'],
        'svg image' => [['image' => UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml')], 'image'],
        'pdf as image' => [['image' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf')], 'image'],
        'image too large' => [['image' => UploadedFile::fake()->image('big.png')->size(1025)], 'image'],
    ]);

    it('rejects a subcategory as parent (two levels only)', function () {
        $child = Category::factory()->childOf()->create();

        actingAsAdmin()->post(route('admin.categories.store'), categoryInput(['parent_id' => $child->id]))
            ->assertSessionHasErrors(['parent_id' => 'Choose a top-level category as the parent.']);
    });
});

describe('update', function () {
    it('updates a category and keeps its slug when the name is unchanged', function () {
        $category = Category::factory()->create(['name' => 'Fans', 'slug' => 'ceiling-fans']);

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput([
            'name' => 'Fans',
            'sort_order' => 5,
            'is_active' => '0',
        ]))->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        expect($category->slug)->toBe('ceiling-fans')
            ->and($category->sort_order)->toBe(5)
            ->and($category->is_active)->toBeFalse();
    });

    it('generates a new slug when the name changes and the slug is empty', function () {
        $category = Category::factory()->create(['name' => 'Fans', 'slug' => 'fans']);

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput(['name' => 'Table Fans']));

        expect($category->fresh()->slug)->toBe('table-fans');
    });

    it('allows keeping its own slug', function () {
        $category = Category::factory()->create(['slug' => 'fans']);

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput(['name' => 'Fans', 'slug' => 'fans']))
            ->assertSessionHasNoErrors();
    });

    it('rejects making a category its own parent', function () {
        $category = Category::factory()->create();

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput(['parent_id' => $category->id]))
            ->assertSessionHasErrors(['parent_id' => 'A category cannot be its own parent.']);
    });

    it('rejects moving a category with subcategories under another category', function () {
        $electrical = Category::factory()->create();
        Category::factory()->childOf($electrical)->create();
        $fashion = Category::factory()->create();

        actingAsAdmin()->put(route('admin.categories.update', $electrical), categoryInput(['parent_id' => $fashion->id]))
            ->assertSessionHasErrors(['parent_id' => 'A category with subcategories must stay at the top level.']);

        expect($electrical->fresh()->parent_id)->toBeNull();
    });

    it('replaces the image and deletes the old file', function () {
        Storage::disk('public')->put('categories/old.png', 'old');
        $category = Category::factory()->withImage('categories/old.png')->create();

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput([
            'name' => $category->name,
            'image' => UploadedFile::fake()->image('new.png'),
        ]));

        $category->refresh();
        expect($category->image)->not->toBe('categories/old.png');
        Storage::disk('public')->assertMissing('categories/old.png');
        Storage::disk('public')->assertExists($category->image);
    });

    it('removes the image', function () {
        Storage::disk('public')->put('categories/old.png', 'old');
        $category = Category::factory()->withImage('categories/old.png')->create();

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput([
            'name' => $category->name,
            'remove_image' => '1',
        ]));

        expect($category->fresh()->image)->toBeNull();
        Storage::disk('public')->assertMissing('categories/old.png');
    });

    it('keeps the image when nothing about it changes', function () {
        Storage::disk('public')->put('categories/old.png', 'old');
        $category = Category::factory()->withImage('categories/old.png')->create();

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryInput(['name' => 'Renamed']));

        expect($category->fresh()->image)->toBe('categories/old.png');
        Storage::disk('public')->assertExists('categories/old.png');
    });
});

describe('delete', function () {
    it('deletes a category and its image', function () {
        Storage::disk('public')->put('categories/fans.png', 'image');
        $category = Category::factory()->withImage('categories/fans.png')->create();

        actingAsAdmin()->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
        Storage::disk('public')->assertMissing('categories/fans.png');
    });

    it('deletes a subcategory', function () {
        $child = Category::factory()->childOf()->create();

        actingAsAdmin()->delete(route('admin.categories.destroy', $child));

        $this->assertModelMissing($child);
    });

    it('does not delete a category with subcategories', function () {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();

        actingAsAdmin()->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', 'Delete or move its subcategories first.');

        $this->assertModelExists($parent);
    });
});

describe('authorization', function () {
    it('keeps guests out', function (string $method, string $routeName) {
        $category = Category::factory()->create();

        $this->{$method}(route($routeName, $category))->assertRedirect(route('admin.login'));
    })->with([
        ['get', 'admin.categories.index'],
        ['get', 'admin.categories.edit'],
        ['post', 'admin.categories.store'],
        ['put', 'admin.categories.update'],
        ['delete', 'admin.categories.destroy'],
    ]);

    it('forbids customers', function (string $method, string $routeName) {
        $category = Category::factory()->create();

        actingAsCustomer()->{$method}(route($routeName, $category))->assertForbidden();
    })->with([
        ['get', 'admin.categories.index'],
        ['post', 'admin.categories.store'],
        ['delete', 'admin.categories.destroy'],
    ]);
});
