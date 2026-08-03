<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'provider' => 'manual',
            'provider_reference' => null,
            'status' => PaymentStatus::Pending,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'MAD',
            'paid_at' => null,
            'metadata' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state([
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => PaymentStatus::Cancelled,
        ]);
    }
}
