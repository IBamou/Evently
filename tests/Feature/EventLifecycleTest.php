<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->asOrganizer()->create();
        $this->admin = User::factory()->asAdmin()->create();
    }

    public function test_submit_draft_to_under_review(): void
    {
        $event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => EventStatus::Draft,
        ]);

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.submit', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::UnderReview->value]);
    }

    public function test_submit_non_draft_event_fails(): void
    {
        $event = Event::factory()->underReview()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.submit', $event));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::UnderReview->value]);
    }

    public function test_admin_publishes_under_review_event(): void
    {
        $event = Event::factory()->underReview()->create([
            'organizer_id' => $this->organizer->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
            'description' => 'This event description is long enough to pass the twenty character minimum validation check.',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.publish', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Published->value]);
    }

    public function test_publish_already_published_event_fails(): void
    {
        $event = Event::factory()->published()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.publish', $event));

        $response->assertSessionHas('error');
    }

    public function test_publish_cancelled_event_fails(): void
    {
        $event = Event::factory()->cancelled()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.publish', $event));

        $response->assertSessionHas('error');
    }

    public function test_publish_started_event_fails(): void
    {
        $event = Event::factory()->underReview()->started()->create([
            'organizer_id' => $this->organizer->id,
            'description' => 'This event description is long enough to pass the twenty character minimum validation check.',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.publish', $event));

        $response->assertSessionHas('error');
    }

    public function test_publish_incomplete_event_fails(): void
    {
        $event = Event::factory()->underReview()->create([
            'organizer_id' => $this->organizer->id,
            'description' => 'Short',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.publish', $event));

        $response->assertSessionHas('error');
    }

    public function test_admin_rejects_event_to_draft(): void
    {
        $event = Event::factory()->underReview()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.reject', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Draft->value]);
    }

    public function test_cancel_published_event(): void
    {
        $event = Event::factory()->published()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.cancel', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Cancelled->value]);
    }

    public function test_cancel_draft_event_fails(): void
    {
        $event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => EventStatus::Draft,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.cancel', $event));

        $response->assertSessionHas('error');
    }

    public function test_cancel_already_cancelled_event_fails(): void
    {
        $event = Event::factory()->cancelled()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.cancel', $event));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Cancelled->value]);
    }

    public function test_organizer_can_cancel_own_published_event(): void
    {
        $event = Event::factory()->published()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.cancel', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Cancelled->value]);
    }

    public function test_organizer_cannot_cancel_another_organizers_event(): void
    {
        $other = User::factory()->asOrganizer()->create();
        $event = Event::factory()->published()->create(['organizer_id' => $other->id]);

        $response = $this->actingAs($this->organizer)
            ->post(route('organizer.events.cancel', $event));

        $response->assertForbidden();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Published->value]);
    }

    public function test_organizer_cannot_publish_event(): void
    {
        $event = Event::factory()->underReview()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->post(route('admin.events.publish', $event));

        $response->assertForbidden();
    }

    public function test_organizer_cannot_reject_event(): void
    {
        $event = Event::factory()->underReview()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->post(route('admin.events.reject', $event));

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_submit_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($user)
            ->post(route('organizer.events.submit', $event));

        $response->assertForbidden();
    }

    // ── Event update lifecycle tests ──

    public function test_future_to_future_update_allowed(): void
    {
        $event = Event::factory()->published()->create([
            'organizer_id' => $this->organizer->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'title' => 'Updated Title',
                'description' => str_repeat('A long enough description for the validation rule to pass. ', 3),
                'location' => 'Updated Location',
                'city' => 'Updated City',
                'format' => 'in_person',
                'starts_at' => now()->addDays(15)->toDateTimeString(),
                'ends_at' => now()->addDays(15)->addHours(3)->toDateTimeString(),
                'category_id' => $event->category_id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Title']);
    }

    public function test_future_to_past_starts_at_rejected(): void
    {
        $event = Event::factory()->published()->create([
            'organizer_id' => $this->organizer->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'description' => str_repeat('A long enough description for the validation rule to pass. ', 3),
                'location' => 'Updated Location',
                'city' => 'Updated City',
                'format' => 'in_person',
                'starts_at' => now()->subDays(1)->toDateTimeString(),
                'ends_at' => now()->addDays(1)->toDateTimeString(),
                'category_id' => $event->category_id,
            ]);

        $response->assertSessionHasErrors('starts_at');
    }

    public function test_ends_at_before_starts_at_rejected(): void
    {
        $event = Event::factory()->published()->create([
            'organizer_id' => $this->organizer->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'description' => str_repeat('A long enough description for the validation rule to pass. ', 3),
                'location' => 'Updated Location',
                'city' => 'Updated City',
                'format' => 'in_person',
                'starts_at' => now()->addDays(10)->toDateTimeString(),
                'ends_at' => now()->addDays(9)->toDateTimeString(),
                'category_id' => $event->category_id,
            ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_valid_partial_update_without_date_fields_allowed(): void
    {
        $event = Event::factory()->published()->create([
            'organizer_id' => $this->organizer->id,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'title' => 'Just Title Change',
                'description' => str_repeat('A long enough description for the validation rule to pass. ', 3),
                'location' => 'Updated Location',
                'city' => 'Updated City',
                'format' => 'in_person',
                'category_id' => $event->category_id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Just Title Change']);
    }

    public function test_already_started_event_still_blocked(): void
    {
        $event = Event::factory()->published()->started()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $response = $this->actingAs($this->organizer)
            ->patch(route('organizer.events.update', $event), [
                'title' => 'Should Not Work',
                'description' => str_repeat('A long enough description for the validation rule to pass. ', 3),
                'location' => 'Updated Location',
                'city' => 'Updated City',
                'format' => 'in_person',
                'starts_at' => now()->addDays(20)->toDateTimeString(),
                'ends_at' => now()->addDays(20)->addHours(3)->toDateTimeString(),
                'category_id' => $event->category_id,
            ]);

        $response->assertSessionHas('error');
    }
}
