@php
    // ── Static demo data, ported 1:1 from design-evently-home.html (scan route, default state) ──
    // QR art = the design's qrPath("scanner") output (deterministic, computed once)
    $qrPath = 'M2 2h1v1h-1zM3 2h1v1h-1zM4 2h1v1h-1zM5 2h1v1h-1zM6 2h1v1h-1zM7 2h1v1h-1zM8 2h1v1h-1zM17 2h1v1h-1zM19 2h1v1h-1zM20 2h1v1h-1zM21 2h1v1h-1zM22 2h1v1h-1zM23 2h1v1h-1zM24 2h1v1h-1zM25 2h1v1h-1zM26 2h1v1h-1zM2 3h1v1h-1zM8 3h1v1h-1zM10 3h1v1h-1zM11 3h1v1h-1zM12 3h1v1h-1zM13 3h1v1h-1zM14 3h1v1h-1zM16 3h1v1h-1zM17 3h1v1h-1zM18 3h1v1h-1zM20 3h1v1h-1zM26 3h1v1h-1zM2 4h1v1h-1zM4 4h1v1h-1zM5 4h1v1h-1zM6 4h1v1h-1zM8 4h1v1h-1zM10 4h1v1h-1zM12 4h1v1h-1zM13 4h1v1h-1zM14 4h1v1h-1zM19 4h1v1h-1zM20 4h1v1h-1zM22 4h1v1h-1zM23 4h1v1h-1zM24 4h1v1h-1zM26 4h1v1h-1zM2 5h1v1h-1zM4 5h1v1h-1zM5 5h1v1h-1zM6 5h1v1h-1zM8 5h1v1h-1zM11 5h1v1h-1zM15 5h1v1h-1zM20 5h1v1h-1zM22 5h1v1h-1zM23 5h1v1h-1zM24 5h1v1h-1zM26 5h1v1h-1zM2 6h1v1h-1zM4 6h1v1h-1zM5 6h1v1h-1zM6 6h1v1h-1zM8 6h1v1h-1zM9 6h1v1h-1zM12 6h1v1h-1zM14 6h1v1h-1zM15 6h1v1h-1zM17 6h1v1h-1zM18 6h1v1h-1zM19 6h1v1h-1zM20 6h1v1h-1zM22 6h1v1h-1zM23 6h1v1h-1zM24 6h1v1h-1zM26 6h1v1h-1zM2 7h1v1h-1zM8 7h1v1h-1zM10 7h1v1h-1zM11 7h1v1h-1zM12 7h1v1h-1zM13 7h1v1h-1zM14 7h1v1h-1zM15 7h1v1h-1zM16 7h1v1h-1zM17 7h1v1h-1zM18 7h1v1h-1zM19 7h1v1h-1zM20 7h1v1h-1zM26 7h1v1h-1zM2 8h1v1h-1zM3 8h1v1h-1zM4 8h1v1h-1zM5 8h1v1h-1zM6 8h1v1h-1zM7 8h1v1h-1zM8 8h1v1h-1zM10 8h1v1h-1zM11 8h1v1h-1zM12 8h1v1h-1zM14 8h1v1h-1zM16 8h1v1h-1zM18 8h1v1h-1zM20 8h1v1h-1zM21 8h1v1h-1zM22 8h1v1h-1zM23 8h1v1h-1zM24 8h1v1h-1zM25 8h1v1h-1zM26 8h1v1h-1zM2 9h1v1h-1zM3 9h1v1h-1zM4 9h1v1h-1zM5 9h1v1h-1zM12 9h1v1h-1zM13 9h1v1h-1zM14 9h1v1h-1zM16 9h1v1h-1zM18 9h1v1h-1zM25 9h1v1h-1zM2 10h1v1h-1zM5 10h1v1h-1zM7 10h1v1h-1zM9 10h1v1h-1zM10 10h1v1h-1zM14 10h1v1h-1zM19 10h1v1h-1zM21 10h1v1h-1zM23 10h1v1h-1zM24 10h1v1h-1zM25 10h1v1h-1zM26 10h1v1h-1zM3 11h1v1h-1zM4 11h1v1h-1zM5 11h1v1h-1zM7 11h1v1h-1zM9 11h1v1h-1zM14 11h1v1h-1zM15 11h1v1h-1zM20 11h1v1h-1zM21 11h1v1h-1zM25 11h1v1h-1zM2 12h1v1h-1zM3 12h1v1h-1zM4 12h1v1h-1zM5 12h1v1h-1zM6 12h1v1h-1zM8 12h1v1h-1zM12 12h1v1h-1zM13 12h1v1h-1zM16 12h1v1h-1zM19 12h1v1h-1zM22 12h1v1h-1zM23 12h1v1h-1zM2 13h1v1h-1zM10 13h1v1h-1zM11 13h1v1h-1zM13 13h1v1h-1zM14 13h1v1h-1zM15 13h1v1h-1zM16 13h1v1h-1zM17 13h1v1h-1zM18 13h1v1h-1zM20 13h1v1h-1zM21 13h1v1h-1zM23 13h1v1h-1zM25 13h1v1h-1zM2 14h1v1h-1zM6 14h1v1h-1zM10 14h1v1h-1zM11 14h1v1h-1zM13 14h1v1h-1zM14 14h1v1h-1zM15 14h1v1h-1zM16 14h1v1h-1zM17 14h1v1h-1zM21 14h1v1h-1zM22 14h1v1h-1zM25 14h1v1h-1zM3 15h1v1h-1zM4 15h1v1h-1zM6 15h1v1h-1zM7 15h1v1h-1zM10 15h1v1h-1zM11 15h1v1h-1zM12 15h1v1h-1zM13 15h1v1h-1zM14 15h1v1h-1zM15 15h1v1h-1zM16 15h1v1h-1zM20 15h1v1h-1zM21 15h1v1h-1zM23 15h1v1h-1zM2 16h1v1h-1zM3 16h1v1h-1zM4 16h1v1h-1zM5 16h1v1h-1zM6 16h1v1h-1zM10 16h1v1h-1zM11 16h1v1h-1zM15 16h1v1h-1zM16 16h1v1h-1zM17 16h1v1h-1zM18 16h1v1h-1zM22 16h1v1h-1zM23 16h1v1h-1zM25 16h1v1h-1zM26 16h1v1h-1zM2 17h1v1h-1zM7 17h1v1h-1zM8 17h1v1h-1zM11 17h1v1h-1zM14 17h1v1h-1zM16 17h1v1h-1zM18 17h1v1h-1zM19 17h1v1h-1zM20 17h1v1h-1zM21 17h1v1h-1zM22 17h1v1h-1zM25 17h1v1h-1zM26 17h1v1h-1zM3 18h1v1h-1zM8 18h1v1h-1zM9 18h1v1h-1zM12 18h1v1h-1zM13 18h1v1h-1zM15 18h1v1h-1zM17 18h1v1h-1zM19 18h1v1h-1zM23 18h1v1h-1zM25 18h1v1h-1zM26 18h1v1h-1zM2 19h1v1h-1zM4 19h1v1h-1zM6 19h1v1h-1zM12 19h1v1h-1zM13 19h1v1h-1zM14 19h1v1h-1zM15 19h1v1h-1zM17 19h1v1h-1zM20 19h1v1h-1zM24 19h1v1h-1zM2 20h1v1h-1zM3 20h1v1h-1zM4 20h1v1h-1zM5 20h1v1h-1zM6 20h1v1h-1zM7 20h1v1h-1zM8 20h1v1h-1zM9 20h1v1h-1zM10 20h1v1h-1zM13 20h1v1h-1zM15 20h1v1h-1zM16 20h1v1h-1zM18 20h1v1h-1zM25 20h1v1h-1zM26 20h1v1h-1zM2 21h1v1h-1zM8 21h1v1h-1zM9 21h1v1h-1zM11 21h1v1h-1zM13 21h1v1h-1zM14 21h1v1h-1zM17 21h1v1h-1zM20 21h1v1h-1zM22 21h1v1h-1zM2 22h1v1h-1zM4 22h1v1h-1zM5 22h1v1h-1zM6 22h1v1h-1zM8 22h1v1h-1zM12 22h1v1h-1zM13 22h1v1h-1zM20 22h1v1h-1zM24 22h1v1h-1zM2 23h1v1h-1zM4 23h1v1h-1zM5 23h1v1h-1zM6 23h1v1h-1zM8 23h1v1h-1zM12 23h1v1h-1zM13 23h1v1h-1zM14 23h1v1h-1zM17 23h1v1h-1zM20 23h1v1h-1zM23 23h1v1h-1zM25 23h1v1h-1zM26 23h1v1h-1zM2 24h1v1h-1zM4 24h1v1h-1zM5 24h1v1h-1zM6 24h1v1h-1zM8 24h1v1h-1zM9 24h1v1h-1zM14 24h1v1h-1zM15 24h1v1h-1zM16 24h1v1h-1zM18 24h1v1h-1zM20 24h1v1h-1zM21 24h1v1h-1zM22 24h1v1h-1zM23 24h1v1h-1zM25 24h1v1h-1zM2 25h1v1h-1zM8 25h1v1h-1zM10 25h1v1h-1zM11 25h1v1h-1zM12 25h1v1h-1zM14 25h1v1h-1zM16 25h1v1h-1zM17 25h1v1h-1zM18 25h1v1h-1zM19 25h1v1h-1zM2 26h1v1h-1zM3 26h1v1h-1zM4 26h1v1h-1zM5 26h1v1h-1zM6 26h1v1h-1zM7 26h1v1h-1zM8 26h1v1h-1zM9 26h1v1h-1zM10 26h1v1h-1zM11 26h1v1h-1zM12 26h1v1h-1zM14 26h1v1h-1zM15 26h1v1h-1zM16 26h1v1h-1zM21 26h1v1h-1zM25 26h1v1h-1z';

    // Design scan state: stats 214/246/32, pct = round(214/246*100) = 87%
    $stats = [
        ['value' => '214', 'label' => 'Checked in'],
        ['value' => '246', 'label' => 'Tickets issued'],
        ['value' => '32', 'label' => 'Not yet arrived'],
    ];
    // Design scanLog has 4 rows; extended to 6 with design refs (BK-77B210-2, BK-8F21C4-1)
    $log = [
        ['code' => 'BK-4C19A7-1', 'when' => 'just now', 'color' => 'var(--ok)'],
        ['code' => 'BK-77B210-1', 'when' => '1 min ago', 'color' => 'var(--ok)'],
        ['code' => 'BK-19AA31-2', 'when' => '2 min ago', 'color' => 'var(--err)'],
        ['code' => 'BK-2E90FF-3', 'when' => '4 min ago', 'color' => 'var(--ok)'],
        ['code' => 'BK-77B210-2', 'when' => '6 min ago', 'color' => 'var(--ok)'],
        ['code' => 'BK-8F21C4-1', 'when' => '9 min ago', 'color' => 'var(--ok)'],
    ];
