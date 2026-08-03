<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use App\Services\BookingService;
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

test('user creates free booking', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('bookings', [
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => BookingStatus::Confirmed->value,
    ]);
    $this->assertDatabaseHas('tickets', [
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => TicketStatus::Valid->value,
    ]);
});

test('user creates paid booking', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();
    $booking = Booking::where('event_id', $this->event->id)->where('user_id', $this->user->id)->first();
    $this->assertEquals(BookingStatus::Pending, $booking->status);
    $this->assertNotNull($booking->expires_at);
    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => 'pending',
        'amount' => 400,
    ]);
    // No tickets yet for pending paid booking
    $this->assertDatabaseCount('tickets', 0);
});

test('draft event rejected', function () {
    $draft = Event::factory()->create(['organizer_id' => $this->organizer->id, 'starts_at' => now()->addDays(5)]);

    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $draft->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHas('error');
});

test('ended event rejected', function () {
    $past = Event::factory()->published()->past()->create(['organizer_id' => $this->organizer->id]);

    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $past->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHas('error');
});

test('inactive ticket type rejected', function () {
    $inactive = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Off',
        'price' => 0,
        'quantity' => 10,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
        'is_active' => false,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $inactive->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHas('error');
});

test('insufficient capacity returns 409', function () {
    $smallType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Small',
        'price' => 0,
        'quantity' => 2,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $smallType->id, 'quantity' => 5],
        ],
    ]);

    $response->assertSessionHas('error');
});

test('totals calculated server side', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 3],
        ],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertEquals('600.00', $booking->total);
    $this->assertEquals('600.00', $booking->subtotal);
    $this->assertEquals('0.00', $booking->fees);
});

test('unique reference generated', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 1],
        ],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertMatchesRegularExpression('/^IEV-[A-Z0-9]{8}$/', $booking->reference);
});

test('free booking confirmed immediately', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 2],
        ],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertEquals(BookingStatus::Confirmed, $booking->status);
    $this->assertNotNull($booking->confirmed_at);
    $this->assertNull($booking->expires_at);
    $this->assertCount(2, $booking->tickets);
});

test('paid booking pending with expires_at', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $this->assertEquals(BookingStatus::Pending, $booking->status);
    $this->assertNotNull($booking->expires_at);
    $this->assertTrue($booking->expires_at->isFuture());
});

test('empty booking rejected', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
});

test('guest cannot create booking', function () {
    $response = $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('login'));
});

test('booking reduces available capacity', function () {
    $initial = $this->paidType->availableQuantity();

    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 3],
        ],
    ]);

    $this->paidType->refresh();
    $this->assertEquals($initial - 3, $this->paidType->availableQuantity());
});

test('capacity released on cancel', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 3],
        ],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $before = $this->freeType->availableQuantity();

    $this->actingAs($this->user)->post(route('bookings.cancel', $booking));

    $this->freeType->refresh();
    $this->assertEquals($before + 3, $this->freeType->availableQuantity());
});

test('concurrent booking does not oversell', function () {
    $smallType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'One Left',
        'price' => 0,
        'quantity' => 1,
        'min_per_booking' => 1,
        'max_per_booking' => 1,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Simulate concurrent bookings using transactions
    $service = app(BookingService::class);
    $successCount = 0;
    $failCount = 0;

    for ($i = 0; $i < 10; $i++) {
        try {
            DB::transaction(function () use ($service, $smallType) {
                $user = User::factory()->create();
                $service->create($user, [
                    'event_id' => $this->event->id,
                    'items' => [
                        ['ticket_type_id' => $smallType->id, 'quantity' => 1],
                    ],
                ]);
            });
            $successCount++;
        } catch (Throwable $e) {
            $failCount++;
        }
    }

    $this->assertEquals(1, $successCount);
    $this->assertDatabaseCount('bookings', 1);
});

test('duplicate ticket type rejected', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
    ]);

    // The store request validates uniqueness of ticket_type_id via 'distinct' rule.
    $response->assertSessionHasErrors(['items.*.ticket_type_id']);
});

test('idempotency key returns existing booking', function () {
    $this->actingAs($this->user);

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'idempotency_key' => 'test-key-123',
    ]);

    $firstBooking = Booking::where('event_id', $this->event->id)->first();

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'idempotency_key' => 'test-key-123',
    ]);

    $this->assertDatabaseCount('bookings', 1);
});

test('same idempotency key returns same booking after window expiry', function () {
    $this->actingAs($this->user);

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'idempotency_key' => 'test-key-123',
    ]);

    $firstBooking = Booking::where('event_id', $this->event->id)->first();

    // Push the booking outside the 15-minute idempotency window.
    $firstBooking->forceFill(['created_at' => now()->subMinutes(30)])->save();

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'idempotency_key' => 'test-key-123',
    ]);

    // Unique index backstop: the stale key resolves to the existing booking
    // instead of 500ing on a duplicate key violation.
    $this->assertDatabaseCount('bookings', 1);
});

test('changed selection with a new key creates a new booking', function () {
    $this->actingAs($this->user);

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'idempotency_key' => 'selection-a',
    ]);

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 2],
        ],
        'idempotency_key' => 'selection-b',
    ]);

    // The checkout UI derives the key from the selection, so a changed
    // selection produces a new key and a legitimate new booking.
    $this->assertDatabaseCount('bookings', 2);
});

test('expired booking cannot be confirmed', function () {
    $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 2]],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $booking->update(['expires_at' => now()->subMinute()]);

    $response = $this->post(route('bookings.confirm-payment', $booking));
    $response->assertSessionHas('error');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Expired);
    $this->assertDatabaseCount('tickets', 0);
    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => PaymentStatus::Cancelled->value,
    ]);
});
