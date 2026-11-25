<?php

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Content\Data\StoreSettings;
use App\Modules\Content\Services\SettingService;

function saveSettings(array $overrides = []): void
{
    $current = app(SettingService::class)->current();

    app(SettingService::class)->save(new StoreSettings(...[
        'storeName' => $current->storeName,
        'supportPhone' => $current->supportPhone,
        'supportEmail' => $current->supportEmail,
        'storeAddress' => $current->storeAddress,
        'supportHours' => $current->supportHours,
        'freeDeliveryThreshold' => $current->freeDeliveryThreshold,
        'lowStockThreshold' => $current->lowStockThreshold,
        ...$overrides,
    ]));
}

it('builds the menus from active categories in sort order', function () {
    $electrical = Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion', 'sort_order' => 2]);
    Category::factory()->inactive()->create(['name' => 'Hidden Dept', 'slug' => 'hidden-dept']);
    Category::factory()->childOf($electrical)->create(['name' => 'Fans', 'slug' => 'fans']);
    Category::factory()->childOf($electrical)->inactive()->create(['name' => 'Secret Sub', 'slug' => 'secret-sub']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Electrical', 'Fans', 'Fashion'])
        ->assertSee(route('category', 'fans'))
        ->assertSee(route('category', 'electrical'))
        ->assertDontSee('Hidden Dept')
        ->assertDontSee('Secret Sub');
});

it('shows at most five departments in the desktop nav', function () {
    Category::factory()->count(6)->sequence(fn ($sequence) => ['name' => "Dept {$sequence->index}", 'slug' => "dept-{$sequence->index}", 'sort_order' => $sequence->index])->create();

    $html = $this->get(route('home'))->getContent();
    $navList = substr($html, strpos($html, 'class="nav-list"'), 2000);

    expect($navList)->toContain('Dept 4')->not->toContain('Dept 5');
});

it('marks the current department active', function () {
    Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical']);

    $this->get(route('category', 'electrical'))
        ->assertSee('href="'.route('category', 'electrical').'" class="active"', escape: false);
});

it('shows store settings in the top bar and footer', function () {
    saveSettings([
        'storeName' => 'Nischoyota BD',
        'supportPhone' => '+880 1700-000000',
        'supportEmail' => 'help@nischoyota.test',
        'storeAddress' => 'Banani, Dhaka',
        'supportHours' => '10am – 8pm',
        'freeDeliveryThreshold' => 250000,
    ]);

    $this->get(route('home'))
        ->assertSee('Free Delivery on Orders Over')
        ->assertSee('৳ 2,500')
        ->assertSee('+880 1700-000000')
        ->assertSee('href="tel:+8801700000000"', escape: false)
        ->assertSee('help@nischoyota.test')
        ->assertSee('Banani, Dhaka')
        ->assertSee('(10am – 8pm)')
        ->assertSee('Nischoyota BD. All rights reserved.');
});

it('drops the free-delivery message when there is no threshold', function () {
    saveSettings(['freeDeliveryThreshold' => null]);

    $this->get(route('home'))->assertSee('Cash on Delivery Available Nationwide')->assertDontSee('Free Delivery on Orders Over');
});

it('shows Account for guests and the first name when signed in', function () {
    $this->get(route('home'))->assertSee('Login / Register');

    $this->actingAs(User::factory()->create(['name' => 'Rahim Uddin']))
        ->get(route('home'))
        ->assertSee('Rahim')
        ->assertSee('My Account')
        ->assertDontSee('Login / Register');
});

it('hides empty cart and wishlist badges', function () {
    $this->get(route('home'))
        ->assertDontSee('data-cart-count', escape: false)
        ->assertDontSee('data-wishlist-count', escape: false);
});

it('refreshes the cached menu when categories change', function () {
    $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
    $this->get(route('home'))->assertSee('Old Name');

    actingAsAdmin()->put(route('admin.categories.update', $category), ['name' => 'New Name', 'slug' => 'new-name', 'is_active' => '1', 'sort_order' => 0]);
    $this->get(route('home'))->assertSee('New Name')->assertDontSee('Old Name');

    actingAsAdmin()->delete(route('admin.categories.destroy', $category));
    $this->get(route('home'))->assertDontSee(route('category', 'new-name'));

    actingAsAdmin()->post(route('admin.categories.store'), ['name' => 'Gadgets', 'is_active' => '1', 'sort_order' => 0]);
    $this->get(route('home'))->assertSee('Gadgets');
});
