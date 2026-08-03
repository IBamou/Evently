<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItem>
 */
class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 200);

        return [
            'booking_id' => Booking::factory(),
            'ticket_type_id' => TicketType::factory(),
            'ticket_name' => fake()->word().' ticket',
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ];
    }

    public function forPendingBooking(): static
    {
        return $this->state([
            'booking_id' => Booking::factory()->pending(),
        ]);
    }

    public function forConfirmedBooking(): static
    {
        return $this->state([
            'booking_id' => Booking::factory()->confirmed(),
        ]);
    }
}
