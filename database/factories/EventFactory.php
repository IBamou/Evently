<?php

namespace Database\Factories;

use App\Enums\EventFormat;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(10);

        return [
            'organizer_id' => User::factory()->asOrganizer(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'slug' => Str::slug(fake()->unique()->sentence(3)),
            'description' => fake()->paragraphs(3, true),
            'location' => fake()->streetAddress(),
            'city' => fake()->city(),
            'format' => EventFormat::InPerson,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'status' => EventStatus::Draft,
            'banner_url' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => EventStatus::Published]);
    }

    public function underReview(): static
    {
        return $this->state(['status' => EventStatus::UnderReview]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => EventStatus::Cancelled]);
    }

    public function started(): static
    {
        return $this->state([
            'starts_at' => now()->subHours(1),
            'ends_at' => now()->addHours(2),
        ]);
    }

    public function past(): static
    {
        return $this->state([
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(5)->addHours(3),
        ]);
    }
}
