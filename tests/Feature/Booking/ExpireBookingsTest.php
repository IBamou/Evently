<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Event;
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

test('expiration serializes with confirmation: a booking confirmed mid-run is never clobbered', function () {
    // Genuine interleaving: the booking is pending + past its window when the
    // command's chunk reads it, but flips to confirmed (exactly as
    // confirmPayment does: status=confirmed, expires_at=NULL) between the
    // candidate read and the command's guarded UPDATE. The flip is injected
    // via DB::beforeExecuting right before the first `update bookings`
    // statement — the guarded re-check under the row lock must then skip the
    // booking, count 0, and leave the confirmation (and its tickets) intact.
    $booking = Booking::where('event_id', $this->event->id)->first();
    $booking->update(['expires_at' => now()->subHour()]);

    $flipped = false;
    DB::beforeExecuting(function ($query) use ($booking, &$flipped) {
        if ($flipped || ! str_contains($query, 'update "bookings"')) {
            return;
        }

        $flipped = true;

        // Simulate confirmPayment()'s transaction: status → confirmed,
        // expires_at → NULL, and the 2 tickets it generates.
        DB::table('bookings')
            ->where('id', $booking->id)
            ->update([
                'status' => BookingStatus::Confirmed->value,
                'confirmed_at' => now(),
                'expires_at' => null,
            ]);

        foreach ([1, 2] as $i) {
            DB::table('tickets')->insert([
                'booking_id' => $booking->id,
                'ticket_type_id' => $this->paidType->id,
                'user_id' => $this->user->id,
                'event_id' => $booking->event_id,
                'code' => 'T-MIDRUN'.$i,
                'status' => TicketStatus::Valid->value,
                'issued_at' => now(),
            ]);
        }
    });

    $this->artisan('bookings:expire')
        ->expectsOutput('No bookings to expire.')
        ->assertSuccessful();

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->tickets()->where('status', TicketStatus::Valid->value)->count())->toBe(2);
});

test('expire command reports only the rows that truly transitioned', function () {
    // Booking A: pending + past window → will expire (1 transition).
    // Booking B: confirmed legitimately (expires_at NULL) → never a candidate.
    // Booking C: pending, still inside its window → untouched.
    $bookingA = Booking::where('event_id', $this->event->id)->first();
    $bookingA->update(['expires_at' => now()->subHour()]);

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 3]],
    ]);
    $bookingB = Booking::where('event_id', $this->event->id)
        ->where('id', '!=', $bookingA->id)
        ->first();
    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $bookingB));

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 5]],
    ]);
    $bookingC = Booking::where('event_id', $this->event->id)
        ->whereNotIn('id', [$bookingA->id, $bookingB->id])
        ->first();
    $bookingC->update(['expires_at' => now()->addMinutes(5)]);

    $this->artisan('bookings:expire')
        ->expectsOutput('Expired 1 booking(s).')
        ->assertSuccessful();

    expect($bookingA->fresh()->status)->toBe(BookingStatus::Expired);
    expect($bookingB->fresh()->status)->toBe(BookingStatus::Confirmed);
    expect($bookingC->fresh()->status)->toBe(BookingStatus::Pending);
});

test('expired booking cannot later be confirmed', function () {
    $booking = Booking::where('event_id', $this->event->id)->first();
    $booking->update(['expires_at' => now()->subHour()]);

    $this->artisan('bookings:expire');
    expect($booking->fresh()->status)->toBe(BookingStatus::Expired);

    // confirmPayment() re-reads under a row lock, sees Expired, and rejects.
    $this->actingAs($this->user)->post(route('bookings.confirm-payment', $booking))
        ->assertSessionHas('error', 'Only pending bookings can be confirmed.');

    expect($booking->fresh()->status)->toBe(BookingStatus::Expired);
});

test('expire command reports when there is nothing to expire', function () {
    $this->artisan('bookings:expire')
        ->expectsOutput('No bookings to expire.')
        ->assertSuccessful();
});
