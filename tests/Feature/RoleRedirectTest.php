<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_redirected_to_their_profile_after_login(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('profile.edit'));
    }

    public function test_organizers_are_redirected_to_the_organizer_dashboard(): void
    {
        $organizer = User::factory()->create(['role' => UserRole::Organizer]);

        $response = $this->actingAs($organizer)->get('/dashboard');

        $response->assertRedirect(route('organizer.dashboard'));

        $this->actingAs($organizer)->get(route('organizer.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_admins_are_redirected_to_the_admin_console(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.events.index'));

        $this->actingAs($admin)->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('Admin console');
    }
}
