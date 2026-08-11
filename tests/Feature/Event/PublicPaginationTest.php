<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create enough events to test pagination
    Event::factory()->published()->count(60)->create();
});

test('per_page=1 returns 200 with 1 result', function () {
    $response = $this->get(route('events.index', ['per_page' => 1]));
    $response->assertOk();
    $events = $response->viewData('events');
    $this->assertCount(1, $events);
});

test('per_page=20 returns 200 with 20 results', function () {
    $response = $this->get(route('events.index', ['per_page' => 20]));
    $response->assertOk();
    $events = $response->viewData('events');
    $this->assertCount(20, $events);
});

test('per_page=50 returns 200 with 50 results', function () {
    $response = $this->get(route('events.index', ['per_page' => 50]));
    $response->assertOk();
    $events = $response->viewData('events');
    $this->assertCount(50, $events);
});

test('per_page=0 returns 200 with sane page size', function () {
    $response = $this->get(route('events.index', ['per_page' => 0]));
    $response->assertOk();
    $events = $response->viewData('events');
    // 0 gets cast to int 0, then max(1, min(0, 50)) = 1
    $this->assertCount(1, $events);
});

test('per_page=-1 returns 200 with sane page size', function () {
    $response = $this->get(route('events.index', ['per_page' => -1]));
    $response->assertOk();
    $events = $response->viewData('events');
    // -1 gets cast to int -1, then max(1, min(-1, 50)) = 1
    $this->assertCount(1, $events);
});

test('per_page=999999 returns 200 capped at 50', function () {
    $response = $this->get(route('events.index', ['per_page' => 999999]));
    $response->assertOk();
    $events = $response->viewData('events');
    $this->assertCount(50, $events);
});

test('per_page=abc returns 200 with sane page size', function () {
    $response = $this->get(route('events.index', ['per_page' => 'abc']));
    $response->assertOk();
    $events = $response->viewData('events');
    // 'abc' cast to int = 0, then max(1, min(0, 50)) = 1
    $this->assertCount(1, $events);
});

test('admin events index per_page=0 returns 200', function () {
    $admin = User::factory()->asAdmin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.events.index', ['per_page' => 0]));
    $response->assertOk();
    $events = $response->viewData('events');
    // 0 gets cast to int 0, then max(1, min(0, 50)) = 1
    $this->assertCount(1, $events);
});

test('default per_page returns 15 results', function () {
    $response = $this->get(route('events.index'));
    $response->assertOk();
    $events = $response->viewData('events');
    $this->assertCount(15, $events);
});
