<?php

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->asAdmin()->create();
    $this->organizer = User::factory()->asOrganizer()->create();
    $this->user = User::factory()->create([
        'name' => 'John Buyer',
    ]);

    $this->event = Event::factory()->published()->create([
        'organizer_id' => $this->organizer->id,
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'VIP',
        'price' => 300,
        'quantity' => 100,
        'min_per_booking' => 1,
        'max_per_booking' => 10,
        'currency' => 'MAD',
        'is_active' => true,
        'sales_start_at' => now()->subDay(),
        'sales_end_at' => now()->addMonth(),
    ]);

    // Create a confirmed booking with 2 items and a succeeded payment of 600
    $this->booking = Booking::create([
        'reference' => Booking::generateReference(),
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'status' => BookingStatus::Confirmed,
        'subtotal' => 600.00,
        'fees' => 0,
        'total' => 600.00,
        'currency' => 'MAD',
        'expires_at' => null,
        'confirmed_at' => now(),
    ]);

    BookingItem::create([
        'booking_id' => $this->booking->id,
        'ticket_type_id' => $this->ticketType->id,
        'ticket_name' => 'VIP',
        'unit_price' => 300.00,
        'quantity' => 2,
        'line_total' => 600.00,
    ]);

    Payment::create([
        'booking_id' => $this->booking->id,
        'provider' => 'manual',
        'status' => PaymentStatus::Succeeded,
        'amount' => 600.00,
        'currency' => 'MAD',
        'paid_at' => now(),
    ]);

    Ticket::create([
        'booking_id' => $this->booking->id,
        'booking_item_id' => null,
        'ticket_type_id' => $this->ticketType->id,
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'code' => Ticket::generateCode(),
        'status' => TicketStatus::Valid,
        'issued_at' => now(),
    ]);
});

it('allows admin to view the platform dashboard', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin command center');
    $response->assertSee('Needs attention');
    $response->assertSee('Gross volume');
    $response->assertSee('Live events');
    $response->assertSee('Payment reliability');
    $response->assertSee('Approval queue');
    $response->assertDontSee('+ New event');
});

it('displays platform operations and moderation data', function () {
    Event::factory()->underReview()->create([
        'organizer_id' => $this->organizer->id,
        'title' => 'Awaiting Platform Review',
    ]);

    Payment::factory()->create([
        'booking_id' => $this->booking->id,
        'status' => PaymentStatus::Failed,
        'amount' => 125,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Awaiting Platform Review');
    $response->assertSee('Failed payments');
    $response->assertSee('Event pipeline');

    expect($response->viewData('attentionItems')['event_reviews'])->toBe(1)
        ->and($response->viewData('attentionItems')['failed_payments'])->toBe(1)
        ->and($response->viewData('eventPipeline')->get(EventStatus::Published->value))->toBe(1)
        ->and($response->viewData('platformStats')['organizers'])->toBe(1);
});

it('displays real data on the admin dashboard', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('600');
    $response->assertSee('John Buyer');
    $response->assertSee('2');
    $response->assertSee('Confirmed');
});

it('forbids organizer from accessing the admin dashboard', function () {
    $response = $this->actingAs($this->organizer)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

it('forbids regular user from accessing the admin dashboard', function () {
    $response = $this->actingAs($this->user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

it('redirects guest to login', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

// ── Dashboard chart series tests ──

it('passes chart series with exactly 5 entries', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $chart = $response->viewData('chart');

    $this->assertCount(5, $chart);

    foreach ($chart as $bar) {
        $this->assertArrayHasKey('label', $bar);
        $this->assertArrayHasKey('revH', $bar);
        $this->assertArrayHasKey('tixH', $bar);
        $this->assertArrayHasKey('revLabel', $bar);
        $this->assertArrayHasKey('tixLabel', $bar);
        $this->assertStringEndsWith('%', $bar['revH']);
        $this->assertStringEndsWith('%', $bar['tixH']);
        $this->assertStringContainsString('MAD', $bar['revLabel']);
    }
});

it('chart labels follow W1..W5 convention', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $chart = $response->viewData('chart');
    $labels = array_column($chart, 'label');

    $this->assertSame(['W1', 'W2', 'W3', 'W4', 'W5'], $labels);
});

it('chart does not contain hardcoded sample values', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $chart = $response->viewData('chart');
    $allLabels = collect($chart)->flatMap(fn ($bar) => [$bar['revLabel'], $bar['tixLabel']])->implode(' ');

    $this->assertStringNotContainsString('44,100', $allLabels);
    $this->assertStringNotContainsString('49,820', $allLabels);
});

// ── Dashboard category bars tests ──

it('passes category bars from real data', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $catBars = $response->viewData('catBars');

    $this->assertIsArray($catBars);
    $this->assertNotEmpty($catBars);

    foreach ($catBars as $bar) {
        $this->assertArrayHasKey('label', $bar);
        $this->assertArrayHasKey('value', $bar);
        $this->assertArrayHasKey('pct', $bar);
        $this->assertArrayHasKey('color', $bar);
        $this->assertStringContainsString('tix', $bar['value']);
        $this->assertStringEndsWith('%', $bar['pct']);
    }
});

it('category bars reflect booking item quantities', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $catBars = $response->viewData('catBars');

    // Our VIP booking has quantity 2 → should appear in category bars
    $vipBar = collect($catBars)->firstWhere('label', $this->event->category->name);
    $this->assertNotNull($vipBar, 'Event category should appear in catBars');
    $this->assertStringContainsString('2', $vipBar['value']);
});

// ── Check-in rate tests ──

it('checkInRate is null when no tickets exist', function () {
    // Remove all tickets
    Ticket::query()->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $checkInRate = $response->viewData('checkInRate');
    $this->assertNull($checkInRate);
});

it('checkInRate is numeric when tickets exist', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $checkInRate = $response->viewData('checkInRate');
    $this->assertIsFloat($checkInRate);
    $this->assertGreaterThanOrEqual(0, $checkInRate);
    $this->assertLessThanOrEqual(100, $checkInRate);
});

it('checkInRate computes correctly with checked-in tickets', function () {
    // Create a second ticket that is checked in
    Ticket::create([
        'booking_id' => $this->booking->id,
        'booking_item_id' => null,
        'ticket_type_id' => $this->ticketType->id,
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'code' => Ticket::generateCode(),
        'status' => TicketStatus::Used,
        'issued_at' => now(),
        'checked_in_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $checkInRate = $response->viewData('checkInRate');
    // 1 checked in / 2 total = 50%
    $this->assertEqualsWithDelta(50.0, $checkInRate, 0.1);
});

// ── hasEvents tests ──

it('hasEvents is true when events exist', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $hasEvents = $response->viewData('hasEvents');
    $this->assertTrue($hasEvents);
});

it('hasEvents is false when no events exist', function () {
    Event::query()->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $hasEvents = $response->viewData('hasEvents');
    $this->assertFalse($hasEvents);
});

// ── Empty state tests ──

it('revenue is zero when no payments exist', function () {
    Payment::query()->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $revenue = $response->viewData('revenue');
    $this->assertEquals(0.0, $revenue);
});

it('ticketsIssued is zero when no tickets exist', function () {
    Ticket::query()->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $ticketsIssued = $response->viewData('ticketsIssued');
    $this->assertEquals(0, $ticketsIssued);
});

// ── Admin events index — Users tab ──

it('index returns real users in view data', function () {
    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);

    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $response->assertOk();
    $users = $response->viewData('users');

    $userNames = $users->pluck('name')->toArray();
    $this->assertContains('Alice Smith', $userNames);
    $this->assertContains('Bob Jones', $userNames);
});

it('index does not contain hardcoded sample user', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $response->assertOk();
    $users = $response->viewData('users');
    $userNames = $users->pluck('name')->toArray();

    $this->assertNotContains('Yassine Benali', $userNames);
});

