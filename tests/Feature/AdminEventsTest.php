<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
    }

    public function test_admin_lists_all_events(): void
    {
        Event::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.events.index'));

        $response->assertOk();
    }

    public function test_admin_can_publish_event(): void
    {
        $event = Event::factory()->underReview()->create([
            'organizer_id' => User::factory()->asOrganizer()->create()->id,
            'description' => 'This event description is long enough to pass the twenty character minimum validation check.',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.publish', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Published->value]);
    }

    public function test_admin_can_reject_event(): void
    {
        $event = Event::factory()->underReview()->create([
            'organizer_id' => User::factory()->asOrganizer()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.reject', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Draft->value]);
    }

    public function test_admin_can_cancel_event(): void
    {
        $event = Event::factory()->published()->create([
            'organizer_id' => User::factory()->asOrganizer()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.cancel', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Cancelled->value]);
    }

    public function test_admin_can_delete_event(): void
    {
        $event = Event::factory()->create([
            'organizer_id' => User::factory()->asOrganizer()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.events.destroy', $event));

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_admin_can_restore_event(): void
    {
        $event = Event::factory()->published()->create([
            'organizer_id' => User::factory()->asOrganizer()->create()->id,
        ]);
        $event->delete();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.restore', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_create_events(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutesByName());

        $this->assertFalse(
            $routes->has('admin.events.store'),
            'Admin should not have a route to create events.'
        );
    }

    public function test_post_admin_events_returns_method_not_allowed(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post('/admin/events', [
                'category_id' => $category->id,
                'title' => 'Should Not Work',
                'description' => 'This should fail because the route does not exist.',
                'location' => 'Somewhere',
                'city' => 'Casablanca',
                'format' => 'in_person',
                'starts_at' => now()->addDays(10),
                'ends_at' => now()->addDays(10)->addHours(3),
            ]);

        $response->assertStatus(405);
    }

    public function test_admin_index_returns_view_data(): void
    {
        Event::factory()->count(3)->create();
        $underReview = Event::factory()->underReview()->create([
            'organizer_id' => User::factory()->asOrganizer()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.events.index'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('published', $stats);
        $this->assertArrayHasKey('under_review', $stats);
    }
}
