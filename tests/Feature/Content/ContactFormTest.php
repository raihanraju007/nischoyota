<?php

use App\Modules\Account\Models\User;
use App\Modules\Content\Data\StoreSettings;
use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Enums\ContactSubject;
use App\Modules\Content\Models\ContactMessage;
use App\Modules\Content\Services\SettingService;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactForm(array $overrides = []): array
{
    return [
        'name' => 'Rahim Uddin',
        'phone' => '+880 1712-345678',
        'email' => 'Rahim@Example.com',
        'subject' => 'order_issue',
        'message' => 'My order has not arrived yet.',
        ...$overrides,
    ];
}

it('shows the contact channels from the store settings', function () {
    app(SettingService::class)->save(new StoreSettings(
        storeName: 'Nischoyota',
        supportPhone: '01999-000111',
        supportEmail: 'help@example.com',
        storeAddress: null,
        supportHours: 'Daily 10–6',
        freeDeliveryThreshold: null,
        lowStockThreshold: 5,
    ));

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('01999-000111')
        ->assertSee('help@example.com')
        ->assertSee('Daily 10–6')
        ->assertDontSee('<strong>Address</strong>', escape: false)
        ->assertSee('Product Question');
});

it('saves a message with a normalised phone', function () {
    $this->post(route('contact.store'), contactForm())
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success', "Message sent! We'll get back to you within 24 hours.");

    $message = ContactMessage::query()->sole();

    expect($message->only(['user_id', 'name', 'phone', 'email', 'message']))->toBe([
        'user_id' => null,
        'name' => 'Rahim Uddin',
        'phone' => '01712345678',
        'email' => 'rahim@example.com',
        'message' => 'My order has not arrived yet.',
    ])
        ->and($message->subject)->toBe(ContactSubject::OrderIssue)
        ->and($message->status)->toBe(ContactMessageStatus::New);
});

it('pre-fills the form and links the message for a signed-in customer', function () {
    $customer = User::factory()->customer()->create(['name' => 'Fatema Akter', 'phone' => '01812345678', 'email' => 'fatema@example.com']);

    $this->actingAs($customer)->get(route('contact'))
        ->assertSee('value="Fatema Akter"', escape: false)
        ->assertSee('value="01812345678"', escape: false)
        ->assertSee('value="fatema@example.com"', escape: false);

    $this->actingAs($customer)->post(route('contact.store'), contactForm(['email' => '']))->assertRedirect(route('contact'));

    expect(ContactMessage::query()->sole())
        ->user_id->toBe($customer->id)
        ->email->toBeNull();
});

it('validates the message', function (array $input, string $field) {
    $this->from(route('contact'))->post(route('contact.store'), contactForm($input))
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors($field);

    expect(ContactMessage::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid phone' => [['phone' => '12345'], 'phone'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'unknown subject' => [['subject' => 'refund'], 'subject'],
    'message too short' => [['message' => 'Hi'], 'message'],
    'message too long' => [['message' => str_repeat('a', 2001)], 'message'],
]);

it('pretends to accept a honeypot submission without saving it', function () {
    $this->post(route('contact.store'), contactForm(['website' => 'https://spam.example']))
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success');

    expect(ContactMessage::query()->count())->toBe(0);
});

it('allows 5 messages per 10 minutes', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.store'), contactForm())->assertRedirect(route('contact'));
    }

    $this->post(route('contact.store'), contactForm())->assertTooManyRequests();
    expect(ContactMessage::query()->count())->toBe(5);

    $this->travel(11)->minutes();
    $this->post(route('contact.store'), contactForm())->assertRedirect(route('contact'));
});
