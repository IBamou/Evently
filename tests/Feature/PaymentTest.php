<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organizer = User::factory()->asOrganizer()->create();
    $this->event = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(14),
    ]);
    $this->paidType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'General',
        'price' => 200,
        'quantity' => 50,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);
});

test('free booking no payment', function () {
    $freeType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Free',
        'price' => 0,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $freeType->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertDatabaseCount('payments', 0);
});

test('paid booking creates pending payment', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => PaymentStatus::Pending->value,
        'amount' => 400,
        'provider' => 'manual',
    ]);
});

test('user confirms payment', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();

    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $booking));

    $booking->refresh();
    $this->assertEquals(BookingStatus::Confirmed, $booking->status);
    $this->assertNotNull($booking->confirmed_at);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => PaymentStatus::Succeeded->value,
    ]);

    $this->assertDatabaseCount('tickets', 2);
});

test('payment confirmation idempotent', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();

    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $booking));
    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $booking));

    $this->assertDatabaseCount('tickets', 2);
    $this->assertDatabaseCount('payments', 1);
});

test('amount matches booking total', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 3]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $payment = Payment::where('booking_id', $booking->id)->first();

    $this->assertEquals($booking->total, $payment->amount);
});

test('free booking instant confirm creates tickets', function () {
    $freeType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Free',
        'price' => 0,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $freeType->id, 'quantity' => 3]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertCount(3, $booking->tickets);
    foreach ($booking->tickets as $ticket) {
        $this->assertMatchesRegularExpression('/^T-[A-Z0-9]{10}$/', $ticket->code);
        $this->assertEquals(TicketStatus::Valid, $ticket->status);
    }
});
