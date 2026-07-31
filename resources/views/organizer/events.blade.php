@php
    // ── Static demo data, ported 1:1 from design-evently-home.html (EVENTS.slice(0,6), evStatus) ──
    $statusMap = [
        'Published' => ['rgba(22,163,74,.12)', 'var(--ok)'],
        'Pending' => ['rgba(217,119,6,.14)', 'var(--warn)'],
        'Draft' => ['rgba(91,119,148,.14)', 'var(--muted)'],
    ];
    $rows = [
        ['title' => 'Saad Lamjarred Concert', 'city' => 'Rabat', 'date' => 'Sat, 15 Jun 2026', 'price' => '300 MAD', 'sold' => '1,840 / 2,400', 'pct' => '77%', 'status' => 'Published', 'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)'],
        ['title' => 'GITEX Africa Morocco', 'city' => 'Marrakech', 'date' => 'Wed, 29 May 2026', 'price' => '250 MAD', 'sold' => '3,100 / 4,000', 'pct' => '78%', 'status' => 'Published', 'grad' => 'linear-gradient(135deg,#0C4A6E,#0EA5E9)'],
        ['title' => 'Mawazine Festival 2026', 'city' => 'Rabat', 'date' => 'Fri, 21 Jun 2026', 'price' => '200 MAD', 'sold' => '7,400 / 9,000', 'pct' => '82%', 'status' => 'Pending', 'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)'],
        ['title' => 'The Phantom of the Opera', 'city' => 'Rabat', 'date' => 'Sun, 2 Jun 2026', 'price' => '180 MAD', 'sold' => '900 / 900', 'pct' => '100%', 'status' => 'Published', 'grad' => 'linear-gradient(135deg,#312E81,#DB2777)'],
        ['title' => 'Chefchaouen Photo Walk', 'city' => 'Chefchaouen', 'date' => 'Thu, 6 Jun 2026', 'price' => '120 MAD', 'sold' => '38 / 60', 'pct' => '63%', 'status' => 'Draft', 'grad' => 'linear-gradient(135deg,#312E81,#DB2777)'],
        ['title' => 'Morocco vs Brazil', 'city' => 'Tanger', 'date' => 'Sat, 8 Jun 2026', 'price' => '150 MAD', 'sold' => '38,000 / 45,000', 'pct' => '84%', 'status' => 'Pending', 'grad' => 'linear-gradient(135deg,#064E3B,#22C55E)'],
    ];
    foreach ($rows as $i => $r) {
        $rows[$i]['badgeBg'] = $statusMap[$r['status']][0];
        $rows[$i]['badgeFg'] = $statusMap[$r['status']][1];
    }
    $tabs = [
        ['label' => 'All', 'count' => 6, 'active' => true],
        ['label' => 'Published', 'count' => 3, 'active' => false],
        ['label' => 'Pending', 'count' => 2, 'active' => false],
        ['label' => 'Draft', 'count' => 1, 'active' => false],
    ];
    $actions = [
        ['label' => 'View', 'fg' => 'var(--muted)', 'icon' => 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z'],
        ['label' => 'Edit', 'fg' => 'var(--primary)', 'icon' => 'M4 20h4L20 8l-4-4L4 16z'],
        ['label' => 'Delete', 'fg' => 'var(--err)', 'icon' => 'M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13'],
    ];
@endphp

<x-app-layout :activeRole="'organizer'" :navRole="'organizer'" :avatarRole="'organizer'" :activeNav="'oevents'">

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        {{-- Header row --}}
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px">
            <div>
                <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">My events</h1>
                <p style="margin:0;color:var(--muted);font-size:14.5px">Publish, edit and track every event you run.</p>
            </div>
            <div style="flex:1"></div>
            <a href="/preview/create" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:46px;text-decoration:none;display:inline-flex;align-items:center">+ New event</a>
        </div>

        {{-- Status pills --}}
        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            @foreach($tabs as $t)
                <button type="button" style="min-height:40px;padding:9px 15px;border:1px solid {{ $t['active'] ? 'var(--primary)' : 'var(--border)' }};background:{{ $t['active'] ? 'var(--primary)' : 'var(--surface)' }};color:{{ $t['active'] ? '#fff' : 'var(--text)' }};border-radius:11px;cursor:pointer;font-size:13px;font-weight:700">{{ $t['label'] }} <span style="opacity:.6">{{ $t['count'] }}</span></button>
            @endforeach
        </div>

        {{-- Events table --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1.3fr .9fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span>Event</span><span>Date</span><span>Price</span><span>Sold</span><span>Status</span><span style="text-align:right">Actions</span>
            </div>
            @foreach($rows as $r)
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1.3fr .9fr 1fr;gap:12px;padding:14px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div style="display:flex;align-items:center;gap:11px;min-width:0">
                        <span style="width:38px;height:38px;flex:0 0 auto;border-radius:10px;background:{{ $r['grad'] }}"></span>
                        <div style="min-width:0">
                            <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $r['title'] }}</div>
                            <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $r['city'] }}</div>
                        </div>
                    </div>
                    <span style="color:var(--muted);font-weight:600">{{ $r['date'] }}</span>
                    <span style="font-weight:700">{{ $r['price'] }}</span>
                    <div>
                        <div style="font-size:12px;font-weight:700;margin-bottom:5px">{{ $r['sold'] }}</div>
                        <div style="height:6px;border-radius:99px;background:var(--chip);overflow:hidden"><div style="height:100%;width:{{ $r['pct'] }};background:linear-gradient(90deg,var(--primary),var(--cyan))"></div></div>
                    </div>
                    <span><span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $r['badgeBg'] }};color:{{ $r['badgeFg'] }}">{{ $r['status'] }}</span></span>
                    <div style="display:flex;gap:6px;justify-content:flex-end">
                        @foreach($actions as $a)
                            <button type="button" title="{{ $a['label'] }}" aria-label="{{ $a['label'] }}" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:{{ $a['fg'] }}">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $a['icon'] }}"></path></svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</x-app-layout>
