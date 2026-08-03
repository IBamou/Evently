<?php

use App\Actions\Events\CancelEventAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organizer = User::factory()->asOrganizer()->create();
    $this->admin = User::factory()->asAdmin()->create();
    $this->event = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(14),
    ]);
    $this->freeType = TicketType::create([
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

test('user cancels eligible booking', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $response = $this->actingAs($this->user)->post(route('bookings.cancel', $booking));

    $response->assertRedirect();
    $booking->refresh();
    $this->assertEquals(BookingStatus::Cancelled, $booking->status);
});

test('cancellation cancels valid tickets', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));

    $this->assertDatabaseHas('tickets', [
        'booking_id' => $booking->id,
        'status' => TicketStatus::Cancelled->value,
    ]);
});

test('used ticket prevents cancellation', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $ticket = $booking->tickets()->first();
    $ticket->update(['status' => TicketStatus::Used, 'checked_in_at' => now(), 'checked_in_by' => $this->organizer->id]);

    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));
    $booking->refresh();
    $this->assertEquals(BookingStatus::Confirmed, $booking->status);
});

test('event started prevents cancellation', function () {
    // Create upcoming event and booking, then simulate event having started
    $event = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addHours(1),
        'ends_at' => now()->addHours(4),
    ]);
    $tt = TicketType::create([
        'event_id' => $event->id,
        'name' => 'Free',
        'price' => 0,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
    ]);

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $event->id,
        'items' => [['ticket_type_id' => $tt->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $event->id)->first();

    // Simulate event having started
    $event->update(['starts_at' => now()->subHour()]);

    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));
    $booking->refresh();
    // Should NOT be cancelled because event has started
    $this->assertEquals(BookingStatus::Confirmed, $booking->status);
});

test('pending payment cancelled', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => 'cancelled',
    ]);
});

test('admin cancels any booking', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->actingAs($this->admin)->post(route('admin.bookings.cancel', $booking));

    $booking->refresh();
    $this->assertEquals(BookingStatus::Cancelled, $booking->status);
});

test('idempotent cancellation', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));
    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));

    $booking->refresh();
    $this->assertEquals(BookingStatus::Cancelled, $booking->status);
});

test('expiry command releases capacity', function () {
    // Create a pending paid booking
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 3]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $before = $this->paidType->availableQuantity();

    // Force the booking to be expired
    $booking->update([
        'expires_at' => now()->subHour(),
    ]);

    // Run the command
    $this->artisan('bookings:expire');

    $this->paidType->refresh();
    $this->assertEquals($before + 3, $this->paidType->availableQuantity());
});

test('event cancellation cascade', function () {
    // Create a confirmed booking
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();

    // Cancel event
    (new CancelEventAction)($this->event);

    $booking->refresh();
    $this->assertEquals(BookingStatus::Cancelled, $booking->status);

    $this->assertDatabaseHas('tickets', [
        'booking_id' => $booking->id,
        'status' => TicketStatus::Cancelled->value,
    ]);
});

test('event cancellation cancels pending payments of pending bookings', function () {
    // Create a pending paid booking (generates a pending payment).
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    expect($booking->status)->toBe(BookingStatus::Pending);

    // Cancel the event: pending bookings expire, their pending payments must
    // not stay orphaned.
    (new CancelEventAction)($this->event);

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Expired);
    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => PaymentStatus::Cancelled->value,
    ]);
});

test('user cannot cancel other booking', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)->post(route('bookings.cancel', $booking));

    $booking->refresh();
    $this->assertEquals(BookingStatus::Confirmed, $booking->status);
});
