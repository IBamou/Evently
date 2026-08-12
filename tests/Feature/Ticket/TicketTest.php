<?php

use App\Enums\BookingStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Ticket;
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
});

test('unique code generated', function () {
    $codes = collect();
    for ($i = 0; $i < 50; $i++) {
        $codes->push(Ticket::generateCode());
    }
    $this->assertEquals(50, $codes->unique()->count());
    foreach ($codes as $code) {
        $this->assertMatchesRegularExpression('/^T-[A-Z0-9]{10}$/', $code);
    }
});

test('user views own tickets grouped by event', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index'));
    $response->assertOk();
    $response->assertViewIs('tickets.index');

    // Contract: view receives eventGroups, counts, status.
    $eventGroups = $response->viewData('eventGroups');
    $counts = $response->viewData('counts');
    $status = $response->viewData('status');

    $this->assertNull($status);
    $this->assertCount(1, $eventGroups);
    $this->assertEquals(2, $eventGroups[0]['total']);
    $this->assertEquals(2, $eventGroups[0]['valid']);
    $this->assertEquals($this->event->id, $eventGroups[0]['event']->id);

    // Counts are unfiltered totals.
    $this->assertEquals(2, $counts['all']);
    $this->assertEquals(2, $counts['valid']);
    $this->assertEquals(0, $counts['used']);
    $this->assertEquals(0, $counts['cancelled']);
});

test('tickets grouped by event with multiple events', function () {
    $event2 = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(20),
    ]);
    $type2 = TicketType::create([
        'event_id' => $event2->id,
        'name' => 'GA',
        'price' => 0,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    // Book event 1 (2 tickets)
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    // Book event 2 (1 ticket) — free type so tickets are generated immediately.
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $event2->id,
        'items' => [['ticket_type_id' => $type2->id, 'quantity' => 1]],
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index'));
    $response->assertOk();

    $eventGroups = $response->viewData('eventGroups');
    $this->assertCount(2, $eventGroups);

    // First group: event1 (sooner starts_at), 2 tickets
    $this->assertEquals($this->event->id, $eventGroups[0]['event']->id);
    $this->assertEquals(2, $eventGroups[0]['total']);

    // Second group: event2 (later starts_at), 1 ticket
    $this->assertEquals($event2->id, $eventGroups[1]['event']->id);
    $this->assertEquals(1, $eventGroups[1]['total']);

    // Counts reflect all tickets across both events.
    $counts = $response->viewData('counts');
    $this->assertEquals(3, $counts['all']);
    $this->assertEquals(3, $counts['valid']);
});

test('sort rule upcoming before past and soonest first among upcoming', function () {
    // Past event: starts 10 days ago — create tickets directly (past events aren't bookable).
    $pastEvent = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDays(10)->addHours(3),
    ]);
    $pastType = TicketType::create([
        'event_id' => $pastEvent->id,
        'name' => 'General',
        'price' => 0,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subMonth(),
        'sales_end_at' => now()->addMonth(),
    ]);

    // Upcoming event A: starts in 5 days (this->event starts in 14 days)
    $upcomingA = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(5),
    ]);
    $upcomingAType = TicketType::create([
        'event_id' => $upcomingA->id,
        'name' => 'Standard',
        'price' => 0,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    // Book upcoming events via normal booking flow (free types → immediate tickets).
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $upcomingA->id,
        'items' => [['ticket_type_id' => $upcomingAType->id, 'quantity' => 1]],
    ]);
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    // Create ticket for past event directly (can't book past events).
    $pastBooking = Booking::create([
        'reference' => Booking::generateReference(),
        'user_id' => $this->user->id,
        'event_id' => $pastEvent->id,
        'status' => BookingStatus::Confirmed,
        'subtotal' => 0,
        'fees' => 0,
        'total' => 0,
        'currency' => 'MAD',
        'confirmed_at' => now(),
    ]);
    $pastBookingItem = BookingItem::create([
        'booking_id' => $pastBooking->id,
        'ticket_type_id' => $pastType->id,
        'ticket_name' => $pastType->name,
        'unit_price' => 0,
        'quantity' => 1,
        'line_total' => 0,
    ]);
    Ticket::create([
        'booking_id' => $pastBooking->id,
        'booking_item_id' => $pastBookingItem->id,
        'ticket_type_id' => $pastType->id,
        'user_id' => $this->user->id,
        'event_id' => $pastEvent->id,
        'code' => Ticket::generateCode(),
        'status' => TicketStatus::Valid,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index'));
    $eventGroups = $response->viewData('eventGroups');

    $this->assertCount(3, $eventGroups);

    // Upcoming events first, ascending by starts_at: A (5 days) then event (14 days)
    $this->assertEquals($upcomingA->id, $eventGroups[0]['event']->id);
    $this->assertEquals($this->event->id, $eventGroups[1]['event']->id);

    // Past event last
    $this->assertEquals($pastEvent->id, $eventGroups[2]['event']->id);
});

