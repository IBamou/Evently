<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->word().' ticket',
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 0, 500),
            'currency' => 'MAD',
            'quantity' => fake()->numberBetween(50, 500),
            'min_per_booking' => 1,
            'max_per_booking' => 10,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(['price' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'sales_start_at' => now()->addDays(5),
            'sales_end_at' => now()->addMonth(),
        ]);
    }
}
