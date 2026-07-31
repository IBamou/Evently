<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_register_as_regular_user_by_default(): void
    {
        $this->post('/register', [
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'default@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals(UserRole::User, $user->role);
    }

    public function test_users_can_register_as_organizer_by_checking_box(): void
    {
        $this->post('/register', [
            'name' => 'Organizer User',
            'email' => 'organizer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'organizer' => '1',
        ]);

        $user = User::where('email', 'organizer@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals(UserRole::Organizer, $user->role);
    }

    public function test_users_cannot_register_as_admin(): void
    {
        $this->post('/register', [
            'name' => 'Fake Admin',
            'email' => 'fakeadmin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $user = User::where('email', 'fakeadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotEquals(UserRole::Admin, $user->role);
        $this->assertEquals(UserRole::User, $user->role);
    }

    public function test_non_boolean_organizer_value_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'organizer' => 'admin',
        ]);

        $response->assertSessionHasErrors('organizer');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }
}
