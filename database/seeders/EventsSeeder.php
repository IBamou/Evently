<?php

namespace Database\Seeders;

use App\Enums\EventFormat;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    /**
     * Seed all 33 demo events for the organizer.
     *
     * Categories are resolved by slug (created by CategoriesSeeder beforehand).
     * Events use either relative dates (days + duration_hours) or absolute
     * dates (starts_at / ends_at strings).
     */
    public function run(): void
    {
        $organizer = User::where('email', 'demo-organizer@evently.test')->firstOrFail();

        // Category index → slug mapping (matches CategoriesSeeder: Str::slug($name))
        $categorySlugs = [
            0 => 'music',
            1 => 'business',
            2 => 'tech',
            3 => 'art',
            4 => 'sports',
            5 => 'food-drinks',
        ];
        $categories = Category::all()->keyBy('slug');

        /**
         * 33 events total:
         *  - 13 first-batch (relative dates)
         *  - 10 ChatGPT batch #2 (absolute dates)
         *  - 10 ChatGPT batch #3 (absolute dates)
         */
        $events = [
            // ── Batch 1 · Published · upcoming (relative dates) ──
            [
                'title' => 'Casablanca Summer Music Festival',
                'slug' => 'casablanca-summer-music-festival',
                'category' => 0, 'city' => 'Casablanca', 'location' => 'Oasis Arena',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 14, 'duration_hours' => 8,
                'banner' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An electrifying three-day music festival featuring top Moroccan and international artists performing live under the stars in Casablanca.',
            ],
            [
                'title' => 'Rabat Tech Summit 2026',
                'slug' => 'rabat-tech-summit-2026',
                'category' => 2, 'city' => 'Rabat', 'location' => 'Mohammed VI Center',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 21, 'duration_hours' => 9,
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'The biggest technology conference in Morocco: keynotes, hands-on workshops, and networking with 2,000+ engineers, founders, and VCs.',
            ],
            [
                'title' => 'Marrakech Street Food Tour',
                'slug' => 'marrakech-street-food-tour',
                'category' => 5, 'city' => 'Marrakech', 'location' => 'Jemaa el-Fnaa',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 5, 'duration_hours' => 4,
                'banner' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Explore hidden gems of Moroccan cuisine on a guided walking food tour through the alleys of the old Medina.',
            ],
            [
                'title' => 'Agadir Surf Cup 2026',
                'slug' => 'agadir-surf-cup-2026',
                'category' => 4, 'city' => 'Agadir', 'location' => 'Taghazout Beach',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 9, 'duration_hours' => 10,
                'banner' => 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Nationally ranked surfers battle it out in the legendary Taghazout waves. Open to the public with beachside food stalls and music.',
            ],
            [
                'title' => 'Tangier Art Biennale',
                'slug' => 'tangier-art-biennale',
                'category' => 3, 'city' => 'Tangier', 'location' => 'Borj Art Gallery',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 45, 'duration_hours' => 6,
                'banner' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Discover contemporary works by 40+ Moroccan and international artists across painting, sculpture, and installation.',
            ],
            [
                'title' => 'Casablanca International Film Festival',
                'slug' => 'casablanca-film-festival',
                'category' => 3, 'city' => 'Casablanca', 'location' => 'Megarama Cinemas',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 32, 'duration_hours' => 5,
                'banner' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A week of screenings, red carpet premieres, and masterclasses with award-winning filmmakers.',
            ],
            [
                'title' => 'Remote Careers Summit',
                'slug' => 'remote-careers-summit',
                'category' => 1, 'city' => 'Online', 'location' => 'Online (Zoom)',
                'format' => EventFormat::Online, 'status' => EventStatus::Published,
                'days' => 11, 'duration_hours' => 5,
                'banner' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Learn how to land remote roles at top companies: portfolios, interviews, negotiations, and global payment setups.',
            ],
            [
                'title' => 'Chefchaouen Art Walk',
                'slug' => 'chefchaouen-art-walk',
                'category' => 3, 'city' => 'Chefchaouen', 'location' => 'Old Medina',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => 18, 'duration_hours' => 4,
                'banner' => 'https://images.unsplash.com/photo-1518998053901-5348d3961a04?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Wander the blue streets of Chefchaouen with local artists as they open their studios for an exclusive evening walk.',
            ],
            // ── Batch 1 · Published · past (for reports/check-in demos) ──
            [
                'title' => 'Rabat Business Networking Night',
                'slug' => 'rabat-business-networking-night',
                'category' => 1, 'city' => 'Rabat', 'location' => 'Hilton Rabat',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => -10, 'duration_hours' => 3,
                'banner' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Casual evening for founders, investors, and freelancers. Speed networking, pitches, and a rooftop after-party.',
            ],
            [
                'title' => 'Atlas Marathon 2026',
                'slug' => 'atlas-marathon-2026',
                'category' => 4, 'city' => 'Marrakech', 'location' => 'Ourika Valley',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'days' => -3, 'duration_hours' => 8,
                'banner' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Full marathon, half-marathon, and 10K trail runs through the Atlas mountains. 3,000 runners took part.',
            ],
            // ── Batch 1 · Under review ──
            [
                'title' => 'Essaouira Jazz Nights',
                'slug' => 'essaouira-jazz-nights',
                'category' => 0, 'city' => 'Essaouira', 'location' => 'Moulay Hassan Square',
                'format' => EventFormat::InPerson, 'status' => EventStatus::UnderReview,
                'days' => 40, 'duration_hours' => 6,
                'banner' => 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Live jazz under the stars the Moulay Hassan Square, from swing to free jazz with international quartets.',
            ],
            // ── Batch 1 · Draft ──
            [
                'title' => 'Fes Cooking Masterclass',
                'slug' => 'fes-cooking-masterclass',
                'category' => 5, 'city' => 'Fes', 'location' => 'Riad Dar Alika',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Draft,
                'days' => 60, 'duration_hours' => 5,
                'banner' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Learn to cook a complete Moroccan feast — tagine, pastilla, and mint tea — with a renowned Fassi chef. (Draft, not published yet.)',
            ],
            // ── Batch 1 · Cancelled ──
            [
                'title' => 'Morocco Esports Championship',
                'slug' => 'morocco-esports-championship',
                'category' => 2, 'city' => 'Casablanca', 'location' => 'Anfa Park Expo',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Cancelled,
                'days' => 80, 'duration_hours' => 12,
                'banner' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=70',
                'description' => 'National League of Legends and FC tournaments with top Moroccan teams. (This event was cancelled.)',
            ],
            // ── Batch 2 · ChatGPT-generated (10 events, absolute dates) ──
            [
                'title' => 'Casablanca Rooftop Jazz Sessions',
                'slug' => 'casablanca-rooftop-jazz-sessions',
                'category' => 0, 'city' => 'Casablanca', 'location' => 'Sky 28 Rooftop',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-09-12T20:00:00', 'ends_at' => '2026-09-13T00:00:00',
                'banner' => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An intimate evening of live jazz featuring Moroccan musicians and emerging North African artists. Enjoy panoramic city views, food, and a relaxed rooftop atmosphere.',
            ],
            [
                'title' => 'Rabat Founders & Innovation Forum',
                'slug' => 'rabat-founders-innovation-forum',
                'category' => 1, 'city' => 'Rabat', 'location' => 'Technopark Rabat',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-09-19T18:30:00', 'ends_at' => '2026-09-19T22:30:00',
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A networking and learning evening bringing together startup founders, investors, students, and innovation leaders. The program includes talks, startup showcases, and structured networking.',
            ],
            [
                'title' => 'Morocco AI Builders Live',
                'slug' => 'morocco-ai-builders-live',
                'category' => 2, 'city' => 'Casablanca', 'location' => 'Online',
                'format' => EventFormat::Online, 'status' => EventStatus::Published,
                'starts_at' => '2026-09-27T19:00:00', 'ends_at' => '2026-09-27T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An online evening for developers, product builders, and entrepreneurs exploring practical AI projects: product development, automation, and real-world Moroccan use cases.',
            ],
            [
                'title' => 'Marrakech Contemporary Art After Dark',
                'slug' => 'marrakech-contemporary-art-after-dark',
                'category' => 3, 'city' => 'Marrakech', 'location' => 'Comptoir des Mines Galerie',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-10-03T19:30:00', 'ends_at' => '2026-10-03T23:30:00',
                'banner' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Discover contemporary Moroccan art through evening exhibitions, live installations, and conversations with local artists.',
            ],
            [
                'title' => 'Agadir Atlantic Night Run',
                'slug' => 'agadir-atlantic-night-run',
                'category' => 4, 'city' => 'Agadir', 'location' => 'Agadir Corniche',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-10-10T18:30:00', 'ends_at' => '2026-10-10T22:30:00',
                'banner' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A lively evening running experience along the Atlantic waterfront with 5 km and 10 km routes, race timing, hydration support, and a finish-line celebration.',
            ],
            [
                'title' => 'Fes Flavours Culinary Evening',
                'slug' => 'fes-flavours-culinary-evening',
                'category' => 5, 'city' => 'Fes', 'location' => 'Palais Amani',
                'format' => EventFormat::InPerson, 'status' => EventStatus::UnderReview,
                'starts_at' => '2026-10-17T19:00:00', 'ends_at' => '2026-10-17T23:00:00',
                'banner' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A celebration of traditional Fassi cuisine featuring tasting stations, live cooking demonstrations, and local chefs.',
            ],
            [
                'title' => 'Tangier Digital Creators Summit',
                'slug' => 'tangier-digital-creators-summit',
                'category' => 2, 'city' => 'Tangier', 'location' => 'Online',
                'format' => EventFormat::Online, 'status' => EventStatus::Published,
                'starts_at' => '2026-10-24T18:00:00', 'ends_at' => '2026-10-24T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A digital-first conference for designers, content creators, developers, and creative entrepreneurs.',
            ],
            [
                'title' => 'Essaouira Sunset Surf Gathering',
                'slug' => 'essaouira-sunset-surf-gathering',
                'category' => 4, 'city' => 'Essaouira', 'location' => 'Essaouira Main Beach',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Cancelled,
                'starts_at' => '2026-11-01T18:00:00', 'ends_at' => '2026-11-01T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An afternoon-to-evening surf gathering with guided sessions, beach activities, and sunset music. (This event was cancelled.)',
            ],
            [
                'title' => 'Dakhla Remote Business Masterclass',
                'slug' => 'dakhla-remote-business-masterclass',
                'category' => 1, 'city' => 'Dakhla', 'location' => 'Online',
                'format' => EventFormat::Online, 'status' => EventStatus::Draft,
                'starts_at' => '2026-11-14T19:00:00', 'ends_at' => '2026-11-14T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An online workshop for freelancers and entrepreneurs building location-independent businesses from Morocco. (Draft, not published yet.)',
            ],
            [
                'title' => 'El Jadida Moroccan Street Food Festival',
                'slug' => 'el-jadida-moroccan-street-food-festival',
                'category' => 5, 'city' => 'El Jadida', 'location' => 'Parc Mohammed V',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-11-21T18:30:00', 'ends_at' => '2026-11-21T23:30:00',
                'banner' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A festive evening celebrating Moroccan street food with local vendors, live cooking, music, and regional specialties.',
            ],
            // ── Batch 3 · ChatGPT-generated (10 events, absolute dates) ──
            [
                'title' => 'Chefchaouen Indie Music Evening',
                'slug' => 'chefchaouen-indie-music-evening',
                'category' => 0, 'city' => 'Chefchaouen', 'location' => 'Dar Echchaouen',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-09-05T20:00:00', 'ends_at' => '2026-09-06T00:00:00',
                'banner' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A relaxed live music night featuring independent Moroccan artists, acoustic sets, and emerging regional talent.',
            ],
            [
                'title' => 'Casablanca Women in Business Connect',
                'slug' => 'casablanca-women-in-business-connect',
                'category' => 1, 'city' => 'Casablanca', 'location' => 'Hyatt Regency',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-09-16T18:30:00', 'ends_at' => '2026-09-16T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A professional networking evening connecting women entrepreneurs, executives, and aspiring founders. Short talks, mentoring conversations, curated networking.',
            ],
            [
                'title' => 'North Africa Game Dev Night',
                'slug' => 'north-africa-game-dev-night',
                'category' => 2, 'city' => 'Rabat', 'location' => 'Online',
                'format' => EventFormat::Online, 'status' => EventStatus::Published,
                'starts_at' => '2026-09-24T19:00:00', 'ends_at' => '2026-09-24T22:30:00',
                'banner' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An online gathering for game developers, designers, artists, and students about building game communities in North Africa.',
            ],
            [
                'title' => 'Rabat Cinema Under the Stars',
                'slug' => 'rabat-cinema-under-the-stars',
                'category' => 3, 'city' => 'Rabat', 'location' => "Jardin d'Essais",
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-10-02T20:00:00', 'ends_at' => '2026-10-03T00:00:00',
                'banner' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An open-air cinema evening with Moroccan short films and independent cinema, followed by discussions with filmmakers.',
            ],
            [
                'title' => 'Atlas Mountain Challenge',
                'slug' => 'atlas-mountain-challenge',
                'category' => 4, 'city' => 'Marrakech', 'location' => 'Oukaimeden',
                'format' => EventFormat::InPerson, 'status' => EventStatus::UnderReview,
                'starts_at' => '2026-10-11T18:00:00', 'ends_at' => '2026-10-11T23:00:00',
                'banner' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A challenging outdoor sports event bringing runners and adventure enthusiasts to marked mountain routes with hydration points.',
            ],
            [
                'title' => 'Tangier Mediterranean Food Market',
                'slug' => 'tangier-mediterranean-food-market',
                'category' => 5, 'city' => 'Tangier', 'location' => 'Marina Bay',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Published,
                'starts_at' => '2026-10-18T18:30:00', 'ends_at' => '2026-10-18T23:30:00',
                'banner' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=70',
                'description' => 'Moroccan and Mediterranean flavours through local chefs, street-food vendors, and artisan producers.',
            ],
            [
                'title' => 'Morocco Product Design Lab',
                'slug' => 'morocco-product-design-lab',
                'category' => 2, 'city' => 'Casablanca', 'location' => 'Online',
                'format' => EventFormat::Online, 'status' => EventStatus::Published,
                'starts_at' => '2026-10-29T19:00:00', 'ends_at' => '2026-10-29T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'An interactive online event for product designers and UX professionals: research, interface design, and design systems.',
            ],
            [
                'title' => 'Essaouira Creative Photography Walk',
                'slug' => 'essaouira-creative-photography-walk',
                'category' => 3, 'city' => 'Essaouira', 'location' => 'Place Moulay Hassan',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Draft,
                'starts_at' => '2026-11-07T18:00:00', 'ends_at' => '2026-11-07T21:30:00',
                'banner' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A guided photography experience through the medina: composition, street photography, and visual storytelling.',
            ],
            [
                'title' => 'Agadir Future of Tourism Forum',
                'slug' => 'agadir-future-of-tourism-forum',
                'category' => 1, 'city' => 'Agadir', 'location' => 'Sofitel',
                'format' => EventFormat::InPerson, 'status' => EventStatus::Cancelled,
                'starts_at' => '2026-11-15T18:30:00', 'ends_at' => '2026-11-15T22:30:00',
                'banner' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A business forum on tourism innovation, sustainable travel, and hospitality technology.',
            ],
            [
                'title' => 'Moroccan Home Cooking Online Workshop',
                'slug' => 'moroccan-home-cooking-online-workshop',
                'category' => 5, 'city' => 'Marrakech', 'location' => 'Online',
                'format' => EventFormat::Online, 'status' => EventStatus::Published,
                'starts_at' => '2026-11-26T19:00:00', 'ends_at' => '2026-11-26T22:00:00',
                'banner' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=70',
                'description' => 'A live online cooking workshop teaching a complete Moroccan dinner: ingredients, techniques, and presentation.',
            ],
        ];

        $created = [];

        foreach ($events as $data) {
            $category = $categories[$categorySlugs[$data['category']]];

            // Absolute dates (from ChatGPT payload) OR relative demo dates
            if (isset($data['starts_at'])) {
                $startsAt = Carbon::parse($data['starts_at']);
                $endsAt = Carbon::parse($data['ends_at']);
            } else {
                $startsAt = now()->addDays($data['days'])->startOfDay()->addHours(18);
                $endsAt = $startsAt->copy()->addHours($data['duration_hours']);
            }

            $event = Event::create([
                'organizer_id' => $organizer->id,
                'category_id' => $category->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'location' => $data['location'],
                'city' => $data['city'],
                'format' => $data['format'],
                'status' => $data['status'],
                'banner_url' => $data['banner'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $created[$data['slug']] = $event;
        }
    }
}
