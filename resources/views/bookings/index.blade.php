{{-- My bookings — pixel-port of design rUBookings (lines 660–693) --}}

<x-app-layout :activeRole="'user'" :navRole="'user'" :avatarRole="'user'">
@php
    $bookings = [
        ['ref' => 'BK-4C19A7', 'event' => 'Saad Lamjarred Concert', 'date' => 'Sat, 15 Jun', 'city' => 'Rabat', 'qty' => 2, 'total' => '630 MAD', 'status' => 'Confirmed', 'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)', 'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)'],
        ['ref' => 'BK-77B210', 'event' => 'Digital Future Summit', 'date' => 'Tue, 11 Jun', 'city' => 'Casablanca', 'qty' => 1, 'total' => '420 MAD', 'status' => 'Pending', 'badgeBg' => 'rgba(217,119,6,.14)', 'badgeFg' => 'var(--warn)', 'grad' => 'linear-gradient(135deg,#082F49,#14B8A6)'],
        ['ref' => 'BK-2E90FF', 'event' => 'Casablanca Street Food Fest', 'date' => 'Sat, 20 Jun', 'city' => 'Casablanca', 'qty' => 4, 'total' => 'Free', 'status' => 'Confirmed', 'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)', 'grad' => 'linear-gradient(135deg,#7C2D12,#F59E0B)'],
        ['ref' => 'BK-19AA31', 'event' => 'The Phantom of the Opera', 'date' => 'Sun, 2 Jun', 'city' => 'Rabat', 'qty' => 2, 'total' => '378 MAD', 'status' => 'Cancelled', 'badgeBg' => 'rgba(220,38,38,.12)', 'badgeFg' => 'var(--err)', 'grad' => 'linear-gradient(135deg,#312E81,#DB2777)'],
    ];

    $allCount = count($bookings);
    $confirmedCount = count(array_filter($bookings, fn($b) => $b['status'] === 'Confirmed'));
    $pendingCount = count(array_filter($bookings, fn($b) => $b['status'] === 'Pending'));
    $cancelledCount = count(array_filter($bookings, fn($b) => $b['status'] === 'Cancelled'));
@endphp

<div style="max-width:1100px;margin:0 auto;padding:34px 26px 60px">
    <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">My bookings</h1>
    <p style="margin:0 0 22px;color:var(--muted);font-size:14.5px">Every order you placed on Evently, newest first.</p>

    {{-- Filter tabs --}}
    <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
        <button type="button" style="min-height:40px;padding:9px 15px;border:1px solid var(--primary);background:var(--primary);color:#fff;border-radius:11px;cursor:pointer;font-size:13px;font-weight:700">All <span style="opacity:.6">{{ $allCount }}</span></button>
        <button type="button" style="min-height:40px;padding:9px 15px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:11px;cursor:pointer;font-size:13px;font-weight:700">Confirmed <span style="opacity:.6">{{ $confirmedCount }}</span></button>
        <button type="button" style="min-height:40px;padding:9px 15px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:11px;cursor:pointer;font-size:13px;font-weight:700">Pending <span style="opacity:.6">{{ $pendingCount }}</span></button>
        <button type="button" style="min-height:40px;padding:9px 15px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:11px;cursor:pointer;font-size:13px;font-weight:700">Cancelled <span style="opacity:.6">{{ $cancelledCount }}</span></button>
    </div>

    {{-- Booking cards --}}
    <div style="display:flex;flex-direction:column;gap:12px">
        @foreach ($bookings as $b)
            <article style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                <div style="width:58px;height:58px;border-radius:14px;background:{{ $b['grad'] }};flex:0 0 auto"></div>
                <div style="flex:1;min-width:190px">
                    <div style="font-size:16px;font-weight:700;letter-spacing:-.2px">{{ $b['event'] }}</div>
                    <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:4px">{{ $b['date'] }} · {{ $b['city'] }} · ref {{ $b['ref'] }}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.6px">{{ $b['qty'] }} tickets</div>
                    <div style="font-size:16px;font-weight:800">{{ $b['total'] }}</div>
                </div>
                <span style="padding:7px 12px;border-radius:9px;font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;background:{{ $b['badgeBg'] }};color:{{ $b['badgeFg'] }}">{{ $b['status'] }}</span>
                <button type="button" onclick="location.href='/preview/booking'" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:13px;font-weight:700;padding:11px 16px;border-radius:11px;min-height:44px">Details</button>
            </article>
        @endforeach
    </div>
</div>
</x-app-layout>
