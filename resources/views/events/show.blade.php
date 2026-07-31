{{-- Event detail — pixel-port of design rDetail (lines 503–598).
     Role-aware shell: ?role= keeps the previewing role (guest/user/organizer/admin). --}}
@php
    $role = request('role', 'guest');
    $role = in_array($role, ['guest', 'user', 'organizer', 'admin'], true) ? $role : 'guest';
@endphp

<x-app-layout :activeRole="$role" :navRole="$role" :avatarRole="$role">
@php
    $ev = [
        'title' => 'Saad Lamjarred Concert',
        'cat' => 'Music',
        'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)',
        'dateLong' => 'Sat, Jun 15, 2026 · 8:00 PM',
        'venue' => 'OLM Souissi',
        'city' => 'Rabat',
        'sold' => 1840,
        'cap' => 2400,
        'price' => 300,
        'fmt' => 'In person',
        'org' => 'Rabat Live',
    ];

    $ticketTypes = [
        ['name' => 'General admission', 'price' => 300, 'stockLeft' => 560, 'window' => 'On sale now', 'key' => 'ga'],
        ['name' => 'VIP lounge', 'price' => 660, 'stockLeft' => 40, 'window' => 'On sale now', 'key' => 'vip'],
        ['name' => 'Early bird', 'price' => 210, 'stockLeft' => 0, 'window' => 'Sale ended', 'key' => 'early'],
    ];

    $related = [
        ['title' => 'Mawazine Festival 2026', 'date' => 'Fri, 21 Jun', 'city' => 'Rabat', 'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)'],
        ['title' => 'Sunset Beach Party', 'date' => 'Sat, 29 Jun', 'city' => 'Salé', 'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)'],
        ['title' => 'Laravel & Vue Meetup', 'date' => 'Wed, 24 Jun', 'city' => 'Casablanca', 'grad' => 'linear-gradient(135deg,#082F49,#14B8A6)'],
    ];

    $soldPct = round(($ev['sold'] / $ev['cap']) * 100);
    $isSoldOut = $ev['sold'] >= $ev['cap'];
    $urgency = $isSoldOut
        ? 'This event is sold out — join the waitlist.'
        : ($soldPct > 70
            ? 'Selling fast — over ' . $soldPct . '% of tickets are gone.'
            : 'Tickets are still widely available.');
@endphp

