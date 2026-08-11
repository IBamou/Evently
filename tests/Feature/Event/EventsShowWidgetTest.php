<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organizer = User::factory()->asOrganizer()->create();
    $this->event = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(14),
    ]);
    $this->gaType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'General Admission',
        'description' => 'Standard entry',
        'price' => 200,
        'quantity' => 50,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);
    $this->vipType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'VIP',
        'description' => 'Premium access',
        'price' => 500,
        'quantity' => 20,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);
});

// ── Ticket types rendering ──

test('events.show renders ticket types with names and prices', function () {
    $response = $this->get(route('events.show', $this->event));

    $response->assertOk();
    $response->assertSee('General Admission');
    $response->assertSee('VIP');
    $response->assertSee('200');
    $response->assertSee('500');
    $response->assertSee('MAD');
});

test('events.show renders quantity steppers for each ticket type', function () {
    $response = $this->get(route('events.show', $this->event));

    $response->assertOk();
    // Each ticket type row has increment/decrement buttons.
    $response->assertSee('data-widget-inc', false);
    $response->assertSee('data-widget-dec', false);
    // Ticket type rows are identified by data-tt-id.
    $response->assertSee("data-tt-id=\"{$this->gaType->id}\"", false);
    $response->assertSee("data-tt-id=\"{$this->vipType->id}\"", false);
});

test('events.show disables increment when sold out', function () {
    // Create a type with max_per_booking=50 so we can sell out in one go.
    $bulkType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Bulk',
        'price' => 0,
        'quantity' => 30,
        'min_per_booking' => 1,
        'max_per_booking' => 30,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $user = User::factory()->create();
    $service = app(BookingService::class);
    $service->create($user, [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $bulkType->id, 'quantity' => 30]],
    ]);

    // Bulk is sold out: available_quantity = 30 - 30 = 0.
    $response = $this->get(route('events.show', $this->event));
    $response->assertOk();
    // data-max="0" means the JS won't allow incrementing.
    $response->assertSee('data-max="0"', false);
    $response->assertSee('Sold out');
});

test('events.show shows stock label with remaining quantity', function () {
    $user = User::factory()->create();
    $service = app(BookingService::class);
    $service->create($user, [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $this->gaType->id, 'quantity' => 5]],
    ]);

    $response = $this->get(route('events.show', $this->event));
    $response->assertOk();
    // 50 - 5 = 45 left.
    $response->assertSee('45 left');
});

// ── CTA link ──

test('events.show CTA link has correct base href for checkout', function () {
    $response = $this->get(route('events.show', $this->event));

    $response->assertOk();
    $expectedHref = route('bookings.checkout', ['event' => $this->event->id]);
    $response->assertSee("data-base-href=\"{$expectedHref}\"", false);
});

test('events.show CTA uses qty params when quantities selected via JS', function () {
    // The qty[] params are added by JavaScript. The base href is in the HTML.
    // We verify the checkout route accepts qty[] params.
    $response = $this->actingAs(User::factory()->create())->get(route('bookings.checkout', [
        'event' => $this->event->id,
        'qty' => [$this->gaType->id => 2, $this->vipType->id => 1],
    ]));

    $response->assertOk();
    $response->assertSee($this->event->title);
});

// ── sold / capacity view data ──

test('events.show passes sold and capacity as view data', function () {
    $response = $this->get(route('events.show', $this->event));

    $response->assertOk();

    $sold = $response->viewData('sold');
    $capacity = $response->viewData('capacity');

    expect($sold)->toBeInt();
    expect($sold)->toBe(0); // No tickets sold yet.

    expect($capacity)->toBeInt();
    expect($capacity)->toBe(70); // 50 GA + 20 VIP.
});

test('events.show sold increases as tickets are issued', function () {
    // Use a free ticket type so tickets are generated immediately on create.
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

    $user = User::factory()->create();
    $service = app(BookingService::class);
    $service->create($user, [
        'event_id' => $this->event->id,
        'items' => [['ticket_type_id' => $freeType->id, 'quantity' => 5]],
    ]);

    $response = $this->get(route('events.show', $this->event));
    $response->assertOk();

    $sold = $response->viewData('sold');
    expect($sold)->toBe(5);

    // Capacity now includes the free type too (50 GA + 20 VIP + 100 Free = 170).
    $capacity = $response->viewData('capacity');
    expect($capacity)->toBe(170);
});

// ── Closed sales ──

test('events.show shows closed state for ticket type with past sales_end_at', function () {
    $this->gaType->update(['sales_end_at' => now()->subDay()]);

    $response = $this->get(route('events.show', $this->event));
    $response->assertOk();
    $response->assertSee('Sales ended');
});

test('events.show shows future open date for ticket type with future sales_start_at', function () {
    $futureType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Early Bird',
        'price' => 100,
        'quantity' => 30,
        'min_per_booking' => 1,
        'max_per_booking' => 5,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->addWeek(),
        'sales_end_at' => now()->addMonth(),
    ]);

    $response = $this->get(route('events.show', $this->event));
    $response->assertOk();
    $response->assertSee('Early Bird');
    $response->assertSee('On sale');
});

// ── No ticket types ──

test('events.show shows coming soon when no ticket types', function () {
    $event = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
        'starts_at' => now()->addDays(7),
    ]);

    $response = $this->get(route('events.show', $event));
    $response->assertOk();
    $response->assertSee('Tickets coming soon');
    $response->assertSee('No tickets available yet');
});