@endphp

<x-app-layout :activeRole="'organizer'" :navRole="'organizer'" :avatarRole="'organizer'" :activeNav="'scan'">

    <div style="max-width:1100px;margin:0 auto;padding:30px 26px 60px">
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Door check-in</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Scan attendee QR codes or type a reference manually.</p>

        <div style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start">
            {{-- Camera card --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
                <div style="position:relative;aspect-ratio:4/3;border-radius:15px;overflow:hidden;background:radial-gradient(circle at 50% 40%,#123B66,#071426)">
                    <div style="position:absolute;inset:14%;border-radius:12px;border:2px solid rgba(255,255,255,.35)"></div>
                    <div style="position:absolute;left:14%;right:14%;height:2px;background:linear-gradient(90deg,transparent,var(--cyan),transparent);box-shadow:0 0 14px var(--cyan);animation:scanline 2.4s ease-in-out infinite"></div>
                    <div style="position:absolute;inset:0;display:grid;place-items:center">
                        <svg width="120" height="120" viewBox="0 0 29 29" style="opacity:.28"><path d="{{ $qrPath }}" fill="#9BD3F2"></path></svg>
                    </div>
                    <span style="position:absolute;bottom:14px;left:0;right:0;text-align:center;color:rgba(255,255,255,.8);font-size:12.5px;font-weight:700">Camera active &middot; hold the QR steady</span>
                </div>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <input type="text" value="BK-4C19A7-1" placeholder="BK-4C19A7-1" aria-label="Ticket reference" style="flex:1;min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                    <button type="button" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:800;font-size:14px;padding:13px 24px;border-radius:12px;min-height:48px">Check in</button>
                </div>
                {{-- Result card — static ok state for the demo ref --}}
                <div style="margin-top:16px;display:flex;align-items:center;gap:14px;border:1px solid var(--ok);background:rgba(22,163,74,.08);border-radius:14px;padding:16px;animation:up .3s ease both">
                    <span style="width:38px;height:38px;flex:0 0 auto;border-radius:50%;background:var(--ok);display:grid;place-items:center">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                    </span>
                    <div>
                        <div style="font-size:15px;font-weight:800">Welcome in</div>
                        <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:3px">BK-4C19A7-1 &middot; General admission &middot; valid</div>
                    </div>
                </div>
            </div>

            {{-- Aside: tonight stats + recent scans --}}
            <aside style="display:flex;flex-direction:column;gap:16px">
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:14px">Tonight at the door</div>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        @foreach($stats as $st)
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $st['value'] }}</span>
                                <span style="font-size:12.5px;font-weight:700;color:var(--muted)">{{ $st['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div style="height:8px;border-radius:99px;background:var(--chip);overflow:hidden;margin-top:16px"><div style="height:100%;width:87%;background:linear-gradient(90deg,var(--primary),var(--cyan));transition:width .6s ease"></div></div>
                </div>
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:14px">Recent scans</div>
                    <div style="display:flex;flex-direction:column;gap:11px">
                        @foreach($log as $l)
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $l['color'] }};flex:0 0 auto"></span>
                                <span style="font-size:13px;font-weight:700;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $l['code'] }}</span>
                                <span style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $l['when'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>

</x-app-layout>