<div style="max-width:1380px;margin:0 auto;padding:22px 26px 60px">
    {{-- Back link --}}
    <a href="{{ url('/preview/events' . ($role !== 'guest' ? '?role=' . $role : '')) }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:12px;display:inline-block;text-decoration:none">&larr; Back to events</a>

    {{-- Hero image/art area --}}
    <div style="position:relative;height:320px;border-radius:20px;overflow:hidden;background:{{ $ev['grad'] }};display:flex;align-items:flex-end;padding:28px">
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(4,20,40,0) 30%,rgba(4,20,40,.78))"></div>
        <div style="position:relative;display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <span style="padding:6px 11px;border-radius:8px;background:rgba(255,255,255,.92);color:#0B2545;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $ev['cat'] }}</span>
                <span style="padding:6px 11px;border-radius:8px;background:{{ $isSoldOut ? '#334155' : 'var(--ok)' }};color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $isSoldOut ? 'Sold out' : 'On sale' }}</span>
            </div>
            <h1 style="margin:0;color:#fff;font-size:38px;font-weight:800;letter-spacing:-1.2px;max-width:22ch;line-height:1.08">{{ $ev['title'] }}</h1>
            <div style="display:flex;gap:18px;flex-wrap:wrap;color:rgba(255,255,255,.9);font-size:14px;font-weight:600">
                <span>{{ $ev['dateLong'] }}</span>
                <span>{{ $ev['venue'] }}, {{ $ev['city'] }}</span>
            </div>
        </div>
    </div>

    {{-- Content grid --}}
    <div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:26px;margin-top:26px;align-items:start">

        {{-- Left column --}}
        <div style="display:flex;flex-direction:column;gap:20px">

            {{-- About card --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
                <h2 style="margin:0 0 12px;font-size:18px;font-weight:800;letter-spacing:-.4px">About this event</h2>
                <p style="margin:0;color:var(--muted);font-size:14.5px;line-height:1.75;text-wrap:pretty">Join {{ number_format($ev['sold']) }} people at {{ $ev['venue'] }} in {{ $ev['city'] }} for one of the most anticipated {{ strtolower($ev['cat']) }} events of the season. Doors open one hour before the start; bring your QR ticket on your phone — no printing needed. Food, drinks and accessible seating are available on site.</p>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:22px">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Date & time</div>
                        <div style="font-size:14px;font-weight:700">{{ $ev['dateLong'] }}</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Format</div>
                        <div style="font-size:14px;font-weight:700">{{ $ev['fmt'] }}</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Category</div>
                        <div style="font-size:14px;font-weight:700">{{ $ev['cat'] }}</div>
                    </div>
                </div>
            </div>

            {{-- Organizer card --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--cyan));color:#fff;display:grid;place-items:center;font-weight:800">{{ substr($ev['org'], 0, 1) }}</div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px">Organizer</div>
                        <div style="font-size:15px;font-weight:700">{{ $ev['org'] }}</div>
                    </div>
                    <div style="flex:1"></div>
                    <button type="button" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:13px;font-weight:700;padding:11px 16px;border-radius:11px;min-height:44px">Share</button>
                </div>
            </div>

            {{-- You may also like --}}
            <div>
                <h2 style="margin:0 0 14px;font-size:18px;font-weight:800;letter-spacing:-.4px">You may also like</h2>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                    @foreach ($related as $e)
                        <article style="border:1px solid var(--border);border-radius:15px;overflow:hidden;background:var(--surface);cursor:pointer" class="ev-card">
                            <div style="height:96px;background:{{ $e['grad'] }}"></div>
                            <div style="padding:13px">
                                <div style="font-size:14px;font-weight:700;margin-bottom:6px">{{ $e['title'] }}</div>
                                <div style="font-size:12px;color:var(--muted);font-weight:600">{{ $e['date'] }} · {{ $e['city'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right aside (sticky) --}}
        <aside style="position:sticky;top:82px;display:flex;flex-direction:column;gap:16px">

            {{-- Booking widget --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:var(--shadow)">
                <div style="font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:14px">Select tickets</div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach ($ticketTypes as $t)
                        @php
                            $isActive = false;
                            $count = 0;
                            $bdStyle = $isActive ? 'var(--primary)' : 'var(--border)';
                            $bgStyle = $isActive ? 'var(--chip)' : 'var(--surface)';
                        @endphp
                        <div style="border:1px solid {{ $bdStyle }};border-radius:14px;padding:14px;background:{{ $bgStyle }}">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:14px;font-weight:700">{{ $t['name'] }}</div>
                                    <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:3px">{{ $t['stockLeft'] > 0 ? $t['stockLeft'] . ' left' : 'Unavailable' }}</div>
                                </div>
                                <div style="font-size:14px;font-weight:800;color:var(--primary)">{{ $t['price'] === 0 ? 'Free' : number_format($t['price']) . ' MAD' }}</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;margin-top:12px">
                                <span style="font-size:11.5px;font-weight:700;color:var(--muted);flex:1">{{ $t['window'] }}</span>
                                <button type="button" aria-label="Remove one" style="width:36px;height:36px;border:1px solid var(--border);background:var(--surface);border-radius:9px;cursor:pointer;font-weight:800;font-size:16px;display:grid;place-items:center">&minus;</button>
                                <span style="min-width:22px;text-align:center;font-weight:800;font-size:15px">0</span>
                                <button type="button" aria-label="Add one" style="width:36px;height:36px;border:0;background:var(--primary);color:#fff;border-radius:9px;cursor:pointer;font-weight:800;font-size:16px;display:grid;place-items:center">+</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="border-top:1px solid var(--border);margin-top:16px;padding-top:16px;display:flex;flex-direction:column;gap:8px">
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);font-weight:600"><span>Subtotal</span><span>0 MAD</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);font-weight:600"><span>Service fee</span><span>0 MAD</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;letter-spacing:-.4px"><span>Total</span><span>0 MAD</span></div>
                </div>
                <button type="button" disabled style="width:100%;margin-top:16px;border:0;cursor:pointer;background:var(--muted);color:#fff;font-weight:800;font-size:15px;padding:15px;border-radius:13px;min-height:52px;opacity:.65">Select tickets to continue</button>
                <p style="margin:10px 0 0;font-size:11.5px;color:var(--muted);text-align:center">Secure payment via Stripe · instant QR ticket</p>
            </div>

            {{-- Sales progress --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:9px">
                    <span>Tickets sold</span>
                    <span>{{ number_format($ev['sold']) }} / {{ number_format($ev['cap']) }}</span>
                </div>
                <div style="height:9px;border-radius:99px;background:var(--chip);overflow:hidden">
                    <div style="height:100%;width:{{ $soldPct }}%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--cyan));transition:width .8s ease"></div>
                </div>
                <p style="margin:12px 0 0;font-size:12px;color:var(--muted);font-weight:600">{{ $urgency }}</p>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
