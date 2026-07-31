<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Evently') }} &mdash; Event booking platform</title>

    {{-- Prevent FOUC: apply dark theme from localStorage before any styles render --}}
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark')document.documentElement.dataset.theme='dark';}catch(e){}})()</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased" style="margin:0;background:var(--bg);color:var(--text)">

{{-- ── Props: role preview switches (pure Blade, no JS auth) ── --}}
@props([
    'activeRole' => 'guest',
    'navRole' => 'guest',
    'avatarRole' => 'guest',
    'activeNav' => null,
])

@php
    // Design roleTabs (design-evently-home.html L1584-1589): active = primary bg/white fg.
    // Clicking a tab switches role AND route (design L1588): guest/user→events, organizer→odash, admin→admin.
    // The events page renders per ?role= so Guest and User tabs show their OWN shell (nav + avatar).
    $roleTabs = [
        ['label' => 'Guest', 'role' => 'guest', 'href' => '/preview/events'],
        ['label' => 'User', 'role' => 'user', 'href' => '/preview/events?role=user'],
        ['label' => 'Organizer', 'role' => 'organizer', 'href' => '/preview/odash'],
        ['label' => 'Admin', 'role' => 'admin', 'href' => '/preview/admin'],
    ];

    // Preview path per route key (design route keys L1575-1583, L1591-1597).
    $routePaths = [
        'events' => '/preview/events', 'detail' => '/preview/events/atlantis-live',
        'login' => '/preview/login', 'register' => '/preview/register', 'forgot' => '/preview/forgot',
        'ubookings' => '/preview/ubookings', 'booking' => '/preview/booking',
        'tickets' => '/preview/tickets', 'profile' => '/preview/profile',
        'odash' => '/preview/odash', 'oevents' => '/preview/oevents',
        'scan' => '/preview/scan', 'admin' => '/preview/admin',
    ];

    // Design topNav per role (design L1575-1583) — label + route key.
    $navItems = [
        'guest' => [
            ['label' => 'Events', 'key' => 'events'],
            ['label' => 'Sign in', 'key' => 'login'],
            ['label' => 'Create account', 'key' => 'register'],
        ],
        'user' => [
            ['label' => 'Events', 'key' => 'events'],
            ['label' => 'My bookings', 'key' => 'ubookings'],
            ['label' => 'My tickets', 'key' => 'tickets'],
            ['label' => 'Profile', 'key' => 'profile'],
        ],
        'organizer' => [
            ['label' => 'Dashboard', 'key' => 'odash'],
            ['label' => 'My events', 'key' => 'oevents'],
            ['label' => 'Check-in', 'key' => 'scan'],
            ['label' => 'Browse', 'key' => 'events'],
            ['label' => 'Profile', 'key' => 'profile'],
        ],
        'admin' => [
            ['label' => 'Admin', 'key' => 'admin'],
            ['label' => 'Dashboard', 'key' => 'odash'],
            ['label' => 'Check-in', 'key' => 'scan'],
            ['label' => 'Browse', 'key' => 'events'],
            ['label' => 'Profile', 'key' => 'profile'],
        ],
    ];

    // Design roleMeta avatars (design L1560-1563): gradient + initials.
    $avatarMap = [
        'guest' => ['grad' => 'linear-gradient(135deg,#5B7794,#8FAAC6)', 'initials' => 'G'],
        'user' => ['grad' => 'linear-gradient(135deg,#0EA5E9,#1565D8)', 'initials' => 'YB'],
        'organizer' => ['grad' => 'linear-gradient(135deg,#7C3AED,#1565D8)', 'initials' => 'SL'],
        'admin' => ['grad' => 'linear-gradient(135deg,#DC2626,#F59E0B)', 'initials' => 'AD'],
    ];

    // Design start route per role (roleTabs.go L1588): guest/user→events, organizer→odash, admin→admin.
    $defaultNav = ['guest' => 'events', 'user' => 'events', 'organizer' => 'odash', 'admin' => 'admin'];
    $activeKey = $activeNav ?: ($defaultNav[$navRole] ?? 'events');
    $nav = $navItems[$navRole] ?? $navItems['guest'];
    $avatar = $avatarMap[$avatarRole] ?? $avatarMap['guest'];
    // Keep the role through the shell on shared pages (events/detail); dedicated pages set their own props.
    $roleSuffix = $activeRole !== 'guest' ? '?role=' . $activeRole : '';
@endphp

