<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Seed the three demo accounts: regular user, organizer, and admin.
     */
    public function run(): void
    {
        // Demo User (regular user)
        User::factory()->create([
            'name' => 'Yassine Benali',
            'email' => 'demo-user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Demo Organizer
        User::factory()->asOrganizer()->create([
            'name' => 'Salma Lahlou',
            'email' => 'demo-organizer@evently.test',
            'password' => bcrypt('password'),
        ]);

        // Demo Admin
        User::factory()->asAdmin()->create([
            'name' => 'Admin Evently',
            'email' => 'demo-admin@evently.test',
            'password' => bcrypt('password'),
        ]);
    }
}
