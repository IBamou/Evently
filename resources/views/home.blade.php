{{-- Home page — pixel-port of design-evently-home.html (hero + featured + all events + newsletter).
     Rendered inside <x-app-layout> (sidebar/header/footer live in layouts/app.blade.php — mimo).
     Role-aware shell: ?role=user shows the USER header (Events·My bookings·My tickets·Profile, YB avatar)
     exactly like the design's role tabs — content is identical for guest & user (design L1588). --}}
@php
    $role = request('role', 'guest');
    $role = in_array($role, ['guest', 'user', 'organizer', 'admin'], true) ? $role : 'guest';
@endphp

<x-app-layout :activeRole="$role" :navRole="$role" :avatarRole="$role">
@php
    $heroStats = [
        ['value' => '124', 'label' => 'Upcoming events'],
        ['value' => '38K', 'label' => 'Tickets sold'],
        ['value' => '4.8★', 'label' => 'Avg. rating'],
    ];

    $heroChips = [
        ['label' => 'Today', 'active' => false],
        ['label' => 'This weekend', 'active' => false],
        ['label' => 'Free', 'active' => false],
        ['label' => 'Online', 'active' => false],
        ['label' => 'Evening', 'active' => false],
        ['label' => 'Near me', 'active' => false],
    ];

    $events = [
        [
            'title' => 'Mawazine Nights', 'cat' => 'Music Festival', 'date' => 'Fri 24 Jul',
            'venue' => 'OLM Souissi', 'city' => 'Rabat', 'price' => '250 MAD', 'spots' => 18, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#1565D8,#0EA5E9)', 'flag' => 'Hot', 'flagBg' => '#DC2626',
        ],
        [
            'title' => 'AI & Future Summit 2026', 'cat' => 'Conference', 'date' => 'Sat 25 Jul',
            'venue' => 'Casablanca Technopark', 'city' => 'Casablanca', 'price' => '600 MAD', 'spots' => 7, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#0B4BAA,#1565D8)', 'flag' => 'New', 'flagBg' => '#1565D8',
        ],
        [
            'title' => 'Atlas Desert Jazz', 'cat' => 'Music', 'date' => 'Sun 26 Jul',
            'venue' => 'Palais Bahia Gardens', 'city' => 'Marrakech', 'price' => '180 MAD', 'spots' => 3, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#14B8A6,#0EA5E9)', 'flag' => 'Almost full', 'flagBg' => '#D97706',
        ],
        [
            'title' => 'Creative Makers Expo', 'cat' => 'Exhibition', 'date' => 'Fri 31 Jul',
            'venue' => 'Tangier Expo Center', 'city' => 'Tangier', 'price' => 'Free', 'spots' => 42, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#7C3AED,#0EA5E9)', 'flag' => 'Last seats', 'flagBg' => '#16A34A',
        ],
        [
            'title' => 'Tech Startup Pitch Night', 'cat' => 'Networking', 'date' => 'Sat 1 Aug',
            'venue' => 'Casablanca Twin Center', 'city' => 'Casablanca', 'price' => '120 MAD', 'spots' => 25, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#D97706,#F59E0B)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'Yoga & Wellness Retreat', 'cat' => 'Workshop', 'date' => 'Sun 2 Aug',
            'venue' => 'Agadir Bay Resort', 'city' => 'Agadir', 'price' => '350 MAD', 'spots' => 15, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#0EA5E9,#14B8A6)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'Casablanca Food Festival', 'cat' => 'Festival', 'date' => 'Fri 7 Aug',
            'venue' => 'Anfa Park', 'city' => 'Casablanca', 'price' => '90 MAD', 'spots' => 60, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#DC2626,#F59E0B)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'Indie Film Nights', 'cat' => 'Cinema', 'date' => 'Sat 8 Aug',
            'venue' => 'Rabat Cinémathèque', 'city' => 'Rabat', 'price' => '60 MAD', 'spots' => 31, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#1565D8,#7C3AED)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'Coding Bootcamp Weekend', 'cat' => 'Workshop', 'date' => 'Sun 9 Aug',
            'venue' => '1337 Khouribga', 'city' => 'Casablanca', 'price' => 'Free', 'spots' => 120, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#14B8A6,#1565D8)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'Moroccan Design Week', 'cat' => 'Exhibition', 'date' => 'Fri 14 Aug',
            'venue' => 'Marrakech Convention Center', 'city' => 'Marrakech', 'price' => '200 MAD', 'spots' => 28, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#F59E0B,#DC2626)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'Beach Volleyball Cup', 'cat' => 'Sports', 'date' => 'Sat 15 Aug',
            'venue' => 'Agadir Beach', 'city' => 'Agadir', 'price' => '75 MAD', 'spots' => 8, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#0B4BAA,#0EA5E9)', 'flag' => '', 'flagBg' => '',
        ],
        [
            'title' => 'SaaS Founders Meetup', 'cat' => 'Networking', 'date' => 'Sun 16 Aug',
            'venue' => 'Tangier Cowork', 'city' => 'Tangier', 'price' => 'Free', 'spots' => 19, 'fav' => false,
            'grad' => 'linear-gradient(135deg,#7C3AED,#1565D8)', 'flag' => '', 'flagBg' => '',
        ],
    ];

    $featuredEvents = array_slice($events, 0, 4);
    $visibleEvents = array_slice($events, 0, 6);

    $filterGroups = [
        [
            'label' => 'Categories',
            'options' => [
                ['label' => 'All categories', 'count' => 12, 'checked' => true],
                ['label' => 'Music', 'count' => 3, 'checked' => false],
                ['label' => 'Business', 'count' => 1, 'checked' => false],
                ['label' => 'Tech', 'count' => 2, 'checked' => false],
                ['label' => 'Art', 'count' => 3, 'checked' => false],
                ['label' => 'Sports', 'count' => 1, 'checked' => false],
                ['label' => 'Food & Drinks', 'count' => 1, 'checked' => false],
            ],
        ],
        [
            'label' => 'Format',
            'options' => [
                ['label' => 'Any', 'count' => '', 'checked' => true],
                ['label' => 'In person', 'count' => '', 'checked' => false],
                ['label' => 'Online', 'count' => '', 'checked' => false],
                ['label' => 'Hybrid', 'count' => '', 'checked' => false],
            ],
        ],
        [
            'label' => 'Time of day',
            'options' => [
                ['label' => 'Any time', 'count' => '', 'checked' => true],
                ['label' => 'Morning', 'count' => '', 'checked' => false],
                ['label' => 'Afternoon', 'count' => '', 'checked' => false],
                ['label' => 'Evening', 'count' => '', 'checked' => false],
            ],
        ],
    ];
@endphp

    {{-- ===================== HERO ===================== --}}
    <section aria-label="Hero" style="position:relative;overflow:hidden;background:linear-gradient(180deg,var(--hero1) 0%,var(--hero2) 58%,var(--hero3) 100%)">
        <div style="position:absolute;top:-90px;right:14%;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(255,214,140,.75),rgba(255,214,140,0) 70%);animation:glow 7s ease-in-out infinite"></div>
        <div style="position:absolute;top:38px;left:0;width:170px;height:44px;border-radius:40px;background:rgba(255,255,255,.6);filter:blur(1px);animation:drift 34s linear infinite"></div>
        <div style="position:absolute;top:96px;left:0;width:110px;height:30px;border-radius:40px;background:rgba(255,255,255,.45);filter:blur(1px);animation:drift 46s linear infinite;animation-delay:-12s"></div>
        <div style="position:absolute;right:9%;bottom:96px;animation:bob 6s ease-in-out infinite;transform-origin:bottom center">
            <svg width="132" height="150" viewBox="0 0 132 150" fill="none" aria-hidden="true">
                <path d="M64 8 118 108H68z" fill="#FFFFFF" opacity=".96"></path>
                <path d="M58 20 12 108h46z" fill="#DCEEFB"></path>
                <path d="M62 6h4v104h-4z" fill="#7C9CBF"></path>
                <path d="M8 112h116l-14 20H22z" fill="#123B66"></path>
            </svg>
        </div>

        <div style="position:relative;max-width:1380px;margin:0 auto;padding:60px 26px 130px">
            <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);gap:48px;align-items:start">
                <div style="animation:up .7s ease both">
                    <div style="display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border-radius:99px;background:rgba(255,255,255,.75);border:1px solid rgba(21,101,216,.16);font-size:12px;font-weight:700;color:var(--primary-dark);margin-bottom:20px">
                        <span style="width:7px;height:7px;border-radius:50%;background:var(--ok);animation:ping 2.4s ease-out infinite"></span>
                        12 events live in Casablanca, Rabat &amp; beyond
                    </div>
                    <h1 style="margin:0 0 16px;font-size:52px;line-height:1.04;letter-spacing:-1.8px;font-weight:800;text-wrap:balance">Find events that <span style="background:linear-gradient(100deg,var(--primary),var(--cyan));-webkit-background-clip:text;background-clip:text;color:transparent">inspire you</span></h1>
                    <p style="margin:0 0 26px;font-size:16.5px;line-height:1.6;color:var(--muted);max-width:44ch;text-wrap:pretty">Concerts, festivals, conferences and workshops across Morocco. Book in seconds, get a QR ticket instantly.</p>
                    <div style="display:flex;gap:26px;flex-wrap:wrap">
                        @foreach ($heroStats as $stat)
                            <div>
                                <div style="font-size:26px;font-weight:800;letter-spacing:-.8px">{{ $stat['value'] }}</div>
                                <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.7px">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="animation:up .7s .1s ease both">
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);padding:14px;display:flex;gap:10px;align-items:center">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" style="margin-left:6px;flex:0 0 auto"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                        <input type="text" class="needs-focus" placeholder="Search events, categories or venues…" aria-label="Search events" style="flex:1;min-width:0;border:0;background:none;font-size:15px;padding:8px 0;outline:none">
                        <button type="button" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:14px;padding:12px 22px;border-radius:12px;min-height:44px">Search</button>
                    </div>
                    <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:14px">
                        @foreach ($heroChips as $chip)
                            <button type="button" class="needs-focus" style="display:inline-flex;align-items:center;gap:7px;min-height:40px;padding:9px 15px;border-radius:11px;cursor:pointer;font-size:13px;font-weight:700;border:1px solid {{ $chip['active'] ? 'var(--primary)' : 'var(--border)' }};background:{{ $chip['active'] ? 'var(--primary)' : 'var(--surface2)' }};color:{{ $chip['active'] ? '#fff' : 'var(--text)' }};box-shadow:var(--shadow)">{{ $chip['label'] }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div style="position:absolute;left:0;right:0;bottom:0;height:150px;pointer-events:none">
            <div style="position:absolute;bottom:0;left:0;width:200%;height:110px;animation:wave 22s linear infinite;opacity:.5">
                <svg width="100%" height="100%" viewBox="0 0 2400 110" preserveAspectRatio="none"><path d="M0 46c200 34 400-34 600 0s400 34 600 0 400-34 600 0 400 34 600 0v64H0z" fill="#9BD3F2"></path></svg>
            </div>
            <div style="position:absolute;bottom:0;left:0;width:200%;height:86px;animation:wave 15s linear infinite;opacity:.72">
                <svg width="100%" height="100%" viewBox="0 0 2400 86" preserveAspectRatio="none"><path d="M0 40c240 30 360-30 600 0s360 30 600 0 360-30 600 0 360 30 600 0v46H0z" fill="#CBE8FA"></path></svg>
            </div>
            <div style="position:absolute;bottom:0;left:0;width:200%;height:58px;animation:wave 9s linear infinite">
                <svg width="100%" height="100%" viewBox="0 0 2400 58" preserveAspectRatio="none"><path d="M0 26c200 22 400-22 600 0s400 22 600 0 400-22 600 0 400 22 600 0v32H0z" fill="var(--bg)"></path></svg>
            </div>
        </div>
    </section>

    {{-- ===================== FEATURED EVENTS ===================== --}}
    <section aria-label="Featured events" style="max-width:1380px;margin:-34px auto 0;padding:0 26px;position:relative;z-index:2">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow);padding:20px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.9" stroke-linejoin="round"><path d="m12 3 2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.8 6.1 20.9l1.2-6.5L2.5 9.8l6.6-.9z"></path></svg>
                <h2 style="margin:0;font-size:17px;font-weight:800;letter-spacing:-.3px">Featured events</h2>
                <div style="flex:1"></div>
                <button type="button" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--primary)">View all</button>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
                @foreach ($featuredEvents as $event)
                    <article class="ev-card" onclick="location.href='{{ url('/preview/events/atlantis-live' . ($role !== 'guest' ? '?role=' . $role : '')) }}'" style="border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface);cursor:pointer">
                        <div style="position:relative;height:150px;background:{{ $event['grad'] }}">
                            <span style="position:absolute;top:11px;left:11px;padding:5px 10px;border-radius:8px;background:{{ $event['flagBg'] }};color:#fff;font-size:10.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase">{{ $event['flag'] }}</span>
                            <span style="position:absolute;bottom:11px;left:11px;padding:5px 10px;border-radius:8px;background:rgba(255,255,255,.92);color:#0B2545;font-size:10.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase">{{ $event['cat'] }}</span>
                            <button type="button" onclick="event.stopPropagation()" aria-label="Save event" aria-pressed="{{ $event['fav'] ? 'true' : 'false' }}" style="position:absolute;top:9px;right:9px;width:34px;height:34px;border-radius:50%;border:0;cursor:pointer;background:rgba(255,255,255,.9);display:grid;place-items:center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $event['fav'] ? '#DC2626' : 'none' }}" stroke="#0B2545" stroke-width="1.8" stroke-linejoin="round"><path d="M12 20s-7-4.4-7-9.4A4.1 4.1 0 0 1 12 8a4.1 4.1 0 0 1 7 2.6c0 5-7 9.4-7 9.4z"></path></svg>
                            </button>
                        </div>
                        <div style="padding:14px">
                            <h3 style="margin:0 0 9px;font-size:15px;font-weight:700;letter-spacing:-.2px;line-height:1.3">{{ $event['title'] }}</h3>
                            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);font-weight:600;margin-bottom:10px;flex-wrap:wrap">
                                <span>{{ $event['date'] }}</span><span style="opacity:.5">·</span><span>{{ $event['city'] }}</span>
                            </div>
                            <div style="font-size:13px;font-weight:800;color:var(--primary)">{{ $event['price'] }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== ALL EVENTS ===================== --}}
    <section aria-label="All events" style="max-width:1380px;margin:0 auto;padding:32px 26px 60px;display:grid;grid-template-columns:262px minmax(0,1fr);gap:26px;align-items:start">
        <aside style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:18px;position:sticky;top:82px">
            <div style="display:flex;align-items:center;gap:9px;margin-bottom:16px">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M4 6h16M7 12h10M10 18h4"></path></svg>
                <span style="font-weight:800;font-size:14px">Filters</span>
                <div style="flex:1"></div>
                <button type="button" style="border:0;background:none;cursor:pointer;font-size:12px;font-weight:700;color:var(--primary)">Clear all</button>
            </div>

            @foreach ($filterGroups as $group)
                <div style="border-top:1px solid var(--border);padding:14px 0">
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:11px">{{ $group['label'] }}</div>
                    <div style="display:flex;flex-direction:column;gap:3px">
                        @foreach ($group['options'] as $option)
                            <button type="button" class="needs-focus" role="checkbox" aria-checked="{{ $option['checked'] ? 'true' : 'false' }}" style="display:flex;align-items:center;gap:10px;padding:8px 8px;min-height:38px;border:0;border-radius:9px;cursor:pointer;background:{{ $option['checked'] ? 'var(--chip)' : 'transparent' }};text-align:left;font-size:13px;font-weight:{{ $option['checked'] ? '800' : '600' }};color:{{ $option['checked'] ? 'var(--primary)' : 'var(--text)' }}">
                                <span style="width:16px;height:16px;flex:0 0 auto;border-radius:5px;border:1.6px solid {{ $option['checked'] ? 'var(--primary)' : 'var(--border)' }};background:{{ $option['checked'] ? 'var(--primary)' : 'transparent' }};display:grid;place-items:center">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:{{ $option['checked'] ? 1 : 0 }}"><path d="M20 6 9 17l-5-5"></path></svg>
                                </span>
                                <span style="flex:1">{{ $option['label'] }}</span>
                                <span style="font-size:11px;color:var(--muted);font-weight:600">{{ $option['count'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div style="border-top:1px solid var(--border);padding-top:14px">
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:12px">Max price</div>
                <input type="range" class="needs-focus" min="0" max="1000" step="50" value="600" aria-label="Maximum price" style="width:100%;accent-color:var(--primary)">
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:var(--muted);margin-top:6px"><span>0 MAD</span><span>600 MAD</span></div>
            </div>
        </aside>

        <div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap">
                <h2 style="margin:0;font-size:22px;font-weight:800;letter-spacing:-.6px">{{ count($events) }} events found</h2>
                <div style="flex:1"></div>
                <span style="font-size:13px;color:var(--muted);font-weight:600">Sort by</span>
                <select aria-label="Sort events" class="needs-focus" style="min-height:40px;padding:9px 12px;border:1px solid var(--border);background:var(--surface);border-radius:11px;font-size:13px;font-weight:600">
                    <option value="Recommended" selected>Recommended</option>
                    <option value="Date (soonest)">Date (soonest)</option>
                    <option value="Date (latest)">Date (latest)</option>
                    <option value="Price (low → high)">Price (low → high)</option>
                    <option value="Price (high → low)">Price (high → low)</option>
                </select>
                <div style="display:flex;gap:4px;padding:4px;border:1px solid var(--border);background:var(--surface);border-radius:11px">
                    <button type="button" aria-label="Grid view" class="needs-focus" style="width:36px;height:36px;display:grid;place-items:center;border:0;border-radius:8px;cursor:pointer;background:var(--primary);color:#fff">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"></path></svg>
                    </button>
                    <button type="button" aria-label="List view" class="needs-focus" style="width:36px;height:36px;display:grid;place-items:center;border:0;border-radius:8px;cursor:pointer;background:transparent;color:var(--muted)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                    </button>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px">
                @foreach ($visibleEvents as $event)
                    @php $detailUrl = url('/preview/events/atlantis-live' . ($role !== 'guest' ? '?role=' . $role : '')); @endphp
                    <article class="ev-card" onclick="location.href='{{ $detailUrl }}'" style="border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface);cursor:pointer">
                        <div style="position:relative;height:160px;background:{{ $event['grad'] }}">
                            <span style="position:absolute;top:11px;left:11px;padding:5px 10px;border-radius:8px;background:rgba(255,255,255,.93);color:#0B2545;font-size:10.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase">{{ $event['cat'] }}</span>
                            <button type="button" onclick="event.stopPropagation()" aria-label="Save event" aria-pressed="{{ $event['fav'] ? 'true' : 'false' }}" style="position:absolute;top:9px;right:9px;width:34px;height:34px;border-radius:50%;border:0;cursor:pointer;background:rgba(255,255,255,.9);display:grid;place-items:center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $event['fav'] ? '#DC2626' : 'none' }}" stroke="#0B2545" stroke-width="1.8" stroke-linejoin="round"><path d="M12 20s-7-4.4-7-9.4A4.1 4.1 0 0 1 12 8a4.1 4.1 0 0 1 7 2.6c0 5-7 9.4-7 9.4z"></path></svg>
                            </button>
                        </div>
                        <div style="padding:16px;flex:1;display:flex;flex-direction:column;gap:10px">
                            <h3 style="margin:0;font-size:16px;font-weight:700;letter-spacing:-.2px">{{ $event['title'] }}</h3>
                            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);font-weight:600;flex-wrap:wrap">
                                <span>{{ $event['date'] }}</span><span style="opacity:.5">·</span><span>{{ $event['venue'] }}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;margin-top:auto">
                                <span style="font-size:13.5px;font-weight:800;color:var(--primary)">{{ $event['price'] }}</span>
                                <div style="flex:1"></div>
                                <span style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">View details</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:32px">
                <button type="button" aria-current="page" style="min-width:40px;min-height:40px;padding:0 12px;border:1px solid var(--primary);background:var(--primary);color:#fff;border-radius:10px;cursor:pointer;font-size:13px;font-weight:700">1</button>
                <button type="button" style="min-width:40px;min-height:40px;padding:0 12px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:10px;cursor:pointer;font-size:13px;font-weight:700">2</button>
                <button type="button" style="min-width:40px;min-height:40px;padding:0 12px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:10px;cursor:pointer;font-size:13px;font-weight:700">3</button>
            </div>
        </div>
    </section>

    {{-- ===================== NEWSLETTER ===================== --}}
    <section aria-label="Newsletter" style="max-width:1380px;margin:0 auto;padding:0 26px 60px">
        <div style="position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(120deg,var(--primary-dark),var(--primary) 55%,var(--cyan));padding:38px;display:flex;align-items:center;gap:26px;flex-wrap:wrap">
            {{-- Wave decoration --}}
            <div style="position:absolute;inset:auto 0 0 0;height:70px;opacity:.28;animation:wave 13s linear infinite;width:200%">
                <svg width="100%" height="100%" viewBox="0 0 2400 70" preserveAspectRatio="none"><path d="M0 30c240 26 360-26 600 0s360 26 600 0 360-26 600 0 360 26 600 0v40H0z" fill="#fff"></path></svg>
            </div>

            {{-- Text --}}
            <div style="position:relative;flex:1;min-width:260px">
                <h2 style="margin:0 0 6px;color:#fff;font-size:24px;font-weight:800;letter-spacing:-.6px">Stay in the loop</h2>
                <p style="margin:0;color:rgba(255,255,255,.82);font-size:14px">New events in Casablanca, Rabat and Marrakech &mdash; straight to your inbox.</p>
            </div>

            {{-- Email input + button --}}
            <div style="position:relative;display:flex;gap:10px;background:rgba(255,255,255,.14);padding:8px;border-radius:14px;border:1px solid rgba(255,255,255,.28)">
                <input placeholder="Enter your email" aria-label="Email" class="needs-focus"
                       style="border:0;background:none;padding:11px 14px;font-size:14px;color:#fff;outline:none;min-width:220px">
                <button type="button"
                        style="border:0;cursor:pointer;background:#fff;color:var(--primary-dark);font-weight:800;font-size:14px;padding:12px 22px;border-radius:10px;min-height:44px">Subscribe</button>
            </div>
        </div>
    </section>
</x-app-layout>