{{-- ── Alpine.js dark mode (sidebar removed — design sidebarOn:false, navGroups:[]) ── --}}
<div x-data="{
    dark: localStorage.getItem('theme') === 'dark',
    toggle() {
        this.dark = !this.dark;
        document.documentElement.dataset.theme = this.dark ? 'dark' : '';
        localStorage.setItem('theme', this.dark ? 'dark' : '');
    },
    init() {
        if (this.dark) {
            document.documentElement.dataset.theme = 'dark';
        }
    }
}" style="display:flex;min-height:100vh;background:var(--bg)">

    {{-- ═══════════════════════ MAIN COLUMN ═══════════════════════ --}}
    <div style="flex:1;min-width:0;display:flex;flex-direction:column">

        {{-- ═══════════════════════ HEADER ═══════════════════════ --}}
        <header style="position:sticky;top:0;z-index:40;background:var(--header-bg);backdrop-filter:blur(14px);border-bottom:1px solid var(--border)">
            <div style="max-width:1380px;margin:0 auto;padding:0 26px;height:66px;display:flex;align-items:center;gap:26px">
                {{-- Logo / wordmark --}}
                <a href="{{ url('/preview/events' . $roleSuffix) }}" style="display:flex;align-items:center;gap:9px;background:none;border:0;cursor:pointer;padding:0;text-decoration:none">
                    <div style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--cyan));display:grid;place-items:center;color:#fff;font-weight:800">E</div>
                    <span style="font-weight:800;font-size:20px;letter-spacing:-.5px;color:var(--primary)">Evently</span>
                </a>

                {{-- Header top-nav tabs (role-dependent, design L275-281: 14px, gap 4px, pad 10px 14px, min-h 44px, active 800/primary, inactive 600/--text) --}}
                <nav aria-label="Main" style="display:flex;align-items:center;gap:4px;margin-left:8px">
                    @foreach($nav as $item)
                        @php $isActive = $item['key'] === $activeKey; @endphp
                        <a href="{{ ($routePaths[$item['key']] ?? '#') . $roleSuffix }}"
                           style="border:0;background:none;cursor:pointer;padding:10px 14px;min-height:44px;font-size:14px;font-weight:{{ $isActive ? '800' : '600' }};color:{{ $isActive ? 'var(--primary)' : 'var(--text)' }};border-bottom:2px solid {{ $isActive ? 'var(--primary)' : 'transparent' }};text-decoration:none;border-radius:0">{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                {{-- Breeze $header slot --}}
                @isset($header){{ $header }}@endisset

                <div style="flex:1"></div>

                {{-- Right controls --}}
                <div style="display:flex;align-items:center;gap:8px">
                    {{-- Role preview tabs strip (design L286-290) --}}
                    <div style="display:flex;align-items:center;gap:6px;padding:4px;background:var(--surface2);border:1px solid var(--border);border-radius:11px">
                        @foreach($roleTabs as $tab)
                            @php $isRole = $tab['role'] === $activeRole; @endphp
                            <a href="{{ $tab['href'] }}" title="Preview as {{ $tab['label'] }}"
                               style="display:block;border:0;cursor:pointer;padding:7px 11px;border-radius:8px;font-size:12px;font-weight:700;background:{{ $isRole ? 'var(--primary)' : 'transparent' }};color:{{ $isRole ? '#fff' : 'var(--muted)' }};text-decoration:none">{{ $tab['label'] }}</a>
                        @endforeach
                    </div>

                    {{-- Theme toggle --}}
                    <button type="button" @click="toggle()" aria-label="Toggle dark mode"
                            style="width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface);border-radius:11px;cursor:pointer;color:var(--muted)">
                        {{-- Sun icon (shown in dark mode) --}}
                        <svg x-show="dark" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
                        </svg>
                        {{-- Moon icon (shown in light mode) --}}
                        <svg x-show="!dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>

                    {{-- My tickets button (design L290: rendered for every role, incl. guest) --}}
                    <a href="{{ '/preview/tickets' . $roleSuffix }}" aria-label="My tickets" style="position:relative;width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface);border-radius:11px;cursor:pointer;color:var(--muted);text-decoration:none">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                            <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-6z"></path>
                        </svg>
                        @if(session('cart_count', 0) > 0)
                            <span style="position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 4px;border-radius:9px;background:var(--primary);color:#fff;font-size:10px;font-weight:800;display:grid;place-items:center">{{ session('cart_count') }}</span>
                        @endif
                    </a>

                    {{-- Avatar (role gradient + initials) --}}
                    <div style="width:40px;height:40px;border-radius:50%;background:{{ $avatar['grad'] }};color:#fff;display:grid;place-items:center;font-weight:700;font-size:14px">{{ $avatar['initials'] }}</div>
                </div>
            </div>
        </header>

        {{-- ═══════════════════════ PAGE CONTENT ═══════════════════════ --}}
        <main style="flex:1;min-width:0">
            {{ $slot }}
        </main>

        {{-- ═══════════════════════ FOOTER ═══════════════════════ --}}
        <footer style="border-top:1px solid var(--border);background:var(--surface);padding:22px 26px">
            <div style="max-width:1380px;margin:0 auto;display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:12.5px;color:var(--muted);font-weight:600">
                <span>&copy; 2026 Evently &mdash; Casablanca, Morocco</span>
                <div style="flex:1"></div>
                <span>Prices in MAD</span>
                <span>&middot;</span>
                <span>Powered by Stripe</span>
            </div>
        </footer>
    </div>
</div>

@livewireScripts
</body>
</html>
