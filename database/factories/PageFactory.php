<?php

namespace Database\Factories;

use App\Modules\Content\Models\Page;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
#[UseModel(Page::class)]
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'subtitle' => fake()->sentence(),
            'body' => "## Heading\n\n".fake()->paragraph(),
            'is_published' => true,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => ['is_system' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
