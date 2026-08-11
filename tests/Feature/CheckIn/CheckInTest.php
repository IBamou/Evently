<?php

use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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
        'name' => 'General',
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

test('valid check-in', function () {
    // Create a booking with a confirmed ticket
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $ticket = Ticket::where('event_id', $this->event->id)->first();

    $response = $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    $response->assertOk();
    $response->assertJson([
        'valid' => true,
        'result' => 'checked_in',
    ]);

    $ticket->refresh();
    $this->assertEquals(TicketStatus::Used, $ticket->status);
    $this->assertNotNull($ticket->checked_in_at);
});

test('ticket not found', function () {
    $response = $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => 'T-NONEXISTENT',
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'valid' => false,
        'result' => 'not_found',
    ]);
});

test('cancelled ticket rejected', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $ticket = Ticket::where('event_id', $this->event->id)->first();
    $ticket->update(['status' => TicketStatus::Cancelled, 'cancelled_at' => now()]);

    $response = $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'valid' => false,
        'result' => 'cancelled',
    ]);
});

test('already used ticket', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $ticket = Ticket::where('event_id', $this->event->id)->first();

    // First check-in
    $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    // Second check-in
    $response = $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'valid' => false,
        'result' => 'already_used',
    ]);
});

test('event cancelled prevents check-in', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $ticket = Ticket::where('event_id', $this->event->id)->first();

    // Cancel event (status is not mass-assignable, use forceFill)
    $this->event->forceFill(['status' => EventStatus::Cancelled])->save();
    $this->event->refresh();

    $response = $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'valid' => false,
        'result' => 'event_cancelled',
    ]);
});

test('double-scan race protection', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $ticket = Ticket::where('event_id', $this->event->id)->first();

    // Simulate race: manually set status before the request
    DB::table('tickets')
        ->where('id', $ticket->id)
        ->update(['status' => TicketStatus::Used->value]);

    // Now try to check in — should fail
    $response = $this->actingAs($this->organizer)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'valid' => false,
        'result' => 'already_used',
    ]);
});

test('admin can view the check-in page for any event', function () {
    $admin = User::factory()->asAdmin()->create();

    $response = $this->actingAs($admin)->get(route('organizer.check-in.index', $this->event));

    $response->assertOk();
});

test('admin can scan a ticket at the door', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->freeType->id, 'quantity' => 1]],
    ]);

    $ticket = Ticket::where('event_id', $this->event->id)->first();

    $response = $this->actingAs($admin)->postJson(route('organizer.check-in.scan', $this->event), [
        'code' => $ticket->code,
    ]);

    $response->assertOk();
    $response->assertJson([
        'valid' => true,
        'result' => 'checked_in',
    ]);
});

test('regular user cannot access the check-in page', function () {
    $response = $this->actingAs($this->user)->get(route('organizer.check-in.index', $this->event));

    $response->assertForbidden();
});

test('organizer sees the picker with their events and door stats', function () {
    $secondEvent = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(21),
    ]);

    $response = $this->actingAs($this->organizer)->get(route('organizer.check-in.picker'));

    $response->assertOk();
    $response->assertSee('Door check-in');
    $response->assertSee($this->event->title);
    $response->assertSee($secondEvent->title);
    $response->assertSee(route('organizer.check-in.index', $this->event), false);
});

test('organizer with no events sees the picker empty state', function () {
    $emptyOrganizer = User::factory()->asOrganizer()->create();

    $response = $this->actingAs($emptyOrganizer)->get(route('organizer.check-in.picker'));

    $response->assertOk();
    $response->assertSee('No events yet');
});

test('organizer only sees their own events in the picker', function () {
    $other = User::factory()->asOrganizer()->create();
    $otherEvent = Event::factory()->published()->create([
        'organizer_id' => $other->id,
        'starts_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($this->organizer)->get(route('organizer.check-in.picker'));

    $response->assertOk();
    $response->assertSee($this->event->title);
    $response->assertDontSee($otherEvent->title);
});

test('admin can open the picker and sees all events', function () {
    $admin = User::factory()->asAdmin()->create();
    $other = User::factory()->asOrganizer()->create();
    $otherEvent = Event::factory()->published()->create([
        'organizer_id' => $other->id,
        'starts_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($admin)->get(route('organizer.check-in.picker'));

    $response->assertOk();
    $response->assertSee($this->event->title);
    $response->assertSee($otherEvent->title);
});

test('regular user cannot access the picker', function () {
    $response = $this->actingAs($this->user)->get(route('organizer.check-in.picker'));

    $response->assertForbidden();
});
