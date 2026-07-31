@php
    // Design dashTitle (L1593): admin role → "Platform dashboard", otherwise "Welcome back, <first name>".
    $dashRole = request('role', 'organizer');
    $dashRole = in_array($dashRole, ['organizer', 'admin'], true) ? $dashRole : 'organizer';
    $dashFirstName = ucfirst(str_word_count(Auth::user()->name ?? '') > 0 ? strtok((string) Auth::user()->name, ' ') : 'there');
    $dashTitle = $dashRole === 'admin' ? 'Platform dashboard' : 'Welcome back, ' . $dashFirstName;

    // Live counts (real data, passed from the organizer dashboard controller).
    $stats ??= [];
    $liveCount = $stats['published'] ?? 0;
    $pendingCount = $stats['underReview'] ?? 0;

    // ── Design sample data, ported 1:1 from design-evently-home.html (organizer role, range "30 days").
    // Revenue / tickets sold / check-in rate have no data source yet (no orders or tickets tables),
    // so those values stay as the design's sample numbers. ──
    $kpis = [
        ['label' => 'Revenue', 'value' => '8,673,560 MAD', 'delta' => '+12.4% vs last period', 'deltaFg' => 'var(--ok)',
         'iconBg' => 'var(--chip)', 'iconFg' => 'var(--primary)',
         'icon' => 'M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
        ['label' => 'Tickets sold', 'value' => '51,278', 'delta' => '+8.1% vs last period', 'deltaFg' => 'var(--ok)',
         'iconBg' => 'rgba(20,184,166,.12)', 'iconFg' => 'var(--teal)',
         'icon' => 'M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-6z'],
        ['label' => 'Live events', 'value' => number_format($liveCount), 'delta' => $pendingCount . ' awaiting approval', 'deltaFg' => $pendingCount > 0 ? 'var(--warn)' : 'var(--muted)',
         'iconBg' => 'rgba(14,165,233,.12)', 'iconFg' => 'var(--cyan)',
         'icon' => 'M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z'],
        ['label' => 'Check-in rate', 'value' => '87%', 'delta' => '214 scanned tonight', 'deltaFg' => 'var(--muted)',
         'iconBg' => 'rgba(22,163,74,.12)', 'iconFg' => 'var(--ok)',
         'icon' => 'M20 6 9 17l-5-5'],
    ];

    // Chart: design chartData for range "30 days" (W1..W5)
    $chart = [
        ['label' => 'W1', 'revH' => '49%', 'tixH' => '71%', 'revLabel' => '44,100 MAD', 'tixLabel' => '852 tickets'],
        ['label' => 'W2', 'revH' => '86%', 'tixH' => '65%', 'revLabel' => '77,400 MAD', 'tixLabel' => '780 tickets'],
        ['label' => 'W3', 'revH' => '55%', 'tixH' => '59%', 'revLabel' => '49,500 MAD', 'tixLabel' => '708 tickets'],
        ['label' => 'W4', 'revH' => '92%', 'tixH' => '53%', 'revLabel' => '82,800 MAD', 'tixLabel' => '636 tickets'],
        ['label' => 'W5', 'revH' => '61%', 'tixH' => '47%', 'revLabel' => '54,900 MAD', 'tixLabel' => '564 tickets'],
    ];

    // Sales by category (CATS[0..4], sold sums + pct of 45,000)
    $catBars = [
        ['label' => 'Music', 'value' => '9,660 tix', 'pct' => '21%', 'color' => 'var(--primary)'],
        ['label' => 'Business', 'value' => '3,100 tix', 'pct' => '7%', 'color' => 'var(--cyan)'],
        ['label' => 'Tech', 'value' => '790 tix', 'pct' => '2%', 'color' => 'var(--teal)'],
        ['label' => 'Art', 'value' => '1,398 tix', 'pct' => '3%', 'color' => '#7C3AED'],
        ['label' => 'Sports', 'value' => '38,000 tix', 'pct' => '84%', 'color' => '#F59E0B'],
    ];

    $statusMap = [
        'Paid' => ['rgba(22,163,74,.12)', 'var(--ok)'],
        'Pending' => ['rgba(217,119,6,.14)', 'var(--warn)'],
        'Refunded' => ['rgba(220,38,38,.12)', 'var(--err)'],
    ];
    $orders = [
        ['buyer' => 'Yassine Benali', 'initial' => 'YB', 'event' => 'Saad Lamjarred Concert', 'qty' => '2', 'total' => '630 MAD', 'status' => 'Paid'],
        ['buyer' => 'Imane Tazi', 'initial' => 'IT', 'event' => 'Digital Future Summit', 'qty' => '1', 'total' => '420 MAD', 'status' => 'Pending'],
        ['buyer' => 'Omar Cherkaoui', 'initial' => 'OC', 'event' => 'Mawazine Festival 2026', 'qty' => '4', 'total' => '840 MAD', 'status' => 'Paid'],
        ['buyer' => 'Nadia El Fassi', 'initial' => 'NE', 'event' => 'Morocco vs Brazil', 'qty' => '3', 'total' => '472 MAD', 'status' => 'Paid'],
        ['buyer' => 'Karim Idrissi', 'initial' => 'KI', 'event' => 'The Phantom of the Opera', 'qty' => '2', 'total' => '378 MAD', 'status' => 'Refunded'],
        ['buyer' => 'Salma Bennis', 'initial' => 'SB', 'event' => 'Sunset Beach Party', 'qty' => '5', 'total' => '525 MAD', 'status' => 'Paid'],
    ];
    foreach ($orders as $i => $o) {
        $orders[$i]['badgeBg'] = $statusMap[$o['status']][0];
        $orders[$i]['badgeFg'] = $statusMap[$o['status']][1];
    }
@endphp

<x-app-layout :activeRole="'organizer'" :navRole="'organizer'" :avatarRole="'organizer'" :activeNav="'odash'">

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        {{-- Header row: h1 + sub, range tabs, New event btn --}}
        <div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:24px">
            <div>
                <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">{{ $dashTitle }}</h1>
                <p style="margin:0;color:var(--muted);font-size:14.5px">Live sales across your events &middot; last 30 days</p>
            </div>
            <div style="flex:1"></div>
            <div style="display:flex;gap:4px;padding:4px;border:1px solid var(--border);background:var(--surface);border-radius:12px">
                @foreach(['7 days', '30 days', '12 months'] as $range)
                    <button type="button" style="border:0;cursor:pointer;padding:9px 14px;min-height:40px;border-radius:9px;font-size:12.5px;font-weight:700;background:{{ $range === '30 days' ? 'var(--primary)' : 'transparent' }};color:{{ $range === '30 days' ? '#fff' : 'var(--muted)' }}">{{ $range }}</button>
                @endforeach
            </div>
            <a href="{{ route('organizer.events.create') }}" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:46px;text-decoration:none;display:inline-flex;align-items:center">+ New event</a>
        </div>

        {{-- KPI cards --}}
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px">
            @foreach($kpis as $k)
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:17px;padding:18px;animation:up .5s ease both">
                    <div style="display:flex;align-items:center;gap:9px;margin-bottom:12px">
                        <span style="width:32px;height:32px;border-radius:10px;background:{{ $k['iconBg'] }};display:grid;place-items:center;color:{{ $k['iconFg'] }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $k['icon'] }}"></path></svg>
                        </span>
                        <span style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px">{{ $k['label'] }}</span>
                    </div>
                    <div style="font-size:27px;font-weight:800;letter-spacing:-1px">{{ $k['value'] }}</div>
                    <div style="font-size:12px;font-weight:700;color:{{ $k['deltaFg'] }};margin-top:5px">{{ $k['delta'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Charts row: Revenue & tickets + Sales by category --}}
        <div style="display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:18px;margin-bottom:20px;align-items:start">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
                    <h2 style="margin:0;font-size:16px;font-weight:800">Revenue &amp; tickets</h2>
                    <div style="flex:1"></div>
                    <div style="display:flex;gap:14px">
                        @foreach([['label' => 'Revenue', 'color' => 'var(--primary)'], ['label' => 'Tickets', 'color' => 'var(--border)']] as $l)
                            <span style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--muted)"><span style="width:9px;height:9px;border-radius:3px;background:{{ $l['color'] }}"></span>{{ $l['label'] }}</span>
                        @endforeach
                    </div>
                </div>
                <div style="display:flex;align-items:flex-end;gap:12px;height:190px;padding-bottom:6px">
                    @foreach($chart as $c)
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end">
                            <div style="width:100%;display:flex;gap:4px;align-items:flex-end;height:100%">
                                <div title="{{ $c['revLabel'] }}" style="flex:1;height:{{ $c['revH'] }};border-radius:6px 6px 0 0;background:linear-gradient(180deg,var(--primary),var(--cyan));transition:height .7s cubic-bezier(.22,1,.36,1)"></div>
                                <div title="{{ $c['tixLabel'] }}" style="flex:1;height:{{ $c['tixH'] }};border-radius:6px 6px 0 0;background:var(--chip);border:1px solid var(--border);transition:height .7s cubic-bezier(.22,1,.36,1)"></div>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:var(--muted)">{{ $c['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
                <h2 style="margin:0 0 16px;font-size:16px;font-weight:800">Sales by category</h2>
                <div style="display:flex;flex-direction:column;gap:14px">
                    @foreach($catBars as $b)
                        <div>
                            <div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:700;margin-bottom:6px"><span>{{ $b['label'] }}</span><span style="color:var(--muted)">{{ $b['value'] }}</span></div>
                            <div style="height:8px;border-radius:99px;background:var(--chip);overflow:hidden"><div style="height:100%;width:{{ $b['pct'] }};border-radius:99px;background:{{ $b['color'] }};transition:width .8s ease"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent orders table --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <h2 style="margin:0;font-size:16px;font-weight:800">Recent orders</h2>
                <div style="flex:1"></div>
                <button type="button" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:12.5px;font-weight:700;padding:10px 15px;border-radius:10px;min-height:40px">Export CSV</button>
            </div>
            <div style="display:grid;grid-template-columns:1.4fr 1.6fr .7fr .8fr .8fr;gap:12px;padding:0 4px 10px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span>Buyer</span><span>Event</span><span>Tickets</span><span>Total</span><span>Status</span>
            </div>
            @foreach($orders as $o)
                <div style="display:grid;grid-template-columns:1.4fr 1.6fr .7fr .8fr .8fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0">
                        <span style="width:30px;height:30px;flex:0 0 auto;border-radius:50%;background:linear-gradient(135deg,#0EA5E9,#1565D8);color:#fff;display:grid;place-items:center;font-size:11px;font-weight:800">{{ $o['initial'] }}</span>
                        <span style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $o['buyer'] }}</span>
                    </div>
                    <span style="color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $o['event'] }}</span>
                    <span style="font-weight:700">{{ $o['qty'] }}</span>
                    <span style="font-weight:800">{{ $o['total'] }}</span>
                    <span><span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $o['badgeBg'] }};color:{{ $o['badgeFg'] }}">{{ $o['status'] }}</span></span>
                </div>
            @endforeach
        </div>
    </div>

</x-app-layout>