test('status filter applies to groups and tickets but counts stay unfiltered', function () {
    // Book 2 tickets
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    // Cancel one ticket
    $ticket = Ticket::where('event_id', $this->event->id)->first();
    $ticket->update(['status' => TicketStatus::Cancelled, 'cancelled_at' => now()]);

    // Request with ?status=valid — should show only the 1 valid ticket
    $response = $this->actingAs($this->user)->get(route('tickets.index', ['status' => 'valid']));
    $response->assertOk();

    $eventGroups = $response->viewData('eventGroups');
    $this->assertCount(1, $eventGroups);
    $this->assertEquals(1, $eventGroups[0]['total']);
    $this->assertEquals(1, $eventGroups[0]['valid']);

    // Counts are still unfiltered (both tickets).
    $counts = $response->viewData('counts');
    $this->assertEquals(2, $counts['all']);
    $this->assertEquals(1, $counts['valid']);
    $this->assertEquals(0, $counts['used']);
    $this->assertEquals(1, $counts['cancelled']);

    // Status filter value is passed through.
    $this->assertEquals('valid', $response->viewData('status'));
});

test('invalid status parameter is ignored silently', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index', ['status' => 'INVALID']));
    $response->assertOk();

    // All tickets shown (no filter applied).
    $eventGroups = $response->viewData('eventGroups');
    $this->assertCount(1, $eventGroups);
    $this->assertEquals(2, $eventGroups[0]['total']);

    // Status is null (invalid value ignored).
    $this->assertNull($response->viewData('status'));

    // Also test with a non-existent but valid-looking string.
    $response2 = $this->actingAs($this->user)->get(route('tickets.index', ['status' => 'active']));
    $response2->assertOk();
    $this->assertNull($response2->viewData('status'));
    $this->assertCount(1, $response2->viewData('eventGroups'));
});

test('tickets sorted valid first within group then by created_at ascending', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 3]],
    ]);

    // Mark first ticket as used, second as cancelled — third stays valid.
    $tickets = Ticket::where('event_id', $this->event->id)
        ->orderBy('created_at')
        ->get();
    $tickets[0]->update(['status' => TicketStatus::Used, 'checked_in_at' => now()]);
    $tickets[1]->update(['status' => TicketStatus::Cancelled, 'cancelled_at' => now()]);

    $response = $this->actingAs($this->user)->get(route('tickets.index'));
    $eventGroups = $response->viewData('eventGroups');

    $groupTickets = $eventGroups[0]['tickets'];

    // Sort order: valid first, then by created_at ascending (stable sort).
    // tickets[2] (valid) → sort key [0, ...] — comes first.
    // tickets[0] (used, oldest) → sort key [1, ...] — comes second.
    // tickets[1] (cancelled, newer) → sort key [1, ...] — comes third.
    $this->assertCount(3, $groupTickets);
    $this->assertEquals(TicketStatus::Valid, $groupTickets[0]->status);
    $this->assertEquals(TicketStatus::Used, $groupTickets[1]->status);
    $this->assertEquals(TicketStatus::Cancelled, $groupTickets[2]->status);
});

test('organizer views event attendees', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 2]],
    ]);

    $response = $this->actingAs($this->organizer)->get(route('organizer.bookings.index', $this->event));
    $response->assertOk();

    $attendees = $response->viewData('attendees');
    $this->assertCount(2, $attendees);
});

test('used ticket cannot be cancelled via cancel endpoint', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $ticket = $booking->tickets()->first();

    $ticket->update(['status' => TicketStatus::Used, 'checked_in_at' => now()]);
    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));
    $booking->refresh();
    $this->assertEquals(BookingStatus::Confirmed, $booking->status);
});

test('admin views all tickets', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $response = $this->actingAs($admin)->get(route('admin.tickets.index'));
    $response->assertOk();

    $tickets = $response->viewData('tickets');
    $this->assertGreaterThanOrEqual(1, $tickets->count());
});
