<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_returns_200(): void
    {
        Event::factory()->published()->count(3)->create();

        $response = $this->get(route('events.index'));

        $response->assertOk();
    }

    public function test_public_index_renders_home_view(): void
    {
        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertViewIs('home');
    }

    public function test_show_published_event_returns_200(): void
    {
        $event = Event::factory()->published()->create();

        $response = $this->get(route('events.show', $event->slug));

        $response->assertOk();
    }

    public function test_show_draft_event_returns_404(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);

        $response = $this->get(route('events.show', $event->slug));

        $response->assertNotFound();
    }

    public function test_show_cancelled_event_returns_404(): void
    {
        $event = Event::factory()->cancelled()->create();

        $response = $this->get(route('events.show', $event->slug));

        $response->assertNotFound();
    }

    public function test_show_deleted_event_returns_404(): void
    {
        $event = Event::factory()->published()->create();
        $event->delete();

        $response = $this->get(route('events.show', $event->slug));

        $response->assertNotFound();
    }

    public function test_show_event_not_found_for_invalid_slug(): void
    {
        $response = $this->get(route('events.show', 'nonexistent-event'));

        $response->assertNotFound();
    }

    public function test_public_index_valid_sort_does_not_error(): void
    {
        $response = $this->get(route('events.index', ['sort' => '-starts_at']));
        $response->assertOk();
    }

    public function test_public_index_provides_view_data_keys(): void
    {
        Event::factory()->published()->count(3)->create();

        $response = $this->get(route('events.index'));
        $response->assertOk();

        // Verify the controller passes the expected variables to the view
        $this->assertNotNull($response->viewData('events'));
        $this->assertNotNull($response->viewData('featured'));
        $this->assertNotNull($response->viewData('categories'));
        $this->assertNotNull($response->viewData('filters'));
        $this->assertNotNull($response->viewData('cities'));
    }

    public function test_show_event_renders_correct_view(): void
    {
        $event = Event::factory()->published()->create();

        $response = $this->get(route('events.show', $event->slug));

        $response->assertOk();
        $response->assertViewIs('events.show');
    }

    public function test_show_event_loads_organizer_and_category_relationships(): void
    {
        $event = Event::factory()->published()->create();

        $response = $this->get(route('events.show', $event->slug));
        $response->assertOk();

        $viewEvent = $response->viewData('event');
        $this->assertNotNull($viewEvent->organizer);
        $this->assertNotNull($viewEvent->category);
        $this->assertSame($event->organizer_id, $viewEvent->organizer->id);
        $this->assertSame($event->category_id, $viewEvent->category->id);
    }

    public function test_show_event_provides_related_upcoming_same_category_events(): void
    {
        $category = Category::factory()->create();
        $event = Event::factory()->published()->create(['category_id' => $category->id]);

        $related = Event::factory()->published()->create([
            'category_id' => $category->id,
            'starts_at' => now()->addDays(2),
        ]);
        Event::factory()->published()->create([
            'category_id' => $category->id,
            'starts_at' => now()->subDays(2),
        ]);
        $otherCategory = Event::factory()->published()->create();

        $response = $this->get(route('events.show', $event->slug));
        $response->assertOk();

        $relatedEvents = $response->viewData('related');
        $this->assertTrue($relatedEvents->contains('id', $related->id));
        $this->assertFalse($relatedEvents->contains('id', $otherCategory->id));
        $this->assertCount(1, $relatedEvents);
    }

    public function test_public_index_provides_featured_upcoming_only(): void
    {
        // Past published event should NOT be in featured
        Event::factory()->published()->create([
            'starts_at' => now()->subDays(5),
        ]);

        // Future published event should be in featured
        $upcoming = Event::factory()->published()->create([
            'starts_at' => now()->addDays(5),
        ]);

        $response = $this->get(route('events.index'));
        $response->assertOk();

        $featured = $response->viewData('featured');
        // Featured only contains upcoming events
        foreach ($featured as $event) {
            $this->assertTrue($event->starts_at->isFuture());
        }
    }

    public function test_public_index_cities_are_distinct(): void
    {
        Event::factory()->published()->create(['city' => 'Casablanca']);
        Event::factory()->published()->create(['city' => 'Casablanca']);
        Event::factory()->published()->create(['city' => 'Rabat']);

        $response = $this->get(route('events.index'));
        $response->assertOk();

        $cities = $response->viewData('cities');
        $this->assertCount(2, $cities);
        $this->assertEquals(['Casablanca', 'Rabat'], $cities->sort()->values()->toArray());
    }

    public function test_public_index_featured_capped_at_three(): void
    {
        Event::factory()->published()->count(6)->create([
            'starts_at' => now()->addDays(rand(1, 30)),
        ]);

        $response = $this->get(route('events.index'));
        $response->assertOk();

        $featured = $response->viewData('featured');
        $this->assertLessThanOrEqual(3, $featured->count());
    }

    public function test_public_index_per_page_defaults_to_15(): void
    {
        Event::factory()->published()->count(20)->create();

        $response = $this->get(route('events.index'));
        $response->assertOk();

        $paginator = $response->viewData('events');
        $this->assertSame(15, $paginator->perPage());
    }

    public function test_public_index_per_page_capped_at_50(): void
    {
        Event::factory()->published()->count(55)->create();

        $response = $this->get(route('events.index', ['per_page' => 100]));
        $response->assertOk();

        $paginator = $response->viewData('events');
        $this->assertSame(50, $paginator->perPage());
    }

    public function test_public_index_filters_by_category_slug(): void
    {
        $music = Category::factory()->create(['slug' => 'music', 'name' => 'Music']);
        $tech = Category::factory()->create(['slug' => 'tech', 'name' => 'Tech']);

        Event::factory()->published()->create(['category_id' => $music->id, 'title' => 'Guitar Night']);
        Event::factory()->published()->create(['category_id' => $tech->id, 'title' => 'AI Summit']);

        $response = $this->get(route('events.index', ['category' => 'music']));
        $response->assertOk();

        $events = $response->viewData('events');
        $this->assertSame(1, $events->total());
        $this->assertSame('Guitar Night', $events->first()->title);
    }

    public function test_public_index_unknown_category_returns_empty_results(): void
    {
        Event::factory()->published()->create(['title' => 'Only Event']);

        $response = $this->get(route('events.index', ['category' => 'nonexistent']));
        $response->assertOk();

        $this->assertSame(0, $response->viewData('events')->total());
    }

    public function test_public_index_filters_by_format(): void
    {
        Event::factory()->published()->create(['format' => 'online', 'title' => 'Webinar']);
        Event::factory()->published()->create(['format' => 'in_person', 'title' => 'Live Show']);

        $response = $this->get(route('events.index', ['format' => 'online']));
        $response->assertOk();

        $events = $response->viewData('events');
        $this->assertSame(1, $events->total());
        $this->assertSame('Webinar', $events->first()->title);
    }

    public function test_public_index_invalid_format_is_ignored(): void
    {
        Event::factory()->published()->create(['title' => 'Only Event']);

        $response = $this->get(route('events.index', ['format' => 'hologram']));
        $response->assertOk();

        $this->assertSame(1, $response->viewData('events')->total());
    }

    public function test_public_index_filters_by_time_of_day(): void
    {
        Event::factory()->published()->create(['starts_at' => now()->addDays(1)->setTime(9, 0), 'title' => 'Morning Meetup']);
        Event::factory()->published()->create(['starts_at' => now()->addDays(1)->setTime(19, 0), 'title' => 'Night Gig']);

        $response = $this->get(route('events.index', ['time' => 'evening']));
        $response->assertOk();

        $events = $response->viewData('events');
        $this->assertSame(1, $events->total());
        $this->assertSame('Night Gig', $events->first()->title);
    }
}
