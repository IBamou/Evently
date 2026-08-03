<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\EventFormat;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
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
        $user = User::factory()->create([
            'name' => 'Yassine Benali',
            'email' => 'test@example.com',
        ]);

        // Demo Organizer
        $organizer = User::factory()->asOrganizer()->create([
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

        // ── 1. Festival (published, upcoming, music) ──
        $festival = Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(0)->id,
            'title' => 'Casablanca Summer Music Festival',
            'slug' => 'casablanca-summer-music-festival',
            'description' => 'An electrifying three-day music festival featuring top Moroccan and international artists performing live under the stars in Casablanca.',
            'location' => 'Oasis Arena',
            'city' => 'Casablanca',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(14)->setTime(19, 0),
            'ends_at' => now()->addDays(14)->setTime(23, 0),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);

        $earlyBird = TicketType::create([
            'event_id' => $festival->id,
            'name' => 'Early Bird',
            'description' => 'Limited early bird pricing',
            'price' => 250.00,
            'currency' => 'MAD',
            'quantity' => 200,
            'min_per_booking' => 1,
            'max_per_booking' => 10,
            'sales_start_at' => now()->subDays(7),
            'sales_end_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        $general = TicketType::create([
            'event_id' => $festival->id,
            'name' => 'General Admission',
            'description' => 'Standard entry',
            'price' => 350.00,
            'currency' => 'MAD',
            'quantity' => 500,
            'min_per_booking' => 1,
            'max_per_booking' => 10,
            'sales_start_at' => now()->subDays(3),
            'sales_end_at' => now()->addDays(13),
            'is_active' => true,
        ]);

        $vip = TicketType::create([
            'event_id' => $festival->id,
            'name' => 'VIP',
            'description' => 'VIP access with backstage pass',
            'price' => 600.00,
            'currency' => 'MAD',
            'quantity' => 50,
            'min_per_booking' => 1,
            'max_per_booking' => 4,
            'sales_start_at' => now()->subDays(2),
            'sales_end_at' => now()->addDays(13),
            'is_active' => true,
        ]);

        // ── 2. Summit (published, upcoming, tech) ──
        $summit = Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(2)->id,
            'title' => 'Rabat Tech Summit 2026',
            'slug' => 'rabat-tech-summit-2026',
            'description' => 'The premier technology conference in Rabat bringing together innovators, developers, and industry leaders.',
            'location' => 'Online',
            'city' => 'Rabat',
            'format' => EventFormat::Online,
            'starts_at' => now()->addDays(21)->setTime(10, 0),
            'ends_at' => now()->addDays(21)->setTime(16, 0),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);

        TicketType::create([
            'event_id' => $summit->id,
            'name' => 'Standard',
            'description' => 'Full conference access',
            'price' => 200.00,
            'currency' => 'MAD',
            'quantity' => 300,
            'min_per_booking' => 1,
            'max_per_booking' => 15,
            'sales_start_at' => now()->subDays(5),
            'sales_end_at' => now()->addDays(20),
            'is_active' => true,
        ]);

        // ── 3. Biennale (published, upcoming, art) ──
        $biennale = Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(3)->id,
            'title' => 'Tangier Art Biennale',
            'slug' => 'tangier-art-biennale',
            'description' => 'A biennial art exhibition showcasing contemporary Moroccan and international artists.',
            'location' => 'Tangier Art Center',
            'city' => 'Tangier',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(45),
            'ends_at' => now()->addDays(50),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);

        TicketType::create([
            'event_id' => $biennale->id,
            'name' => 'Standard',
            'description' => 'General admission',
            'price' => 150.00,
            'currency' => 'MAD',
            'quantity' => 150,
            'min_per_booking' => 1,
            'max_per_booking' => 10,
            'sales_start_at' => now()->subDays(10),
            'sales_end_at' => now()->addDays(44),
            'is_active' => true,
        ]);

        // ── Non-published events (for seed completeness) ──
        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(5)->id,
            'title' => 'Marrakech Food Festival',
            'slug' => 'marrakech-food-festival',
            'description' => 'A celebration of Moroccan cuisine featuring local chefs and street food vendors.',
            'location' => 'Jemaa el-Fnaa Square',
            'city' => 'Marrakech',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->addDays(30),
            'ends_at' => now()->addDays(30)->addHours(8),
            'status' => EventStatus::Draft,
            'banner_url' => null,
        ]);

        Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categories->get(1)->id,
            'title' => 'Rabat Business Networking Night',
            'slug' => 'rabat-business-networking-night',
            'description' => 'An evening of networking and business development in Rabat.',
            'location' => 'Hyatt Regency Rabat',
            'city' => 'Rabat',
            'format' => EventFormat::InPerson,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(10)->addHours(3),
            'status' => EventStatus::Published,
            'banner_url' => null,
        ]);

        // ── Realistic demo bookings for the festival ──

        // Booking 1: Confirmed + paid (2 Early Bird + 1 VIP), one ticket used
        $b1 = Booking::create([
            'reference' => 'IEV-4C19A700',
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 1100.00,
            'fees' => 0,
            'total' => 1100.00,
            'currency' => 'MAD',
            'expires_at' => null,
            'confirmed_at' => now()->subDays(2),
            'cancelled_at' => null,
        ]);

        $bi1a = BookingItem::create([
            'booking_id' => $b1->id,
            'ticket_type_id' => $earlyBird->id,
            'ticket_name' => 'Early Bird',
            'unit_price' => 250.00,
            'quantity' => 2,
            'line_total' => 500.00,
        ]);

        BookingItem::create([
            'booking_id' => $b1->id,
            'ticket_type_id' => $vip->id,
            'ticket_name' => 'VIP',
            'unit_price' => 600.00,
            'quantity' => 1,
            'line_total' => 600.00,
        ]);

        Payment::create([
            'booking_id' => $b1->id,
            'provider' => 'manual',
            'status' => PaymentStatus::Succeeded,
            'amount' => 1100.00,
            'currency' => 'MAD',
            'paid_at' => now()->subDays(2),
        ]);

        // Tickets for booking 1
        $ticket1 = Ticket::create([
            'booking_id' => $b1->id,
            'booking_item_id' => $bi1a->id,
            'ticket_type_id' => $earlyBird->id,
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'code' => 'T-EB1A2C3D4F',
            'status' => TicketStatus::Used,
            'issued_at' => now()->subDays(2),
            'checked_in_at' => now()->subHour(),
            'checked_in_by' => $organizer->id,
        ]);

        Ticket::create([
            'booking_id' => $b1->id,
            'booking_item_id' => $bi1a->id,
            'ticket_type_id' => $earlyBird->id,
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'code' => 'T-EB5G6H7I8J',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(2),
        ]);

        Ticket::create([
            'booking_id' => $b1->id,
            'booking_item_id' => BookingItem::where('booking_id', $b1->id)->where('ticket_type_id', $vip->id)->first()->id,
            'ticket_type_id' => $vip->id,
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'code' => 'T-VP9K0L1M2N',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(2),
        ]);

        // Booking 2: Confirmed + paid (3 General Admission)
        $b2 = Booking::create([
            'reference' => 'IEV-77B21000',
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 1050.00,
            'fees' => 0,
            'total' => 1050.00,
            'currency' => 'MAD',
            'expires_at' => null,
            'confirmed_at' => now()->subDay(),
            'cancelled_at' => null,
        ]);

        $bi2 = BookingItem::create([
            'booking_id' => $b2->id,
            'ticket_type_id' => $general->id,
            'ticket_name' => 'General Admission',
            'unit_price' => 350.00,
            'quantity' => 3,
            'line_total' => 1050.00,
        ]);

        Payment::create([
            'booking_id' => $b2->id,
            'provider' => 'manual',
            'status' => PaymentStatus::Succeeded,
            'amount' => 1050.00,
            'currency' => 'MAD',
            'paid_at' => now()->subDay(),
        ]);

        for ($i = 0; $i < 3; $i++) {
            Ticket::create([
                'booking_id' => $b2->id,
                'booking_item_id' => $bi2->id,
                'ticket_type_id' => $general->id,
                'user_id' => $user->id,
                'event_id' => $festival->id,
                'code' => 'T-GA'.str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                'status' => TicketStatus::Valid,
                'issued_at' => now()->subDay(),
            ]);
        }

        // Booking 3: Pending + paid (waiting for confirmation)
        Booking::create([
            'reference' => 'IEV-2E90FF00',
            'user_id' => $user->id,
            'event_id' => $summit->id,
            'status' => BookingStatus::Pending,
            'subtotal' => 400.00,
            'fees' => 0,
            'total' => 400.00,
            'currency' => 'MAD',
            'expires_at' => now()->addMinutes(10),
            'confirmed_at' => null,
            'cancelled_at' => null,
        ]);

        // Booking 4: Cancelled
        Booking::create([
            'reference' => 'IEV-19AA3100',
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'status' => BookingStatus::Cancelled,
            'subtotal' => 350.00,
            'fees' => 0,
            'total' => 350.00,
            'currency' => 'MAD',
            'expires_at' => null,
            'confirmed_at' => now()->subDays(5),
            'cancelled_at' => now()->subDays(1),
        ]);
    }
}
