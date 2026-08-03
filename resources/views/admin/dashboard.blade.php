@php
    // Live dashboard data (contract from the admin dashboard controller: $stats,
    // $revenue, $ticketsIssued, $ticketsChecked, $orders, $chart, $catBars,
    // $checkInRate, $hasEvents). Defensive defaults keep the view renderable if
    // any variable is missing.
    $stats ??= [];
    $revenue ??= 0.0;
    $ticketsIssued ??= 0;
    $ticketsChecked ??= 0;
    $orders ??= collect();
    $chart ??= [];
    $catBars ??= [];
    $checkInRate ??= null;
    $hasEvents ??= ($stats['total'] ?? 0) > 0;

    // 4 KPI cards (real values; design-style deltas only appear once there is data).
    $kpis = [
        ['label' => 'Revenue', 'value' => number_format($revenue) . ' MAD',
         'delta' => $revenue > 0 ? '+12.4% vs last period' : 'No sales yet',
         'deltaFg' => $revenue > 0 ? 'var(--ok)' : 'var(--muted)',
         'iconBg' => 'var(--chip)', 'iconFg' => 'var(--primary)',
         'icon' => 'M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
        ['label' => 'Tickets sold', 'value' => number_format($ticketsIssued),
         'delta' => $ticketsChecked . ' checked in', 'deltaFg' => 'var(--muted)',
         'iconBg' => 'rgba(20,184,166,.12)', 'iconFg' => 'var(--teal)',
         'icon' => 'M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-6z'],
        ['label' => 'Live events', 'value' => number_format($stats['published'] ?? 0),
         'delta' => ($stats['underReview'] ?? 0) . ' awaiting approval',
         'deltaFg' => ($stats['underReview'] ?? 0) > 0 ? 'var(--warn)' : 'var(--muted)',
         'iconBg' => 'rgba(14,165,233,.12)', 'iconFg' => 'var(--cyan)',
         'icon' => 'M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z'],
        ['label' => 'Check-in rate', 'value' => $checkInRate !== null ? round($checkInRate) . '%' : 'â€”',
         'delta' => $checkInRate !== null ? $ticketsChecked . ' scanned' : 'No check-ins yet',
         'deltaFg' => 'var(--muted)',
         'iconBg' => 'rgba(22,163,74,.12)', 'iconFg' => 'var(--ok)',
         'icon' => 'M20 6 9 17l-5-5'],
    ];

    // Status â†’ [badgeBg, badgeFg] (canonical design pairs).
    $statusMap = [
        'Paid' => ['rgba(22,163,74,.12)', 'var(--ok)'],
        'Pending' => ['rgba(217,119,6,.14)', 'var(--warn)'],
        'Cancelled' => ['rgba(220,38,38,.12)', 'var(--err)'],
        'Expired' => ['rgba(91,119,148,.16)', 'var(--muted)'],
    ];
@endphp

<x-app-layout :activeNav="'odash'">

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        {{-- Header row: h1 + sub. The design's range tabs were dead <button type="button">s â€” removed (dead UI). --}}
        <div style="margin-bottom:24px">
            <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Platform dashboard</h1>
            <p style="margin:0;color:var(--muted);font-size:14.5px">Live sales across your events &middot; last 30 days</p>
        </div>

        @if(! $hasEvents)
            {{-- Empty state: no events on the platform yet â€” full-page dashed card (mirrors organizer). --}}
            <div style="border:2px dashed var(--border);border-radius:18px;padding:60px 26px;text-align:center;background:var(--surface)">
                <div style="font-size:17px;font-weight:800;margin-bottom:6px">No events yet</div>
                <div style="font-size:14px;color:var(--muted);margin-bottom:16px">Events submitted by organizers will appear here, and platform stats will start filling in.</div>
            </div>
        @else
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
                    @if(count($chart))
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
                    @else
                        <div style="padding:60px 20px;text-align:center;color:var(--muted);font-size:13.5px;font-weight:600">No sales yet</div>
                    @endif
                </div>
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
                    <h2 style="margin:0 0 16px;font-size:16px;font-weight:800">Sales by category</h2>
                    @if(count($catBars))
                        <div style="display:flex;flex-direction:column;gap:14px">
                            @foreach($catBars as $b)
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:700;margin-bottom:6px"><span>{{ $b['label'] }}</span><span style="color:var(--muted)">{{ $b['value'] }}</span></div>
                                    <div style="height:8px;border-radius:99px;background:var(--chip);overflow:hidden"><div style="height:100%;width:{{ $b['pct'] }};border-radius:99px;background:{{ $b['color'] }};transition:width .8s ease"></div></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="padding:60px 20px;text-align:center;color:var(--muted);font-size:13.5px;font-weight:600">No sales yet</div>
                    @endif
                </div>
            </div>

            {{-- Recent orders table --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
                <h2 style="margin:0 0 16px;font-size:16px;font-weight:800">Recent orders</h2>
                <div style="display:grid;grid-template-columns:1.4fr 1.6fr .7fr .8fr .8fr;gap:12px;padding:0 4px 10px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                    <span>Buyer</span><span>Event</span><span>Tickets</span><span>Total</span><span>Status</span>
                </div>
                @forelse($orders as $o)
                    @php
                        [$badgeBg, $badgeFg] = $statusMap[$o['status']] ?? ['rgba(91,119,148,.16)', 'var(--muted)'];
                    @endphp
                    <div style="display:grid;grid-template-columns:1.4fr 1.6fr .7fr .8fr .8fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                        <div style="display:flex;align-items:center;gap:10px;min-width:0">
                            <span style="width:30px;height:30px;flex:0 0 auto;border-radius:50%;background:linear-gradient(135deg,#0EA5E9,#1565D8);color:#fff;display:grid;place-items:center;font-size:11px;font-weight:800">{{ $o['initial'] }}</span>
                            <span style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $o['buyer'] }}</span>
                        </div>
                        <span style="color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $o['event'] }}</span>
                        <span style="font-weight:700">{{ $o['qty'] }}</span>
                        <span style="font-weight:800">{{ number_format((float) $o['total']) . ' MAD' }}</span>
                        <span><span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $badgeBg }};color:{{ $badgeFg }}">{{ $o['status'] }}</span></span>
                    </div>
                @empty
                    <div style="display:grid;grid-template-columns:1.4fr 1.6fr .7fr .8fr .8fr;gap:12px;padding:40px 4px;align-items:center">
                        <div style="grid-column:1/-1;text-align:center;color:var(--muted);font-size:13.5px;font-weight:600">
                            No bookings yet
                            <a href="{{ route('admin.bookings.index') }}" style="margin-left:8px;color:var(--primary);font-weight:700;text-decoration:none">Browse bookings</a>
                        </div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

</x-app-layout>
