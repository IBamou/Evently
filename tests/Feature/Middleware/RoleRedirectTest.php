<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects attendees to event discovery', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('events.index'));
});

it('redirects organizers to their workspace dashboard', function () {
    $organizer = User::factory()->asOrganizer()->create();

    $this->actingAs($organizer)
        ->get(route('dashboard'))
        ->assertRedirect(route('organizer.dashboard'));

    $this->actingAs($organizer)
        ->get(route('organizer.dashboard'))
        ->assertOk()
        ->assertSee('Welcome back');
});

it('redirects admins to the platform dashboard', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin command center');
});

it('redirects guests to sign in', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
