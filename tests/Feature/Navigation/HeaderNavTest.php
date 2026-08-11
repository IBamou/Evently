<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organizer = User::factory()->asOrganizer()->create();
    $this->admin = User::factory()->asAdmin()->create();
    $this->user = User::factory()->create();
});

/**
 * Extract the <nav aria-label="Main">...</nav> block from the HTML.
 * Returns the nav's inner HTML or empty string if not found.
 */
function extractNav(string $html): string
{
    if (preg_match('/<nav\s+aria-label="Main"[^>]*>(.*?)<\/nav>/s', $html, $m)) {
        return $m[1];
    }

    return '';
}

// ───────────────────────── GUEST ─────────────────────────

it('shows the correct top-nav items for guests', function () {
    $response = $this->get(route('events.index'));

    $response->assertOk();
    $response->assertSee('Events');
    $response->assertSee('Sign in');
    $response->assertSee('Create account');
});

it('links guest nav items to the correct routes', function () {
    $response = $this->get(route('events.index'));

    $response->assertOk();
    $response->assertSee(route('events.index'), false);
    $response->assertSee(route('login'), false);
    $response->assertSee(route('register'), false);
});

it('does not show user or workspace nav items for guests', function () {
    $response = $this->get(route('events.index'));
    $nav = extractNav($response->getContent());

    $this->assertStringNotContainsString('My bookings', $nav);
    $this->assertStringNotContainsString('My tickets', $nav);
    $this->assertStringNotContainsString('Dashboard', $nav);
    $this->assertStringNotContainsString('Check-in', $nav);
    $this->assertStringNotContainsString('Profile', $nav);
    $this->assertStringNotContainsString('Admin', $nav);
});

it('ignores role preview query parameters for guests', function () {
    $response = $this->get(route('events.index', ['role' => 'admin']));

    $response->assertOk();
    $response->assertSee('Sign in');
    $response->assertDontSee('Preview as');
    $response->assertDontSee('?role=admin', false);
});

// ───────────────────────── USER ──────────────────────────

it('shows the correct top-nav items for authenticated users', function () {
    $response = $this->actingAs($this->user)->get(route('events.index'));

    $response->assertOk();
    $response->assertSee('Events');
    $response->assertSee('My bookings');
    $response->assertSee('My tickets');
    $response->assertSee('Profile');
});

it('links user nav items to the correct routes', function () {
    $response = $this->actingAs($this->user)->get(route('events.index'));

    $response->assertOk();
    $response->assertSee(route('events.index'), false);
    $response->assertSee(route('bookings.index'), false);
    $response->assertSee(route('tickets.index'), false);
    $response->assertSee(route('profile.edit'), false);
});

it('does not show organizer or admin nav items for regular users', function () {
    $response = $this->actingAs($this->user)->get(route('events.index'));
    $nav = extractNav($response->getContent());

    $this->assertStringNotContainsString('Dashboard', $nav);
    $this->assertStringNotContainsString('Check-in', $nav);
    $this->assertStringNotContainsString('Admin', $nav);
    $this->assertStringNotContainsString('My events', $nav);
});

// ───────────────────────── ORGANIZER ─────────────────────

it('shows the correct top-nav items for organizer', function () {
    $response = $this->actingAs($this->organizer)->get(route('organizer.dashboard'));

    $response->assertOk();
    $response->assertSee('Dashboard');
    $response->assertSee('My events');
    $response->assertSee('Check-in');
    $response->assertSee('Browse');
    $response->assertSee('Profile');
});

it('links organizer nav items to the correct routes', function () {
    $response = $this->actingAs($this->organizer)->get(route('organizer.dashboard'));

    $response->assertOk();
    $response->assertSee(route('organizer.dashboard'), false);
    $response->assertSee(route('organizer.events.index'), false);
    $response->assertSee(route('organizer.check-in.picker'), false);
    $response->assertSee(route('events.index'), false);
    $response->assertSee(route('profile.edit'), false);
});

it('does not show user or admin nav items for organizer', function () {
    $response = $this->actingAs($this->organizer)->get(route('organizer.dashboard'));
    $nav = extractNav($response->getContent());

    $this->assertStringNotContainsString('My bookings', $nav);
    $this->assertStringNotContainsString('My tickets', $nav);
    $this->assertStringNotContainsString('Admin', $nav);
});

