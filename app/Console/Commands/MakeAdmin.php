<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:admin {email : Email of the admin user} {password? : Password (prompted if omitted)}';

    /**
     * The console command description.
     */
    protected $description = 'Set a user as admin: promote an existing account or create a new admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$email}");

            return self::FAILURE;
        }

        $password = $this->argument('password') ?? $this->secret('Password for the admin account');

        if ($this->validatePassword($password)) {
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user instanceof User) {
            // Account exists: prompt before promoting.
            if ($user->role === UserRole::Admin) {
                $this->info("{$email} is already an admin.");

                return self::SUCCESS;
            }

            if (! $this->confirm("User {$email} already exists. Promote them to admin?", false)) {
                $this->info('Aborted - nothing changed.');

                return self::SUCCESS;
            }

            $user->role = UserRole::Admin;
            $user->save();

            $this->info("{$email} has been promoted to admin.");

            return self::SUCCESS;
        }

        // Account does not exist: create it as admin.
        $defaultName = ucfirst(strtok($email, '@') ?: 'Admin');
        $name = $this->ask('Name for the new admin account', $defaultName);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
        ]);

        $this->info("Admin account created for {$email}.");

        return self::SUCCESS;
    }

    /**
     * Validate the password against the app's password rules.
     */
    private function validatePassword(?string $password): bool
    {
        if (is_null($password) || $password === '') {
            $this->error('A password is required.');

            return true;
        }

        $validator = validator(['password' => $password], [
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return true;
        }

        return false;
    }
}
