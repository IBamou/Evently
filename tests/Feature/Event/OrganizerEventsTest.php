<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->asOrganizer()->create();
    }

    public function test_organizer_creates_event_with_draft_status(): void
    {
        $category = Category::factory()->create();

        $data = [
            'category_id' => $category->id,
            'title' => 'My New Event',
            'description' => 'This is a great event with more than twenty characters for validation.',
            'location' => 'Venue Name',
            'city' => 'Casablanca',
            'format' => 'in_person',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(3)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.store'), $data);

        $response->assertRedirect(route('organizer.events.index'));
        $this->assertDatabaseHas('events', [
            'organizer_id' => $this->organizer->id,
            'status' => EventStatus::Draft->value,
            'title' => 'My New Event',
        ]);
    }

    public function test_organizer_index_returns_200(): void
    {
        Event::factory()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.index'));

        $response->assertOk();
    }

    public function test_organizer_index_starts_to_includes_events_later_that_day(): void
    {
        Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Afternoon Session',
            'starts_at' => now()->addDays(1)->setTime(15, 30),
        ]);

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.index', ['starts_to' => now()->addDays(1)->format('Y-m-d')]));

        $response->assertOk();

        $events = $response->viewData('events');
        $this->assertSame(1, $events->total());
        $this->assertSame('Afternoon Session', $events->first()->title);
    }

    public function test_organizer_index_starts_to_excludes_events_after_that_day(): void
    {
        Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Next Day Event',
            'starts_at' => now()->addDays(2)->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.index', ['starts_to' => now()->addDays(1)->format('Y-m-d')]));

        $response->assertOk();

        $this->assertSame(0, $response->viewData('events')->total());
    }

    public function test_organizer_create_returns_200(): void
    {
        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.create'));

        $response->assertOk()
            ->assertSee('Create your event')
            ->assertSee('Event creation progress')
            ->assertSee('Step 1 of 3')
            ->assertSee('Create draft');
    }

    public function test_organizer_id_in_payload_causes_validation_error(): void
    {
        $category = Category::factory()->create();

        $data = [
            'category_id' => $category->id,
            'title' => 'Event',
            'description' => 'This is a description with enough characters for sure.',
            'location' => 'Venue',
            'city' => 'Casablanca',
            'format' => 'in_person',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(3)->toDateTimeString(),
            'organizer_id' => 999,
        ];

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.store'), $data);

        $response->assertSessionHasErrors('organizer_id');
    }

    public function test_status_in_payload_causes_validation_error(): void
    {
        $category = Category::factory()->create();

        $data = [
            'category_id' => $category->id,
            'title' => 'Event',
            'description' => 'This is a description with enough characters for sure.',
            'location' => 'Venue',
            'city' => 'Casablanca',
            'format' => 'in_person',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(3)->toDateTimeString(),
            'status' => 'published',
        ];

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.store'), $data);

        $response->assertSessionHasErrors('status');
    }

    public function test_regular_user_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('organizer.events.index'));

        $response->assertForbidden();
    }

    public function test_guest_gets_redirect_to_login(): void
    {
        $response = $this->get(route('organizer.events.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_organizer_cannot_view_another_organizers_event(): void
    {
        $otherEvent = Event::factory()->create();

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.edit', $otherEvent));

        $response->assertForbidden();
    }

    public function test_organizer_cannot_update_another_organizers_event(): void
    {
        $otherEvent = Event::factory()->create();

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $otherEvent), ['title' => 'Hacked']);

        $response->assertForbidden();
    }

    public function test_organizer_cannot_delete_another_organizers_event(): void
    {
        $otherEvent = Event::factory()->create();

        $response = $this->actingAs($this->organizer)
            ->delete(route('organizer.events.destroy', $otherEvent));

        $response->assertForbidden();
    }

    public function test_cannot_update_after_event_started(): void
    {
        $event = Event::factory()->started()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'title' => 'Updated Title',
                'description' => 'Updated description that has enough characters for validation.',
                'location' => $event->location,
                'city' => $event->city,
                'format' => $event->format->value,
                'starts_at' => $event->starts_at->toDateTimeString(),
                'ends_at' => $event->ends_at->toDateTimeString(),
            ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => $event->title]);
    }

    public function test_update_with_invalid_date_range_fails(): void
    {
        $event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'city' => $event->city,
                'format' => $event->format->value,
                'starts_at' => now()->addDays(10)->toDateTimeString(),
                'ends_at' => now()->addDays(8)->toDateTimeString(),
            ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_cannot_edit_while_under_review(): void
    {
        $event = Event::factory()->underReview()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'title' => 'Trying to edit',
                'description' => $event->description,
                'location' => $event->location,
                'city' => $event->city,
                'format' => $event->format->value,
                'starts_at' => $event->starts_at->toDateTimeString(),
                'ends_at' => $event->ends_at->toDateTimeString(),
            ]);

        $response->assertSessionHas('error');
    }

    public function test_organizer_can_delete_own_event(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->delete(route('organizer.events.destroy', $event));

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_organizer_edit_page_returns_200(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.edit', $event));

        $response->assertOk();
    }
}