it('highlights "My events" on organizer ticket-types index', function () {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
    ]);
    $response = $this->actingAs($this->organizer)->get(
        route('organizer.ticket-types.index', $event)
    );

    $response->assertOk();

    $html = $response->getContent();
    $myEventsHref = route('organizer.events.index');

    // The "My events" tab must have font-weight:800 (active marker).
    $this->assertMatchesRegularExpression(
        '/href="'.preg_quote($myEventsHref, '/').'"[^>]*font-weight:800/',
        $html,
    );
});

// ───────────────────────── ADMIN ─────────────────────────

it('shows the correct top-nav items for admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin');
    $response->assertSee('Dashboard');
    $response->assertSee('Check-in');
    $response->assertSee('Browse');
    $response->assertSee('Profile');
});

it('links admin nav items to the correct routes', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee(route('admin.events.index'), false);
    $response->assertSee(route('admin.dashboard'), false);
    $response->assertSee(route('organizer.check-in.picker'), false);
    $response->assertSee(route('events.index'), false);
    $response->assertSee(route('profile.edit'), false);
});

it('does not show user or organizer nav items for admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $nav = extractNav($response->getContent());

    $this->assertStringNotContainsString('My bookings', $nav);
    $this->assertStringNotContainsString('My tickets', $nav);
    $this->assertStringNotContainsString('My events', $nav);
});

it('highlights "Admin" on admin.bookings.index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

    $response->assertOk();

    $html = $response->getContent();
    $adminHref = route('admin.events.index');

    $this->assertMatchesRegularExpression(
        '/href="'.preg_quote($adminHref, '/').'"[^>]*font-weight:800/',
        $html,
    );
});

it('highlights "Admin" on admin.tickets.index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.tickets.index'));

    $response->assertOk();

    $html = $response->getContent();
    $adminHref = route('admin.events.index');

    $this->assertMatchesRegularExpression(
        '/href="'.preg_quote($adminHref, '/').'"[^>]*font-weight:800/',
        $html,
    );
});

it('highlights "Admin" on admin.payments.index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.payments.index'));

    $response->assertOk();

    $html = $response->getContent();
    $adminHref = route('admin.events.index');

    $this->assertMatchesRegularExpression(
        '/href="'.preg_quote($adminHref, '/').'"[^>]*font-weight:800/',
        $html,
    );
});

it('highlights "Admin" on admin.categories.index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.categories.index'));

    $response->assertOk();

    $html = $response->getContent();
    $adminHref = route('admin.events.index');

    $this->assertMatchesRegularExpression(
        '/href="'.preg_quote($adminHref, '/').'"[^>]*font-weight:800/',
        $html,
    );
});

// ───────────────────────── NO SIDEBAR ────────────────────

it('does not render any sidebar markup on organizer dashboard', function () {
    $response = $this->actingAs($this->organizer)->get(route('organizer.dashboard'));

    $response->assertOk();
    $response->assertDontSee('ev-sb');
});

it('does not render any sidebar markup on admin dashboard', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertDontSee('ev-sb');
});

// ───────────────────────── AUTHORIZATION ─────────────────

it('prevents workspace roles from opening attendee-only pages', function () {
    $this->actingAs($this->organizer)
        ->get(route('bookings.index'))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('tickets.index'))
        ->assertForbidden();
});

// ───────────────────────── ATTENDEE SHORTCUT ─────────────

it('shows the attendee ticket shortcut to users', function () {
    $response = $this->actingAs($this->user)->get(route('events.index'));

    $response->assertOk();

    // Check for a rendered <a> element with aria-label="My tickets" (not the CSS rule).
    $html = $response->getContent();
    $this->assertMatchesRegularExpression(
        '/<a\s[^>]*aria-label="My tickets"/',
        $html,
    );
});

it('hides the attendee ticket shortcut from guests', function () {
    $html = $this->get(route('events.index'))->assertOk()->getContent();

    // Ensure no <a> element has aria-label="My tickets" (ignore CSS selectors).
    $this->assertDoesNotMatchRegularExpression(
        '/<a\s[^>]*aria-label="My tickets"/',
        $html,
    );
});

it('hides the attendee ticket shortcut from workspace roles', function () {
    $this->actingAs($this->organizer)
        ->get(route('organizer.dashboard'))
        ->assertOk();

    $html = $this->actingAs($this->organizer)
        ->get(route('organizer.dashboard'))
        ->getContent();
    $this->assertDoesNotMatchRegularExpression(
        '/<a\s[^>]*aria-label="My tickets"/',
        $html,
    );

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    $html = $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->getContent();
    $this->assertDoesNotMatchRegularExpression(
        '/<a\s[^>]*aria-label="My tickets"/',
        $html,
    );
});