it('index users have bookings_count', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $users = $response->viewData('users');
    // John Buyer from beforeEach has 1 booking
    $john = $users->firstWhere('name', 'John Buyer');
    $this->assertNotNull($john);
    $this->assertEquals(1, $john->bookings_count);

    // Admin user from beforeEach has 0 bookings
    $admin = $users->firstWhere('name', $this->admin->name);
    $this->assertNotNull($admin);
    $this->assertEquals(0, $admin->bookings_count);
});

it('index user_search filters users', function () {
    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);

    $response = $this->actingAs($this->admin)->get(route('admin.events.index', ['user_search' => 'Alice']));

    $users = $response->viewData('users');
    $userNames = $users->pluck('name')->toArray();

    $this->assertContains('Alice Smith', $userNames);
    $this->assertNotContains('Bob Jones', $userNames);

    $userSearch = $response->viewData('userSearch');
    $this->assertEquals('Alice', $userSearch);
});

// ── Admin events index — Reports tab ──

it('index returns cityBars from real data', function () {
    $city1Event = Event::factory()->published()->create(['city' => 'Casablanca']);
    $city2Event = Event::factory()->published()->create(['city' => 'Rabat']);

    // Create tickets in both cities
    foreach ([$city1Event, $city2Event] as $ev) {
        $tt = TicketType::create([
            'event_id' => $ev->id,
            'name' => 'GA',
            'price' => 100,
            'quantity' => 50,
            'min_per_booking' => 1,
            'max_per_booking' => 5,
            'currency' => 'MAD',
            'is_active' => true,
        ]);

        $bk = Booking::create([
            'reference' => Booking::generateReference(),
            'user_id' => $this->user->id,
            'event_id' => $ev->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 100.00,
            'fees' => 0,
            'total' => 100.00,
            'currency' => 'MAD',
        ]);

        Ticket::create([
            'booking_id' => $bk->id,
            'ticket_type_id' => $tt->id,
            'user_id' => $this->user->id,
            'event_id' => $ev->id,
            'code' => Ticket::generateCode(),
            'status' => TicketStatus::Valid,
            'issued_at' => now(),
        ]);
    }

    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $cityBars = $response->viewData('cityBars');
    $this->assertIsArray($cityBars);
    $this->assertNotEmpty($cityBars);

    $cityLabels = array_column($cityBars, 'label');
    $this->assertContains('Casablanca', $cityLabels);
    $this->assertContains('Rabat', $cityLabels);

    foreach ($cityBars as $bar) {
        $this->assertArrayHasKey('label', $bar);
        $this->assertArrayHasKey('value', $bar);
        $this->assertArrayHasKey('pct', $bar);
        $this->assertIsInt($bar['value']);
        $this->assertGreaterThan(0, $bar['value']);
    }
});

