<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: users → categories → events → ticket types → demo bookings.
     */
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            CategoriesSeeder::class,
            EventsSeeder::class,
            TicketTypesSeeder::class,
            DemoBookingsSeeder::class,
        ]);
    }
}
