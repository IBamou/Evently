<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'reference' => Booking::generateReference(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'status' => BookingStatus::Pending,
            'subtotal' => fake()->randomFloat(2, 0, 500),
            'fees' => 0,
            'total' => fake()->randomFloat(2, 0, 500),
            'currency' => 'MAD',
            'expires_at' => now()->addMinutes(15),
            'confirmed_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => BookingStatus::Confirmed,
            'expires_at' => null,
            'confirmed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => BookingStatus::Pending,
            'confirmed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => BookingStatus::Expired,
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function free(): static
    {
        return $this->state([
            'subtotal' => 0,
            'fees' => 0,
            'total' => 0,
            'status' => BookingStatus::Confirmed,
            'expires_at' => null,
            'confirmed_at' => now(),
        ]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(['event_id' => $event->id]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
