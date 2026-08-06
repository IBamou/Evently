<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organizer = User::factory()->asOrganizer()->create();
    $this->event = Event::factory()->published()->create(['organizer_id' => $this->organizer->id]);
});

// ── Store invariants ──

test('store: min > max rejected', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'Bad Min',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 15,
        'max_per_booking' => 10,
    ]);

    $response->assertSessionHasErrors('min_per_booking');
});

test('store: min > quantity rejected', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'Min Exceeds Qty',
        'price' => 100,
        'quantity' => 5,
        'min_per_booking' => 10,
        'max_per_booking' => 20,
    ]);

    $response->assertSessionHasErrors('min_per_booking');
});

test('store: valid values accepted', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'Valid Ticket',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ticket_types', [
        'event_id' => $this->event->id,
        'name' => 'Valid Ticket',
    ]);
});

// ── Update invariants (effective state) ──

test('update: lowering max below existing min rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Invariant TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 5,
        'max_per_booking' => 20,
        'currency' => 'MAD',
    ]);

    // Set min higher than new max
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'min_per_booking' => 10,
        'max_per_booking' => 5,
    ]);

    $response->assertSessionHasErrors('min_per_booking');
});

test('update: raising min above existing max rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Invariant TT 2',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
    ]);

    // Raise min above existing max
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'min_per_booking' => 15,
    ]);

    $response->assertSessionHasErrors('min_per_booking');
});

test('update: lowering max below existing min via effective state rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Effective State TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 8,
        'max_per_booking' => 20,
        'currency' => 'MAD',
    ]);

    // Lower max below existing min (8)
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'max_per_booking' => 3,
    ]);

    $response->assertSessionHasErrors('min_per_booking');
});

test('update: raising min above existing max via effective state rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Effective State TT 2',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
    ]);

    // Raise min above existing max (5)
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'min_per_booking' => 10,
    ]);

    $response->assertSessionHasErrors('min_per_booking');
});

// ── Sales window invariants ──

test('update: sales_start after existing sales_end rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Sales Window TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'sales_start_at' => now()->addDay(),
        'sales_end_at' => now()->addDays(5),
    ]);

    // Move sales_start after existing sales_end
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'sales_start_at' => now()->addDays(10)->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('sales_end_at');
});

test('update: sales_end before existing sales_start rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Sales Window TT 2',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'sales_start_at' => now()->addDays(3),
        'sales_end_at' => now()->addDays(10),
    ]);

    // Move sales_end before existing sales_start
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'sales_end_at' => now()->addDays(1)->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('sales_end_at');
});

test('update: sales_end >= event.starts_at rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Sales Event TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'sales_start_at' => now(),
        'sales_end_at' => now()->addDays(5),
    ]);

    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'sales_end_at' => $this->event->starts_at->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('sales_end_at');
});

test('update: valid partial updates still allowed', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Partial OK TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
    ]);

    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'name' => 'Renamed',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ticket_types', ['id' => $tt->id, 'name' => 'Renamed', 'min_per_booking' => 1]);
});

// ── Allocated quantity protection ──

test('update: lowering quantity below allocated rejected', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Allocated TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
    ]);

    // Create a booking with 5 allocated tickets
    $booking = Booking::factory()->confirmed()->create([
        'event_id' => $this->event->id,
    ]);
    BookingItem::create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $tt->id,
        'ticket_name' => $tt->name,
        'unit_price' => 100,
        'quantity' => 5,
        'line_total' => 500,
    ]);

    // Try to reduce quantity below 5
    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'quantity' => 3,
    ]);

    $response->assertSessionHasErrors('quantity');
});

// ── Price lock ──

test('update: price lock still enforced', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Locked Price TT',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
    ]);

    $booking = Booking::factory()->confirmed()->create([
        'event_id' => $this->event->id,
    ]);
    BookingItem::create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $tt->id,
        'ticket_name' => $tt->name,
        'unit_price' => 100,
        'quantity' => 2,
        'line_total' => 200,
    ]);

    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'price' => 200,
    ]);

    $response->assertSessionHasErrors('price');
});