it('cityBars does not contain hardcoded sample values', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $response->assertOk();
    // Verify old hardcoded data "14,200" is not in the cityBars
    $cityBars = $response->viewData('cityBars');
    foreach ($cityBars as $bar) {
        $this->assertNotEquals('14,200', (string) $bar['value']);
        $this->assertStringNotContainsString('14,200', $bar['label'] ?? '');
    }
});

it('index returns reportStats with correct shape', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $reportStats = $response->viewData('reportStats');
    $this->assertIsArray($reportStats);
    $this->assertArrayHasKey('grossVolume', $reportStats);
    $this->assertArrayHasKey('activeUsers', $reportStats);
    $this->assertArrayHasKey('organizers', $reportStats);
    $this->assertArrayHasKey('refundRate', $reportStats);
    $this->assertIsFloat($reportStats['grossVolume']);
    $this->assertIsInt($reportStats['activeUsers']);
    $this->assertIsInt($reportStats['organizers']);
    $this->assertIsFloat($reportStats['refundRate']);
});

it('reportStats grossVolume equals sum of succeeded payments', function () {
    // The beforeEach creates a 600.00 succeeded payment
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $reportStats = $response->viewData('reportStats');
    $this->assertEqualsWithDelta(600.00, $reportStats['grossVolume'], 0.01);
});

it('reportStats organizers count is correct', function () {
    User::factory()->asOrganizer()->create();
    User::factory()->asOrganizer()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $reportStats = $response->viewData('reportStats');
    // 2 from this test + 1 from beforeEach
    $this->assertEquals(3, $reportStats['organizers']);
});

it('reportStats refundRate is 0 when no refunded payments', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $reportStats = $response->viewData('reportStats');
    $this->assertEquals(0.0, $reportStats['refundRate']);
});

it('reportStats refundRate computes correctly', function () {
    // Create 1 refunded + 1 succeeded = 50% refund rate
    Payment::create([
        'booking_id' => $this->booking->id,
        'provider' => 'manual',
        'status' => PaymentStatus::Refunded,
        'amount' => 100.00,
        'currency' => 'MAD',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $reportStats = $response->viewData('reportStats');
    // 1 refunded / 2 total = 50%
    $this->assertEqualsWithDelta(50.0, $reportStats['refundRate'], 0.01);
});

it('index passes userSearch as null when not provided', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

    $userSearch = $response->viewData('userSearch');
    $this->assertNull($userSearch);
});

// ── Bookings index $filters ──

it('bookings index passes filters with status and search', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.bookings.index', [
        'status' => 'confirmed',
        'search' => 'test',
    ]));

    $response->assertOk();
    $filters = $response->viewData('filters');
    $this->assertIsArray($filters);
    $this->assertEquals('confirmed', $filters['status'] ?? null);
    $this->assertEquals('test', $filters['search'] ?? null);
});

it('bookings index passes empty filters when no params', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

    $response->assertOk();
    $filters = $response->viewData('filters');
    $this->assertIsArray($filters);
    $this->assertArrayNotHasKey('status', $filters);
    $this->assertArrayNotHasKey('search', $filters);
});

// ── Tickets index $filters ──

it('tickets index passes filters with event_id, status, search', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.tickets.index', [
        'event_id' => '1',
        'status' => 'valid',
        'search' => 'T-',
    ]));

    $response->assertOk();
    $filters = $response->viewData('filters');
    $this->assertIsArray($filters);
    $this->assertEquals('1', $filters['event_id'] ?? null);
    $this->assertEquals('valid', $filters['status'] ?? null);
    $this->assertEquals('T-', $filters['search'] ?? null);
});

it('tickets index passes empty filters when no params', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.tickets.index'));

    $response->assertOk();
    $filters = $response->viewData('filters');
    $this->assertIsArray($filters);
    $this->assertEmpty($filters);
});

// ── Payments index $filters ──

it('payments index passes filters with status, reference, date_from, date_to', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.payments.index', [
        'status' => 'succeeded',
        'reference' => 'IEV-',
        'date_from' => '2026-01-01',
        'date_to' => '2026-12-31',
    ]));

    $response->assertOk();
    $filters = $response->viewData('filters');
    $this->assertIsArray($filters);
    $this->assertEquals('succeeded', $filters['status'] ?? null);
    $this->assertEquals('IEV-', $filters['reference'] ?? null);
    $this->assertEquals('2026-01-01', $filters['date_from'] ?? null);
    $this->assertEquals('2026-12-31', $filters['date_to'] ?? null);
});

it('payments index passes empty filters when no params', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.payments.index'));

    $response->assertOk();
    $filters = $response->viewData('filters');
    $this->assertIsArray($filters);
    $this->assertEmpty($filters);
});
