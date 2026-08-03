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

test('organizer creates ticket type', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'General Admission',
        'description' => 'Standard entry',
        'price' => 150,
        'quantity' => 200,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ticket_types', [
        'event_id' => $this->event->id,
        'name' => 'General Admission',
        'price' => 150,
    ]);
});

test('duplicate name rejected', function () {
    $this->actingAs($this->organizer);

    TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'VIP',
        'price' => 300,
        'quantity' => 50,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
    ]);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'VIP',
        'price' => 200,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
    ]);

    $response->assertSessionHasErrors('name');
});

test('negative price rejected', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'Bad Price',
        'price' => -10,
        'quantity' => 50,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
    ]);

    $response->assertSessionHasErrors('price');
});

test('invalid quantity bounds rejected', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'Bad Qty',
        'price' => 100,
        'quantity' => 0,
        'min_per_booking' => 0,
        'max_per_booking' => 0,
    ]);

    $response->assertSessionHasErrors(['quantity', 'min_per_booking']);
});

test('invalid sales dates rejected', function () {
    $this->actingAs($this->organizer);

    $response = $this->post(route('organizer.ticket-types.store', $this->event), [
        'name' => 'Bad Dates',
        'price' => 100,
        'quantity' => 50,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'sales_start_at' => now()->addDays(20),
        'sales_end_at' => now()->addDays(10),
    ]);

    $response->assertSessionHasErrors('sales_end_at');
});

test('organizer updates ticket type', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Update Me',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
    ]);

    $response = $this->put(route('organizer.ticket-types.update', [$this->event, $tt]), [
        'name' => 'Updated Name',
        'price' => 150,
        'quantity' => 200,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ticket_types', ['id' => $tt->id, 'name' => 'Updated Name', 'price' => 150]);
});

test('cannot change price with bookings', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Locked Price',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
    ]);

    // Create a booking item to lock the price
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

test('activate and deactivate ticket type', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Toggle Me',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
    ]);

    $this->post(route('organizer.ticket-types.deactivate', [$this->event, $tt]));
    $this->assertDatabaseHas('ticket_types', ['id' => $tt->id, 'is_active' => false]);

    $this->post(route('organizer.ticket-types.activate', [$this->event, $tt]));
    $this->assertDatabaseHas('ticket_types', ['id' => $tt->id, 'is_active' => true]);
});

test('public sees only active ticket types', function () {
    $active = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Active',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
    ]);

    TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Inactive',
        'price' => 100,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => false,
    ]);

    $response = $this->get(route('events.show', $this->event->slug));
    $response->assertOk();

    $ticketTypes = $response->viewData('ticketTypes');
    $this->assertCount(1, $ticketTypes);
    $this->assertEquals('Active', $ticketTypes->first()['name']);
});

test('cannot delete ticket type with bookings', function () {
    $this->actingAs($this->organizer);

    $tt = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Has Bookings',
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

    $response = $this->delete(route('organizer.ticket-types.destroy', [$this->event, $tt]));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('ticket_types', ['id' => $tt->id, 'deleted_at' => null]);
});

test('organizer cannot create for other event', function () {
    $this->actingAs($this->organizer);

    $otherEvent = Event::factory()->published()->create();

    $response = $this->post(route('organizer.ticket-types.store', $otherEvent), [
        'name' => 'Sneaky',
        'price' => 100,
        'quantity' => 50,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
    ]);

    $response->assertStatus(403);
});
