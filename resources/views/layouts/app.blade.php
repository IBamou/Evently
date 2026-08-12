<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Evently') }} &mdash; Event booking platform</title>

    {{-- Prevent FOUC: apply dark theme from localStorage before any styles render --}}
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark')document.documentElement.dataset.theme='dark';}catch(e){}})()</script>

    <style>
        /* ── Responsive header safety (the design has no media queries; minimal rules) ──
           <900px: tighter side padding + horizontally scrollable nav.
           <640px: hide the attendee "My tickets" bell shortcut (keep theme + account). */
        @media (max-width: 900px) {
            header { padding-left: 16px !important; padding-right: 16px !important; }
            header nav { overflow-x: auto; scrollbar-width: none; }
            header nav::-webkit-scrollbar { display: none; }
        }
        @media (max-width: 640px) {
            header nav { display: none !important; }
            header a[aria-label="My tickets"] { display: none; }
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased" style="margin:0;background:var(--bg);color:var(--text)">

{{-- ── Props ── --}}
@props([
    'activeNav' => null,
])

@php
    $isGuest = auth()->guest();
    $navRole = auth()->user()?->role?->value ?? 'guest';
    $avatarRole = $navRole;

    // Design topNav per role (design L1575-1583) — label + route key.
    // Every nav key below resolves to a REAL named route. Guest-only pages
    // (bookings/tickets/profile/check-in/organizer/admin) go through auth/role
    // middleware which redirects guests to route('login') — exactly what we want.
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

    $defaultNav = ['guest' => 'events', 'user' => 'events', 'organizer' => 'odash', 'admin' => 'odash'];
    $activeKey = $activeNav ?: ($defaultNav[$navRole] ?? 'events');

    // Admin secondary pages (bookings/tickets/payments/categories) share the
    // "Admin" top-nav tab — normalize so it stays highlighted on those pages.
    if (in_array($activeKey, ['bookings', 'admin.tickets', 'payments', 'categories'], true)) {
        $activeKey = 'admin';
    }

    $nav = $navItems[$navRole] ?? $navItems['guest'];
    $avatar = $avatarMap[$avatarRole] ?? $avatarMap['guest'];

    // Avatar initials: logged-in users get the REAL name's first+last initials
    // (e.g. "Yassine Benali" → "YB"); guests keep the generic initial.
    $avatarInitials = $avatar['initials'];
    if (! $isGuest) {
        $nameParts = preg_split('/\s+/', trim((string) auth()->user()->name));
        $realInitials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1));
        $avatarInitials = $realInitials !== '' ? $realInitials : $avatar['initials'];
    }

    // Single source of truth for nav hrefs (top nav + any future nav).
    $resolveHref = function (string $key, ?\App\Models\Event $event = null): string {
        return match ($key) {
            'login' => route('login'),
            'register' => route('register'),
            'events' => route('events.index'),
            'profile' => route('profile.edit'),
            'odash' => auth()->user()?->isAdmin() ? route('admin.dashboard') : route('organizer.dashboard'),
            'admin' => route('admin.events.index'),
            'oevents' => route('organizer.events.index'),
            'ubookings' => route('bookings.index'),
            'tickets' => route('tickets.index'),
            'admin.tickets' => route('admin.tickets.index'),
            'bookings' => route('admin.bookings.index'),
            'payments' => route('admin.payments.index'),
            'categories' => route('admin.categories.index'),
            'scan' => route('organizer.check-in.picker'),
            default => '#',
        };
    };
@endphp

{{-- ── Alpine.js dark mode + account menu state ── --}}
<div x-data="{
    dark: localStorage.getItem('theme') === 'dark',
    accountMenuOpen: false,
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
        <header style="height:66px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 26px;gap:26px;flex-shrink:0;backdrop-filter:blur(14px)">
            <div style="max-width:1380px;margin:0 auto;width:100%;display:flex;align-items:center;gap:26px">
                {{-- Logo / wordmark: logged-in users go to the role dashboard, guests to the public events page --}}
                @auth
                    <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:9px;background:none;border:0;cursor:pointer;padding:0;text-decoration:none">
                        <div style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--cyan));display:grid;place-items:center;color:#fff;font-weight:800">E</div>
                        <span style="font-weight:800;font-size:20px;letter-spacing:-.5px;color:var(--primary)">Evently</span>
                    </a>
                @else
                    <a href="{{ route('events.index') }}" style="display:flex;align-items:center;gap:9px;background:none;border:0;cursor:pointer;padding:0;text-decoration:none">
                        <div style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--cyan));display:grid;place-items:center;color:#fff;font-weight:800">E</div>
                        <span style="font-weight:800;font-size:20px;letter-spacing:-.5px;color:var(--primary)">Evently</span>
                    </a>
                @endauth

                {{-- Header top-nav tabs (role-dependent, design L275-281: 14px, gap 4px, pad 10px 14px, min-h 44px, active 800/primary, inactive 600/--text) --}}
                <nav aria-label="Main" style="display:flex;align-items:center;gap:4px;margin-left:8px">
                    @foreach($nav as $item)
                        @php
                            $isActive = $item['key'] === $activeKey;
                            $href = $resolveHref($item['key']);
                        @endphp
                        <a href="{{ $href }}"
                           style="border:0;background:none;cursor:pointer;padding:10px 14px;min-height:44px;font-size:14px;font-weight:{{ $isActive ? '800' : '600' }};color:{{ $isActive ? 'var(--primary)' : 'var(--text)' }};border-bottom:2px solid {{ $isActive ? 'var(--primary)' : 'transparent' }};text-decoration:none;border-radius:0">{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                {{-- Breeze $header slot --}}
                @isset($header){{ $header }}@endisset

                <div style="flex:1"></div>

                {{-- Right controls (shared partial: theme toggle, attendee tickets, account menu) --}}
                @include('layouts.partials.right-controls')
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

        {{-- Confirmation modal for danger actions (see layouts/partials/confirm-modal.blade.php) --}}
        @include('layouts.partials.confirm-modal')
    </div>
</div>

@livewireScripts

{{-- Password show/hide toggle (vanilla JS — works regardless of Alpine instances) --}}
<script>
function togglePassword(btn) {
    var input = btn.parentElement.querySelector('input');
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    var eyeOn  = btn.querySelector('.pw-eye-on');
    var eyeOff = btn.querySelector('.pw-eye-off');
    if (eyeOn)  eyeOn.style.display  = isHidden ? 'none' : '';
    if (eyeOff) eyeOff.style.display = isHidden ? '' : 'none';
    var label = isHidden ? 'Hide password' : 'Show password';
    btn.setAttribute('aria-label', label);
    btn.title = label;
}
</script>
</body>
</html>
