{{-- Home page — pixel-port of design-evently-home.html (hero + featured + all events + newsletter).
     Rendered inside <x-app-layout> (sidebar/header/footer live in layouts/app.blade.php — mimo). --}}
@use('App\Helpers\Helper')

<x-app-layout :activeNav="'events'">
@php
    $activeCategory = $filters['category'] ?? '';
    $activeFormat = $filters['format'] ?? '';
    $activeTime = $filters['time'] ?? '';

    $mkFilterUrl = function (array $overrides) use ($filters): string {
        $query = $filters;
        unset($query['per_page']);

        return route('events.index', array_filter(array_merge($query, $overrides), fn ($v) => $v !== null && $v !== ''));
    };

    // Hero quick-filter chips (design). Wired to the real controller params
    // (format / time / starts_from / starts_to / max_price).
    $today = now()->format('Y-m-d');
    $weekendStart = now()->next(Carbon\Carbon::SATURDAY)->format('Y-m-d');
    $weekendEnd = now()->next(Carbon\Carbon::SUNDAY)->format('Y-m-d');

    $heroChips = [
        ['label' => 'Today', 'active' => ($filters['starts_from'] ?? '') === $today, 'url' => $mkFilterUrl(['starts_from' => $today, 'starts_to' => $today])],
        ['label' => 'This weekend', 'active' => ($filters['starts_from'] ?? '') === $weekendStart, 'url' => $mkFilterUrl(['starts_from' => $weekendStart, 'starts_to' => $weekendEnd])],
        ['label' => 'Free', 'active' => ($filters['max_price'] ?? '') === '0', 'url' => $mkFilterUrl(['max_price' => '0'])],
        ['label' => 'Online', 'active' => $activeFormat === 'online', 'url' => $mkFilterUrl(['format' => 'online'])],
        ['label' => 'Evening', 'active' => $activeTime === 'evening', 'url' => $mkFilterUrl(['time' => 'evening'])],
    ];

    // Sidebar filter groups, wired to the controller's real params
    // (category slug / format / time of day). Options link back to the
    // listing, keeping the current search/city/sort query intact.
    $categoryOptions = [['label' => 'All categories', 'count' => $events->total(), 'checked' => $activeCategory === '', 'url' => $mkFilterUrl(['category' => ''])]];
    foreach ($categories as $category) {
        $categoryOptions[] = [
            'label' => $category->name,
            'count' => $category->published_count ?? 0,
            'checked' => $activeCategory === $category->slug,
            'url' => $mkFilterUrl(['category' => $category->slug]),
        ];
    }

    $filterGroups = [
        [
            'label' => 'Categories',
            'options' => $categoryOptions,
        ],
        [
            'label' => 'Format',
            'options' => [
                ['label' => 'Any', 'count' => '', 'checked' => $activeFormat === '', 'url' => $mkFilterUrl(['format' => ''])],
                ['label' => 'In person', 'count' => '', 'checked' => $activeFormat === 'in_person', 'url' => $mkFilterUrl(['format' => 'in_person'])],
                ['label' => 'Online', 'count' => '', 'checked' => $activeFormat === 'online', 'url' => $mkFilterUrl(['format' => 'online'])],
            ],
        ],
        [
            'label' => 'Time of day',
            'options' => [
                ['label' => 'Any time', 'count' => '', 'checked' => $activeTime === '', 'url' => $mkFilterUrl(['time' => ''])],
                ['label' => 'Morning', 'count' => '', 'checked' => $activeTime === 'morning', 'url' => $mkFilterUrl(['time' => 'morning'])],
                ['label' => 'Afternoon', 'count' => '', 'checked' => $activeTime === 'afternoon', 'url' => $mkFilterUrl(['time' => 'afternoon'])],
                ['label' => 'Evening', 'count' => '', 'checked' => $activeTime === 'evening', 'url' => $mkFilterUrl(['time' => 'evening'])],
            ],
        ],
    ];

    // Cover background: real banner image when uploaded, otherwise the category
    // gradient from the shared helper (App\Helpers\Helper — was duplicated here).
    $coverBg = function ($event) {
        if ($event->banner_url) {
            return "url('" . e($event->banner_url) . "') center/cover";
        }
        return Helper::categoryGradient($event->category?->slug) ?? 'linear-gradient(135deg,var(--primary),var(--cyan))';
    };

    // Date line "Fri 24 Jul" — design format.
    $cardDate = fn ($event) => $event->starts_at?->format('D j M') ?: '';

    $detailUrl = fn ($event) => route('events.show', $event->slug);

    // Design sort labels → real controller sort keys. There is no price column
    // yet, so the price options map to title ordering — labeled as Title sorts.
    // Initial load (no ?sort=) shows "Recommended"; once the user picks a sort it
    // stays reflected after the GET submit.
    $sortSelected = request()->has('sort') ? ($filters['sort'] ?? '') : 'recommended';
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
                        {{ $events->total() }} events live in Casablanca, Rabat &amp; beyond
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
                    <form method="GET" action="{{ route('events.index') }}" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);padding:14px;display:flex;gap:10px;align-items:center;margin:0">

                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" style="margin-left:6px;flex:0 0 auto"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="needs-focus" placeholder="Search events, categories or venues…" aria-label="Search events" style="flex:1;min-width:0;border:0;background:none;font-size:15px;padding:8px 0;outline:none">
                        <button type="submit" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:14px;padding:12px 22px;border-radius:12px;min-height:44px">Search</button>
                    </form>
                    <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:14px">
                        @foreach ($heroChips as $chip)
                            <a href="{{ $chip['url'] }}" class="needs-focus" style="display:inline-flex;align-items:center;gap:7px;min-height:40px;padding:9px 15px;border-radius:11px;cursor:pointer;font-size:13px;font-weight:700;text-decoration:none;border:1px solid {{ $chip['active'] ? 'var(--primary)' : 'var(--border)' }};background:{{ $chip['active'] ? 'var(--primary)' : 'var(--surface2)' }};color:{{ $chip['active'] ? '#fff' : 'var(--text)' }};box-shadow:var(--shadow)">{{ $chip['label'] }}</a>
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
                <a href="{{ route('events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--primary);text-decoration:none">View all</a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
                @foreach ($featured as $event)
                    <article class="ev-card" onclick="location.href='{{ $detailUrl($event) }}'" style="border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface);cursor:pointer">
                        <div style="position:relative;height:150px;background:{{ $coverBg($event) }}">
                            <span style="position:absolute;bottom:11px;left:11px;padding:5px 10px;border-radius:8px;background:rgba(255,255,255,.92);color:#0B2545;font-size:10.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase">{{ $event->category?->name ?? 'Event' }}</span>
                        </div>
                        <div style="padding:14px">
                            <h3 style="margin:0 0 9px;font-size:15px;font-weight:700;letter-spacing:-.2px;line-height:1.3">{{ $event->title }}</h3>
                            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);font-weight:600;margin-bottom:10px;flex-wrap:wrap">
                                <span>{{ $cardDate($event) }}</span><span style="opacity:.5">·</span><span>{{ $event->city }}</span>
                            </div>
                            <div style="font-size:13px;font-weight:800;color:var(--primary)">{{ $event->format?->label() ?? 'In person' }}</div>
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
                <a href="{{ route('events.index') }}" style="border:0;background:none;cursor:pointer;font-size:12px;font-weight:700;color:var(--primary);text-decoration:none">Clear all</a>
            </div>

            @foreach ($filterGroups as $group)
                <div style="border-top:1px solid var(--border);padding:14px 0">
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:11px">{{ $group['label'] }}</div>
                    <div style="display:flex;flex-direction:column;gap:3px">
                        @foreach ($group['options'] as $option)
                            <a href="{{ $option['url'] }}" class="needs-focus" role="checkbox" aria-checked="{{ $option['checked'] ? 'true' : 'false' }}" style="display:flex;align-items:center;gap:10px;padding:8px 8px;min-height:38px;border:0;border-radius:9px;cursor:pointer;background:{{ $option['checked'] ? 'var(--chip)' : 'transparent' }};text-align:left;font-size:13px;font-weight:{{ $option['checked'] ? '800' : '600' }};color:{{ $option['checked'] ? 'var(--primary)' : 'var(--text)' }};text-decoration:none">
                                <span style="width:16px;height:16px;flex:0 0 auto;border-radius:5px;border:1.6px solid {{ $option['checked'] ? 'var(--primary)' : 'var(--border)' }};background:{{ $option['checked'] ? 'var(--primary)' : 'transparent' }};display:grid;place-items:center">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:{{ $option['checked'] ? 1 : 0 }}"><path d="M20 6 9 17l-5-5"></path></svg>
                                </span>
                                <span style="flex:1">{{ $option['label'] }}</span>
                                <span style="font-size:11px;color:var(--muted);font-weight:600">{{ $option['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div style="border-top:1px solid var(--border);padding-top:14px">
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:12px">Max price</div>
                <form method="GET" action="{{ route('events.index') }}" id="max-price-form">
                    @foreach (['category', 'format', 'time', 'search', 'city', 'sort', 'starts_from', 'starts_to'] as $keep)
                        @if (filled($filters[$keep] ?? null))
                            <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">
                        @endif
                    @endforeach
                    <input type="range" class="needs-focus" name="max_price" min="0" max="1000" step="50"
                           value="{{ $filters['max_price'] ?? 600 }}" aria-label="Maximum price"
                           onchange="this.form.submit()"
                           style="width:100%;accent-color:var(--primary)">
                </form>
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:var(--muted);margin-top:6px"><span>0 MAD</span><span>600 MAD</span></div>
            </div>
        </aside>

        <div>
            <form method="GET" action="{{ route('events.index') }}" style="display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap">

                @if (filled($filters['search'] ?? null))<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                <h2 style="margin:0;font-size:22px;font-weight:800;letter-spacing:-.6px">{{ $events->total() }} events found</h2>
                <div style="flex:1"></div>
                <span style="font-size:13px;color:var(--muted);font-weight:600">City</span>
                <select name="city" aria-label="Filter by city" class="needs-focus" onchange="this.form.submit()" style="min-height:40px;padding:9px 12px;border:1px solid var(--border);background:var(--surface);border-radius:11px;font-size:13px;font-weight:600">
                    <option value="" {{ ! filled($filters['city'] ?? null) ? 'selected' : '' }}>All cities</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" {{ ($filters['city'] ?? null) === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                </select>
                <span style="font-size:13px;color:var(--muted);font-weight:600">Sort by</span>
                <select name="sort" aria-label="Sort events" class="needs-focus" onchange="this.form.submit()" style="min-height:40px;padding:9px 12px;border:1px solid var(--border);background:var(--surface);border-radius:11px;font-size:13px;font-weight:600">
                    <option value="" {{ $sortSelected === '' || $sortSelected === 'recommended' ? 'selected' : '' }}>Recommended</option>
                    <option value="starts_at" {{ $sortSelected === 'starts_at' ? 'selected' : '' }}>Date (soonest)</option>
                    <option value="-starts_at" {{ $sortSelected === '-starts_at' ? 'selected' : '' }}>Date (latest)</option>
                    <option value="title" {{ $sortSelected === 'title' ? 'selected' : '' }}>Title (A → Z)</option>
                    <option value="-title" {{ $sortSelected === '-title' ? 'selected' : '' }}>Title (Z → A)</option>
                </select>
                <div style="display:flex;gap:4px;padding:4px;border:1px solid var(--border);background:var(--surface);border-radius:11px">
                    <button type="button" aria-label="Grid view" class="needs-focus" style="width:36px;height:36px;display:grid;place-items:center;border:0;border-radius:8px;cursor:pointer;background:var(--primary);color:#fff">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"></path></svg>
                    </button>
                    <button type="button" aria-label="List view" class="needs-focus" style="width:36px;height:36px;display:grid;place-items:center;border:0;border-radius:8px;cursor:pointer;background:transparent;color:var(--muted)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                    </button>
                </div>
            </form>

            @if ($events->isEmpty())
                <div style="border:1.5px dashed var(--border);border-radius:18px;padding:60px 26px;text-align:center;background:var(--surface)">
                    <div style="font-size:17px;font-weight:800;color:var(--text);margin-bottom:6px">No events found</div>
                    <div style="font-size:14px;color:var(--muted);font-weight:600">Try a different search or browse all events.</div>
                    <a href="{{ route('events.index') }}" style="display:inline-block;margin-top:18px;border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13.5px;padding:11px 20px;border-radius:11px;text-decoration:none">Browse all events</a>
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px">
                    @foreach ($events as $event)
                        <article class="ev-card" onclick="location.href='{{ $detailUrl($event) }}'" style="border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface);cursor:pointer">
                            <div style="position:relative;height:168px;background:{{ $coverBg($event) }}">
                                <span style="position:absolute;top:11px;left:11px;padding:5px 10px;border-radius:8px;background:rgba(255,255,255,.93);color:#0B2545;font-size:10.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase">{{ $event->category?->name ?? 'Event' }}</span>
                            </div>
                            <div style="padding:16px;flex:1;display:flex;flex-direction:column;gap:10px">
                                <h3 style="margin:0;font-size:16px;font-weight:700;letter-spacing:-.2px">{{ $event->title }}</h3>
                                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);font-weight:600;flex-wrap:wrap">
                                    <span>{{ $cardDate($event) }}</span><span style="opacity:.5">·</span><span>{{ $event->location }}</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;margin-top:auto">
                                    <span style="font-size:13.5px;font-weight:800;color:var(--primary)">{{ $event->format?->label() ?? 'In person' }}</span>
                                    <div style="flex:1"></div>
                                    <a href="{{ $detailUrl($event) }}" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px;text-decoration:none;display:inline-flex;align-items:center">View details</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div style="margin-top:32px">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== NEWSLETTER ===================== --}}
    <section aria-label="Newsletter" style="max-width:1380px;margin:0 auto;padding:0 26px 60px">
        @if (session('success'))
            <div role="status" style="margin-bottom:16px;padding:13px 18px;border-radius:12px;font-size:13.5px;font-weight:700;background:color-mix(in srgb,var(--ok) 12%,transparent);border:1px solid color-mix(in srgb,var(--ok) 40%,transparent);color:var(--ok)">{{ session('success') }}</div>
        @endif
        @if ($errors->has('email'))
            <div role="alert" style="margin-bottom:16px;padding:13px 18px;border-radius:12px;font-size:13.5px;font-weight:700;background:color-mix(in srgb,#E5484D 10%,transparent);border:1px solid color-mix(in srgb,#E5484D 40%,transparent);color:#E5484D">{{ $errors->first('email') }}</div>
        @endif
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
            <form method="POST" action="{{ route('newsletter.store') }}" style="position:relative;display:flex;gap:10px;background:rgba(255,255,255,.14);padding:8px;border-radius:14px;border:1px solid rgba(255,255,255,.28);margin:0">
                @csrf
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email" aria-label="Email" class="needs-focus"
                       style="border:0;background:none;padding:11px 14px;font-size:14px;color:#fff;outline:none;min-width:220px;min-height:44px">
                <button type="submit"
                        style="border:0;cursor:pointer;background:#fff;color:var(--primary-dark);font-weight:800;font-size:14px;padding:12px 22px;border-radius:10px;min-height:44px">Subscribe</button>
            </form>
        </div>
    </section>
</x-app-layout>
