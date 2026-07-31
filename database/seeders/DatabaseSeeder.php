<?php

namespace Database\Seeders;

use App\Enums\EventFormat;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test User (regular user)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Demo Organizer
        $organizer = User::factory()->asOrganizer()->create([
            'name' => 'Demo Organizer',
            'email' => 'demo-organizer@evently.test',
            'password' => bcrypt('password'),
        ]);

        // Demo Admin
        User::factory()->asAdmin()->create([
            'name' => 'Demo Admin',
            'email' => 'demo-admin@evently.test',
            'password' => bcrypt('password'),
        ]);

        // Categories
        $categories = collect([
            ['name' => 'Music', 'description' => 'Concerts, festivals, and live music events.'],
            ['name' => 'Business', 'description' => 'Conferences, networking, and business workshops.'],
            ['name' => 'Tech', 'description' => 'Tech talks, hackathons, and developer meetups.'],
            ['name' => 'Art', 'description' => 'Exhibitions, galleries, and creative showcases.'],
            ['name' => 'Sports', 'description' => 'Tournaments, matches, and sporting events.'],
            ['name' => 'Food & Drinks', 'description' => 'Food festivals, tastings, and culinary experiences.'],
        ])->map(fn (array $cat) => Category::create([
            'name' => $cat['name'],
            'slug' => Str::slug($cat['name']),
            'description' => $cat['description'],
        ]));

        // Sample events for the Demo Organizer
        // 1. Published upcoming (In person, Casablanca)
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(0)->id, // Music
            'title' => 'Casablanca Summer Music Festival',
            'slug' => 'casablanca-summer-music-festival',
            'description' => 'An electrifying three-day music festival featuring top Moroccan and international artists performing live under the stars in Casablanca.',
            'location' => 'Oasis Arena',
            'city' => 'Casablanca',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(14)->setTime(19, 0), // evening
            'ends_at' => now()->addDays(14)->setTime(23, 0),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);

        // 2. Published upcoming (Online, Rabat)
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(2)->id, // Tech
            'title' => 'Rabat Tech Summit 2026',
            'slug' => 'rabat-tech-summit-2026',
            'description' => 'The premier technology conference in Rabat bringing together innovators, developers, and industry leaders to discuss the future of tech.',
            'location' => 'Online',
            'city' => 'Rabat',
            'format' => EventFormat::Online,
            'starts_at' => now()->addDays(21)->setTime(10, 0), // morning
            'ends_at' => now()->addDays(21)->setTime(16, 0),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);

        // 3. Draft
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(5)->id, // Food & Drinks
            'title' => 'Marrakech Food Festival',
            'slug' => 'marrakech-food-festival',
            'description' => 'A celebration of Moroccan cuisine featuring local chefs, street food vendors, and interactive cooking workshops in the heart of Marrakech.',
            'location' => 'Jemaa el-Fnaa Square',
            'city' => 'Marrakech',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(30),
            'ends_at' => now()->addDays(30)->addHours(8),
            'status' => EventStatus::Draft,
            'banner_url' => null,
        ]);

        // 4. Under Review
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(3)->id, // Art
            'title' => 'Tangier Art Biennale',
            'slug' => 'tangier-art-biennale',
            'description' => 'A biennial art exhibition showcasing contemporary Moroccan and international artists across multiple venues in Tangier.',
            'location' => 'Tangier Art Center',
            'city' => 'Tangier',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(45),
            'ends_at' => now()->addDays(50),
            'status' => EventStatus::UnderReview,
            'banner_url' => null,
        ]);

        // 5. Cancelled
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(4)->id, // Sports
            'title' => 'Casablanca Marathon 2026',
            'slug' => 'casablanca-marathon-2026',
            'description' => 'The annual Casablanca Marathon attracting runners from around the world to race through the scenic streets of the city.',
            'location' => 'Casa Voyageurs Station',
            'city' => 'Casablanca',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(60),
            'ends_at' => now()->addDays(60)->addHours(6),
            'status' => EventStatus::Cancelled,
            'banner_url' => null,
        ]);

        // 6. Past published
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(1)->id, // Business
            'title' => 'Rabat Business Networking Night',
            'slug' => 'rabat-business-networking-night',
            'description' => 'An evening of networking and business development bringing together entrepreneurs and business leaders in Rabat.',
            'location' => 'Hyatt Regency Rabat',
            'city' => 'Rabat',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(10)->addHours(3),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);
    }
}
