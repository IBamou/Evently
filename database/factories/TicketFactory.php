<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'booking_item_id' => BookingItem::factory(),
            'ticket_type_id' => TicketType::factory(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'code' => Ticket::generateCode(),
            'status' => TicketStatus::Valid,
            'issued_at' => now(),
            'checked_in_at' => null,
            'checked_in_by' => null,
            'cancelled_at' => null,
        ];
    }

    public function used(): static
    {
        return $this->state([
            'status' => TicketStatus::Used,
            'checked_in_at' => now(),
            'checked_in_by' => User::factory()->asOrganizer(),
        ]);
    }

    public function valid(): static
    {
        return $this->state(['status' => TicketStatus::Valid]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => TicketStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
