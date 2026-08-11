<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteRestoreTest extends TestCase
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

    public function test_organizer_soft_deletes_own_event(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer)
            ->delete(route('organizer.events.destroy', $event));

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_deleted_event_excluded_from_public_listing(): void
    {
        $event = Event::factory()->published()->create(['organizer_id' => $this->organizer->id]);
        $event->delete();

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertDontSee($event->title);
    }

    public function test_deleted_event_excluded_from_own_listing(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $event->delete();

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.events.index'));

        $response->assertOk();
        $response->assertDontSee($event->title);
    }

    public function test_restore_via_admin_route(): void
    {
        $event = Event::factory()->published()->create(['organizer_id' => $this->organizer->id]);
        $event->delete();

        $this->assertSoftDeleted('events', ['id' => $event->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.events.restore', $event));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
        // Status should be preserved
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => EventStatus::Published->value]);
    }

    public function test_organizer_cannot_delete_others_event(): void
    {
        $otherEvent = Event::factory()->create();

        $response = $this->actingAs($this->organizer)
            ->delete(route('organizer.events.destroy', $otherEvent));

        $response->assertForbidden();
    }

    public function test_no_force_delete_route_exists(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutesByName());

        $hasForceDelete = $routes->contains(function ($route, $name): bool {
            return str_contains($name, 'forceDelete') || str_contains($name, 'force-delete');
        });

        $this->assertFalse($hasForceDelete, 'No force-delete route should exist.');
    }
}
