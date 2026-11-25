<?php

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Support\Locale;
use App\Support\Money;
use App\Support\Numerals;

it('shows the storefront in Bangla when the visitor has chosen it', function () {
    $this->withCookie(Locale::COOKIE_NAME, 'bn')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="bn">', escape: false)
        ->assertSee('সারা দেশে ক্যাশ অন ডেলিভারি সুবিধা');
});

it('keeps English when the visitor has chosen it', function () {
    $this->withCookie(Locale::COOKIE_NAME, 'en')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Cash on Delivery Available Nationwide');
});

it('stores the chosen language in a cookie and goes back', function () {
    $this->from(route('shop'))
        ->post(route('locale.update', 'en'))
        ->assertRedirect(route('shop'))
        ->assertCookie(Locale::COOKIE_NAME, 'en');
});

it('rejects an unknown language', function () {
    $this->post(route('locale.update', 'fr'))->assertNotFound();
});

it('offers the other language in the switcher', function () {
    $this->withCookie(Locale::COOKIE_NAME, 'bn')
        ->get(route('home'))
        ->assertSee(route('locale.update', 'en'), escape: false);
});

it('uses Bangla digits only while the page is in Bangla', function () {
    app()->setLocale('bn');
    expect(Numerals::localize('৳ 4,850'))->toBe('৳ ৪,৮৫০')
        ->and(Money::format(485000))->toBe('৳ ৪,৮৫০');

    app()->setLocale('en');
    expect(Numerals::localize('4,850'))->toBe('4,850')
        ->and(Money::format(485000))->toBe('৳ 4,850');
});

it('turns Bangla digits typed into a form back into ASCII', function () {
    expect(Numerals::toAscii('০১৭১২৩৪৫৬৭৮'))->toBe('01712345678');
});

it('shows the admin panel in the chosen language too', function () {
    $this->withCookie(Locale::COOKIE_NAME, 'bn')
        ->get(route('admin.login'))
        ->assertOk()
        ->assertSee('<html lang="bn">', escape: false);

    actingAsAdmin()->withCookie(Locale::COOKIE_NAME, 'bn')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('ড্যাশবোর্ড');
});

it('shows Bangla product and category names in Bangla and English otherwise', function () {
    $category = Category::factory()->create(['name' => 'Honey', 'name_bn' => 'মধু']);
    $product = Product::factory()->active()->withDefaultVariant()->create([
        'name' => 'Sundarban Honey',
        'name_bn' => 'সুন্দরবনের মধু',
        'category_id' => $category->id,
    ]);

    app()->setLocale('bn');
    expect($product->fresh()->name)->toBe('সুন্দরবনের মধু')
        ->and($category->fresh()->name)->toBe('মধু');

    app()->setLocale('en');
    expect($product->fresh()->name)->toBe('Sundarban Honey')
        ->and($category->fresh()->name)->toBe('Honey');
});

it('falls back to the English text when there is no Bangla text', function () {
    $category = Category::factory()->create(['name' => 'Plants', 'name_bn' => null]);

    app()->setLocale('bn');

    expect($category->fresh()->name)->toBe('Plants');
});
