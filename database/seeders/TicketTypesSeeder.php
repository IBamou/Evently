<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypesSeeder extends Seeder
{
    /**
     * Seed the application's ticket types for all events that sell tickets.
     *
     * Events are looked up by slug so this seeder is independent from the
     * in-memory references used while creating the events themselves.
     *
     * Only published and under_review events receive ticket types; draft and
     * cancelled events intentionally get none.
     */
    public function run(): void
    {
        $ticketTypes = [
            // ── Flagship published events ──
            'casablanca-summer-music-festival' => [
                ['general', 'General Admission', 'Standard entry to the festival grounds.', 25.00, 500],
                ['vip', 'VIP Pass', 'Front stage area, dedicated bar and fast lane.', 55.00, 100],
            ],
            'rabat-tech-summit-2026' => [
                ['general', 'General Ticket', 'Access to all talks, expo floor and lunch.', 40.00, 500],
                ['workshop', 'Workshop Add-on', 'Hands-on workshop evening session.', 20.00, 150],
            ],
            'marrakech-street-food-tour' => [
                ['standard', 'Standard Tour', 'Guided tour with 8 tasting stops.', 15.00, 30],
            ],
            'casablanca-film-festival' => [
                ['daypass', 'Day Pass', 'Access to daily screenings (choose your date later).', 12.00, 200],
            ],
            'remote-careers-summit' => [
                ['live', 'Live Ticket', 'Access to the live streaming + replays for 30 days.', 9.00, 999],
            ],
            // ── Batch #2 (published / under review get types; draft & cancelled get none) ──
            'casablanca-rooftop-jazz-sessions' => [
                ['early_bird', 'Early Bird', 'Limited discounted admission.', 100.00, 80],
                ['general', 'General', 'Standard event access.', 150.00, 220],
                ['vip', 'VIP', 'Reserved seating and welcome drink.', 280.00, 50],
            ],
            'rabat-founders-innovation-forum' => [
                ['student', 'Student', 'Discounted access for students.', 50.00, 120],
                ['general', 'General', 'Access to talks and networking sessions.', 120.00, 250],
            ],
            'morocco-ai-builders-live' => [
                ['online', 'Online Access', 'Full access to the livestream and Q&A.', 60.00, 500],
            ],
            'marrakech-contemporary-art-after-dark' => [
                ['general', 'General', 'Access to exhibitions and performances.', 90.00, 180],
                ['supporter', 'Supporter', 'Event access plus artist catalogue.', 180.00, 60],
            ],
            'agadir-atlantic-night-run' => [
                ['5k', '5K Entry', 'Entry for the 5 km race.', 80.00, 300],
                ['10k', '10K Entry', 'Entry for the 10 km race.', 120.00, 250],
            ],
            'fes-flavours-culinary-evening' => [
                ['tasting', 'Tasting Pass', 'Access to the tasting experience.', 180.00, 120],
                ['premium', 'Premium Table', 'Reserved seating with extended tasting menu.', 350.00, 40],
            ],
            'tangier-digital-creators-summit' => [
                ['general', 'General Access', 'Access to all online sessions.', 75.00, 500],
                ['creator', 'Creator Pass', 'All sessions plus post-event recordings.', 140.00, 200],
            ],
            'el-jadida-moroccan-street-food-festival' => [
                ['general', 'General', 'Festival entry.', 50.00, 500],
                ['tasting', 'Tasting Pass', 'Festival entry with tasting vouchers.', 150.00, 250],
            ],
            // No types: essaouira-sunset-surf-gathering (cancelled), dakhla-remote-business-masterclass (draft)
            // ── Batch #3 (published / under review get types; draft & cancelled get none) ──
            'chefchaouen-indie-music-evening' => [
                ['general', 'General', 'Standard event access.', 100.00, 160],
                ['premium', 'Premium', 'Reserved seating with early entry.', 180.00, 50],
            ],
            'casablanca-women-in-business-connect' => [
                ['early_bird', 'Early Bird', 'Limited discounted admission.', 80.00, 80],
                ['general', 'General', 'Access to talks and networking sessions.', 140.00, 200],
            ],
            'north-africa-game-dev-night' => [
                ['online', 'Online Pass', 'Full access to the livestream and Q&A.', 50.00, 500],
            ],
            'rabat-cinema-under-the-stars' => [
                ['student', 'Student', 'Discounted access for students.', 40.00, 120],
                ['general', 'General', 'Access to the evening screening.', 70.00, 280],
            ],
            'atlas-mountain-challenge' => [
                ['standard', 'Standard Entry', 'Entry for the standard challenge route.', 180.00, 200],
                ['adventure', 'Adventure Pack', 'Standard entry plus navigation and gear pack.', 280.00, 80],
            ],
            'tangier-mediterranean-food-market' => [
                ['entry', 'Entry Pass', 'Market entry access.', 50.00, 500],
                ['tasting', 'Tasting Pass', 'Market entry with tasting vouchers.', 160.00, 220],
            ],
            'morocco-product-design-lab' => [
                ['student', 'Student', 'Discounted access for students.', 40.00, 200],
                ['professional', 'Professional', 'Full access for working professionals.', 120.00, 300],
            ],
            'moroccan-home-cooking-online-workshop' => [
                ['individual', 'Individual', 'Individual access to the live workshop.', 80.00, 250],
                ['premium', 'Premium', 'Individual access plus recipe kit add-on.', 140.00, 120],
            ],
            // No types: essaouira-creative-photography-walk (draft), agadir-future-of-tourism-forum (cancelled)
        ];

        foreach ($ticketTypes as $slug => $types) {
            $event = Event::where('slug', $slug)->firstOrFail();

            foreach ($types as [$key, $name, $desc, $price, $qty]) {
                TicketType::create([
                    'event_id' => $event->id,
                    'name' => $name,
                    'description' => $desc,
                    'price' => $price,
                    'currency' => 'MAD',
                    'quantity' => $qty,
                    'min_per_booking' => 1,
                    'max_per_booking' => 4,
                    'sales_start_at' => now()->subDays(30),
                    'sales_end_at' => $event->starts_at->subHours(2),
                ]);
            }
        }
    }
}
