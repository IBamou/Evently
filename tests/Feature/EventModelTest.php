<?php

use App\Enums\EventFormat;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_published_returns_true_for_published_event(): void
    {
        $event = Event::factory()->published()->create();

        $this->assertTrue($event->isPublished());
    }

    public function test_is_published_returns_false_for_draft_event(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);

        $this->assertFalse($event->isPublished());
    }

    public function test_is_upcoming_returns_true_for_future_event(): void
    {
        $event = Event::factory()->create(['starts_at' => now()->addDays(5)]);

        $this->assertTrue($event->isUpcoming());
    }

    public function test_is_upcoming_returns_false_for_past_event(): void
    {
        $event = Event::factory()->create(['starts_at' => now()->subDays(5)]);

        $this->assertFalse($event->isUpcoming());
    }

    public function test_is_bookable_requires_published_and_upcoming_and_not_trashed(): void
    {
        $event = Event::factory()->published()->create(['starts_at' => now()->addDays(5)]);

        $this->assertTrue($event->isBookable());
    }

    public function test_is_bookable_returns_false_for_draft(): void
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Draft,
            'starts_at' => now()->addDays(5),
        ]);

        $this->assertFalse($event->isBookable());
    }

    public function test_scope_published_filters_correctly(): void
    {
        Event::factory()->published()->count(3)->create();
        Event::factory()->count(2)->create(['status' => EventStatus::Draft]);

        $published = Event::query()->published()->get();

        $this->assertCount(3, $published);
    }

    public function test_event_status_enum_labels(): void
    {
        $this->assertSame('Draft', EventStatus::Draft->label());
        $this->assertSame('Under review', EventStatus::UnderReview->label());
        $this->assertSame('Published', EventStatus::Published->label());
        $this->assertSame('Cancelled', EventStatus::Cancelled->label());
    }

    public function test_event_status_enum_helpers(): void
    {
        $this->assertTrue(EventStatus::Draft->isDraft());
        $this->assertFalse(EventStatus::Draft->isPublished());

        $this->assertTrue(EventStatus::UnderReview->isUnderReview());
        $this->assertFalse(EventStatus::UnderReview->isDraft());

        $this->assertTrue(EventStatus::Published->isPublished());
        $this->assertFalse(EventStatus::Published->isCancelled());

        $this->assertTrue(EventStatus::Cancelled->isCancelled());
        $this->assertFalse(EventStatus::Cancelled->isDraft());
    }

    public function test_event_format_enum_labels(): void
    {
        $this->assertSame('In person', EventFormat::InPerson->label());
        $this->assertSame('Online', EventFormat::Online->label());
    }

    public function test_event_belongs_to_organizer(): void
    {
        $event = Event::factory()->create();

        $this->assertNotNull($event->organizer);
        $this->assertSame($event->organizer_id, $event->organizer->id);
    }

    public function test_event_belongs_to_category(): void
    {
        $event = Event::factory()->create();

        $this->assertNotNull($event->category);
        $this->assertSame($event->category_id, $event->category->id);
    }

    public function test_event_uses_soft_deletes(): void
    {
        $event = Event::factory()->create();

        $event->delete();

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::withTrashed()->find($event->id));
    }
}
