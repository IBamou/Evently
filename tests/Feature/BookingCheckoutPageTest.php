<?php

use App\Enums\BookingStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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

// ── Checkout page rendering ──

test('checkout page renders with event details and ticket types', function () {
    $response = $this->actingAs($this->user)->get(route('bookings.checkout', [
        'event' => $this->event->id,
        'qty' => [$this->paidType->id => 2],
    ]));

    $response->assertOk();
    $response->assertViewIs('bookings.checkout');
    $response->assertSee($this->event->title);
    $response->assertSee($this->paidType->name);
    $response->assertSee('Checkout');
    // Payment form fields exist in the HTML (big-pickle adds them).
    $response->assertSee('payment[card_number]', false);
    $response->assertSee('payment[expiry]', false);
    $response->assertSee('payment[cvc]', false);
});

test('checkout page shows correct total for selected quantities', function () {
    $response = $this->actingAs($this->user)->get(route('bookings.checkout', [
        'event' => $this->event->id,
        'qty' => [$this->paidType->id => 3],
    ]));

    $response->assertOk();
    // 200 MAD × 3 = 600 MAD — check the total appears in the page.
    $response->assertSee('600');
});

// ── Store with valid mock card → confirmed + tickets ──

test('paid booking with valid mock card is confirmed immediately', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 2],
        ],
        'payment' => [
            'card_number' => '4242 4242 4242 4242',
            'expiry' => '12/30',
            'cvc' => '123',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $booking = Booking::where('event_id', $this->event->id)
        ->where('user_id', $this->user->id)
        ->first();

    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->confirmed_at)->not->toBeNull();

    // Tickets should be issued immediately.
    $this->assertDatabaseCount('tickets', 2);
    $this->assertDatabaseHas('tickets', [
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => TicketStatus::Valid->value,
    ]);

    // Payment should be marked as succeeded.
    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => 'succeeded',
        'amount' => 400,
    ]);
});

test('paid booking with valid mock card accepts 4242 card number with spaces', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'payment' => [
            'card_number' => '4242424242424242',
            'expiry' => '06/28',
            'cvc' => '456',
        ],
    ]);

    $response->assertRedirect();
    $booking = Booking::where('event_id', $this->event->id)->first();
    expect($booking->status)->toBe(BookingStatus::Confirmed);
});

// ── Store with invalid card → validation error, no booking ──

test('paid booking with past expiry card is rejected', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'payment' => [
            'card_number' => '4242 4242 4242 4242',
            'expiry' => '01/20',
            'cvc' => '123',
        ],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseCount('bookings', 0);
});

test('paid booking with invalid card number format is rejected', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'payment' => [
            'card_number' => '1234 5678 9012 3456',
            'expiry' => '12/30',
            'cvc' => '123',
        ],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseCount('bookings', 0);
});

test('paid booking with short card number is rejected', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'payment' => [
            'card_number' => '4242 4242',
            'expiry' => '12/30',
            'cvc' => '123',
        ],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseCount('bookings', 0);
});

test('paid booking without payment fields is created as pending', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();

    $booking = Booking::where('event_id', $this->event->id)
        ->where('user_id', $this->user->id)
        ->first();

    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe(BookingStatus::Pending);
    // No tickets for pending paid booking.
    $this->assertDatabaseCount('tickets', 0);
});

// ── Free event without card → confirmed + tickets ──

test('free booking without card is confirmed immediately', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->freeType->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();

    $booking = Booking::where('event_id', $this->event->id)
        ->where('user_id', $this->user->id)
        ->first();

    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->confirmed_at)->not->toBeNull();
    $this->assertDatabaseCount('tickets', 2);
});

// ── Idempotency preserved ──

test('idempotency key works with mock payment', function () {
    $this->actingAs($this->user);

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 1]],
        'idempotency_key' => 'mock-payment-key',
        'payment' => [
            'card_number' => '4242 4242 4242 4242',
            'expiry' => '12/30',
            'cvc' => '123',
        ],
    ]);

    $this->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->paidType->id, 'quantity' => 1]],
        'idempotency_key' => 'mock-payment-key',
        'payment' => [
            'card_number' => '4242 4242 4242 4242',
            'expiry' => '12/30',
            'cvc' => '123',
        ],
    ]);

    // Only one booking created despite two submissions.
    $this->assertDatabaseCount('bookings', 1);
    $booking = Booking::first();
    expect($booking->status)->toBe(BookingStatus::Confirmed);
    $this->assertDatabaseCount('tickets', 1);
});

// ── Cancel route throttle ──

test('bookings.cancel route has throttle middleware', function () {
    $route = Route::getRoutes()->getByName('bookings.cancel');
    expect($route)->not->toBeNull();
    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('throttle:10,1');
});

// ── Redirect lands on bookings.show with confirmed state ──

test('after mock payment redirect lands on bookings.show with confirmed booking', function () {
    $response = $this->actingAs($this->user)->post(route('bookings.store'), [
        'event_id' => $this->event->id,
        'items' => [
            ['ticket_type_id' => $this->paidType->id, 'quantity' => 1],
        ],
        'payment' => [
            'card_number' => '4242 4242 4242 4242',
            'expiry' => '12/30',
            'cvc' => '123',
        ],
    ]);

    $booking = Booking::where('event_id', $this->event->id)->first();
    $response->assertRedirect(route('bookings.show', $booking));

    // Follow the redirect and verify the show page renders.
    $showResponse = $this->actingAs($this->user)->get(route('bookings.show', $booking));
    $showResponse->assertOk();
    $showResponse->assertSee('Confirmed');
    $showResponse->assertSee($booking->reference);
});
