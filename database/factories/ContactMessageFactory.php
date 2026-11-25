<?php

namespace Database\Factories;

use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Enums\ContactSubject;
use App\Modules\Content\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
#[UseModel(ContactMessage::class)]
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'phone' => '017'.fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'subject' => fake()->randomElement(ContactSubject::cases()),
            'message' => fake()->paragraph(),
            'status' => ContactMessageStatus::New,
        ];
    }

    public function replied(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ContactMessageStatus::Replied]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ContactMessageStatus::Closed]);
    }
}
