<?php

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

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 2]],
    ]);
});

test('expire command expires pending bookings past their window', function () {
    $booking = Booking::where('event_id', $this->event->id)->first();
    expect($booking->status)->toBe(BookingStatus::Pending);

    $booking->update(['expires_at' => now()->subHour()]);

    $this->artisan('bookings:expire')->assertSuccessful();

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Expired);
    expect($booking->cancelled_at)->toBeNull();
});

test('expire command cascades to tickets and payments', function () {
    $booking = Booking::where('event_id', $this->event->id)->first();

    // Confirm while the window is open so tickets get generated and the
    // payment succeeds, then revert the row to a pending+past state so the
    // command has both cascades to perform (artificial state — pending
    // bookings normally never hold tickets).
    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $booking));

    // Refresh first: the in-memory model is stale (still "pending" from
    // beforeEach), and Eloquent's update() only writes dirty attributes — a
    // stale status would silently mask the revert below.
    $booking->refresh();
    $booking->update([
        'status' => BookingStatus::Pending->value,
        'expires_at' => now()->subHour(),
        'confirmed_at' => null,
    ]);
    $booking->payments()->update(['status' => PaymentStatus::Pending->value]);

    $this->artisan('bookings:expire');

    $this->assertDatabaseHas('tickets', [
        'booking_id' => $booking->id,
        'status' => TicketStatus::Cancelled->value,
    ]);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => PaymentStatus::Cancelled->value,
    ]);
});

test('expire command leaves pending bookings inside their window alone', function () {
    $booking = Booking::where('event_id', $this->event->id)->first();
    $booking->update(['expires_at' => now()->addMinutes(5)]);

    $this->artisan('bookings:expire');

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('expire command leaves confirmed bookings alone', function () {
    $booking = Booking::where('event_id', $this->event->id)->first();

    // Confirm while the window is open (legitimate path, tickets generated).
    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $booking));
    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);

    // Simulate a stale window (as if the row had been read as pending+past by
    // the command's chunk before this confirmation): the guarded update must
    // never expire the confirmed booking nor cancel its tickets.
    $booking->update(['expires_at' => now()->subHour()]);

    $this->artisan('bookings:expire');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->tickets()->where('status', TicketStatus::Valid->value)->count())->toBe(2);
});

test('expiry never clobbers a booking confirmed mid-run', function () {
    // Simulates the ExpireBookings race: a booking was pending and past its
    // window when the command read it, but was confirmed (status → confirmed,
    // expires_at → null, as confirmPayment does) before the guarded update
    // executed. The update is re-guarded on status + expiry so it cannot
    // overwrite the fresh confirmation. (State-level simulation — a true
    // interleaving requires a concurrent connection, which SQLite :memory:
    // cannot provide.)
    $booking = Booking::where('event_id', $this->event->id)->first();
    $booking->update(['expires_at' => now()->subHour()]);

    $booking->update([
        'status' => BookingStatus::Confirmed->value,
        'confirmed_at' => now(),
        'expires_at' => null,
    ]);

    // Give the simulated confirmation its tickets (confirmPayment generates
    // them as part of the same transaction).
    $booking->tickets()->createMany([
        [
            'ticket_type_id' => $this->paidType->id,
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'code' => 'T-SIMULATED01',
            'status' => TicketStatus::Valid->value,
            'issued_at' => now(),
        ],
        [
            'ticket_type_id' => $this->paidType->id,
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'code' => 'T-SIMULATED02',
            'status' => TicketStatus::Valid->value,
            'issued_at' => now(),
        ],
    ]);

    $this->artisan('bookings:expire');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->tickets()->where('status', TicketStatus::Valid->value)->count())->toBe(2);
});

test('expire command reports when there is nothing to expire', function () {
    $this->artisan('bookings:expire')
        ->expectsOutput('No bookings to expire.')
        ->assertSuccessful();
});
