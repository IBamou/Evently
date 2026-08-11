<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_a_role_protected_route(): void
    {
        Route::middleware('role:admin')->get('/_role-test', fn () => response('ok'));

        $response = $this->get('/_role-test');

        $response->assertRedirect(route('login'));
    }

    public function test_user_with_a_non_matching_role_gets_a_403_response(): void
    {
        Route::middleware('role:admin')->get('/_role-test', fn () => response('ok'));

        $user = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($user)->get('/_role-test');

        $response->assertForbidden();
    }

    public function test_user_with_a_matching_role_can_access_the_route(): void
    {
        Route::middleware('role:admin')->get('/_role-test', fn () => response('ok'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/_role-test');

        $response->assertOk();
    }

    public function test_roles_accept_enum_names_in_addition_to_values(): void
    {
        Route::middleware('role:Admin')->get('/_role-test-name', fn () => response('ok'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/_role-test-name');

        $response->assertOk();
    }

    public function test_multiple_allowed_roles_are_accepted(): void
    {
        Route::middleware('role:organizer,admin')->get('/_role-test-multi', fn () => response('ok'));

        $organizer = User::factory()->create(['role' => UserRole::Organizer]);

        $response = $this->actingAs($organizer)->get('/_role-test-multi');

        $response->assertOk();
    }
}
