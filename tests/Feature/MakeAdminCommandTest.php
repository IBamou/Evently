<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_new_admin_account_when_the_user_does_not_exist(): void
    {
        $this->artisan('make:admin', ['email' => 'boss@evently.ma', 'password' => 'password123'])
            ->expectsQuestion('Name for the new admin account', 'Boss Evently')
            ->expectsOutputToContain('Admin account created for boss@evently.ma')
            ->assertExitCode(0);

        $user = User::where('email', 'boss@evently.ma')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function test_promotes_an_existing_user_after_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('make:admin', ['email' => 'existing@example.com', 'password' => 'password123'])
            ->expectsConfirmation('User existing@example.com already exists. Promote them to admin?', 'yes')
            ->expectsOutputToContain('has been promoted to admin')
            ->assertExitCode(0);

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_does_not_promote_when_the_user_declines(): void
    {
        $user = User::factory()->create(['email' => 'decline@example.com']);

        $this->artisan('make:admin', ['email' => 'decline@example.com', 'password' => 'password123'])
            ->expectsConfirmation('User decline@example.com already exists. Promote them to admin?')
            ->expectsOutputToContain('nothing changed')
            ->assertExitCode(0);

        $this->assertSame(UserRole::User, $user->fresh()->role);
    }

    public function test_reports_users_that_are_already_admins(): void
    {
        $user = User::factory()->asAdmin()->create(['email' => 'admin@example.com']);

        $this->artisan('make:admin', ['email' => 'admin@example.com', 'password' => 'password123'])
            ->expectsOutputToContain('is already an admin')
            ->assertExitCode(0);

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_rejects_an_invalid_email(): void
    {
        $this->artisan('make:admin', ['email' => 'not-an-email', 'password' => 'password123'])
            ->expectsOutputToContain('Invalid email address')
            ->assertExitCode(1);
    }
}
