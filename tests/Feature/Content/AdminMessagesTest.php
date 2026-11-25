<?php

use App\Modules\Account\Models\User;
use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Models\ContactMessage;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

it('opens on new messages with counts per tab', function () {
    ContactMessage::factory()->create(['name' => 'New Sender', 'message' => 'Where is my order?']);
    ContactMessage::factory()->replied()->create(['name' => 'Replied Sender']);
    ContactMessage::factory()->closed()->create();

    $this->actingAs($this->staff)->get(route('admin.messages.index'))
        ->assertOk()
        ->assertSee('New Sender')
        ->assertSee('Where is my order?')
        ->assertDontSee('Replied Sender')
        ->assertViewHas('tabCounts', ['new' => 1, 'replied' => 1, 'closed' => 1, 'all' => 3]);
});

it('shows all messages and searches them', function () {
    ContactMessage::factory()->replied()->create(['name' => 'Nusrat Jahan', 'phone' => '01812556677']);
    ContactMessage::factory()->create(['name' => 'Tanvir Ahmed', 'phone' => '01712998811']);

    $this->actingAs($this->staff)->get(route('admin.messages.index', ['status' => 'all']))
        ->assertSee('Nusrat Jahan')
        ->assertSee('Tanvir Ahmed');

    $this->actingAs($this->staff)->get(route('admin.messages.index', ['status' => 'all', 'search' => '01812']))
        ->assertSee('Nusrat Jahan')
        ->assertDontSee('Tanvir Ahmed');
});

it('shows a message with a link to the sender\'s account', function () {
    $customer = User::factory()->customer()->create();
    $message = ContactMessage::factory()->create(['user_id' => $customer->id, 'message' => "Line one\nLine two"]);

    $this->actingAs($this->staff)->get(route('admin.messages.show', $message))
        ->assertOk()
        ->assertSee("Line one\nLine two")
        ->assertSee(route('admin.customers.show', $customer->id))
        ->assertSee('Mark as Replied');
});

it('changes the status', function () {
    $message = ContactMessage::factory()->create();

    $this->actingAs($this->staff)->patch(route('admin.messages.status', $message), ['status' => 'replied'])
        ->assertRedirect(route('admin.messages.show', $message))
        ->assertSessionHas('success', 'Message marked as replied.');

    expect($message->fresh()->status)->toBe(ContactMessageStatus::Replied);

    $this->actingAs($this->staff)->patch(route('admin.messages.status', $message), ['status' => 'spam'])->assertSessionHasErrors('status');
});

it('shows the new-message count in the sidebar', function () {
    ContactMessage::factory()->count(2)->create();
    ContactMessage::factory()->closed()->create();

    $this->actingAs($this->staff)->get(route('admin.messages.index'))
        ->assertSeeInOrder(['Messages', '<span class="nav-count">2</span>'], escape: false);
});

it('keeps customers out', function () {
    $this->actingAs(User::factory()->customer()->create())->get(route('admin.messages.index'))->assertForbidden();
});
