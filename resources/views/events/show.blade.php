{{-- Event detail — pixel-port of design rDetail (lines 503–598).
     Real data: $event (published, organizer + category loaded). No ticket/pricing
     tables exist yet, so the booking widget is a neutral "coming soon" card. --}}
@php
    $role = request('role', 'guest');
    $role = in_array($role, ['guest', 'user', 'organizer', 'admin'], true) ? $role : 'guest';
@endphp

<x-app-layout :activeRole="$role" :navRole="$role" :avatarRole="$role">
@php
    // Category slug → hero/thumb gradient (same map as home).
    $categoryGradients = [
        'music' => 'linear-gradient(135deg,#1565D8,#0EA5E9)',
        'business' => 'linear-gradient(135deg,#D97706,#F59E0B)',
        'tech' => 'linear-gradient(135deg,#7C3AED,#0EA5E9)',
        'art' => 'linear-gradient(135deg,#14B8A6,#0EA5E9)',
        'sports' => 'linear-gradient(135deg,#0EA5E9,#14B8A6)',
        'food-drinks' => 'linear-gradient(135deg,#DC2626,#F59E0B)',
    ];
    $heroBg = $event->banner_url
        ? "url('" . e($event->banner_url) . "') center/cover"
        : ($categoryGradients[$event->category?->slug] ?? 'linear-gradient(135deg,#1E3A8A,#7C3AED)');

    $dateLong = $event->starts_at?->format('D, M j, Y · g:i A') ?: '';
    $isEnded = ! $event->isUpcoming();
    $badgeLabel = $isEnded ? 'Ended' : 'On sale';
    $badgeBg = $isEnded ? '#334155' : 'var(--ok)';

    $roleSuffix = $role !== 'guest' ? '?role=' . $role : '';
@endphp

<div style="max-width:1380px;margin:0 auto;padding:22px 26px 60px">
    {{-- Back link --}}
    <a href="{{ route('events.index') . $roleSuffix }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:12px;display:inline-block;text-decoration:none">&larr; Back to events</a>

    {{-- Hero image/art area --}}
    <div style="position:relative;height:320px;border-radius:20px;overflow:hidden;background:{{ $heroBg }};display:flex;align-items:flex-end;padding:28px">
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(4,20,40,0) 30%,rgba(4,20,40,.78))"></div>
        <div style="position:relative;display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <span style="padding:6px 11px;border-radius:8px;background:rgba(255,255,255,.92);color:#0B2545;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $event->category?->name ?? 'Event' }}</span>
                <span style="padding:6px 11px;border-radius:8px;background:{{ $badgeBg }};color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $badgeLabel }}</span>
            </div>
            <h1 style="margin:0;color:#fff;font-size:38px;font-weight:800;letter-spacing:-1.2px;max-width:22ch;line-height:1.08">{{ $event->title }}</h1>
            <div style="display:flex;gap:18px;flex-wrap:wrap;color:rgba(255,255,255,.9);font-size:14px;font-weight:600">
                <span>{{ $dateLong }}</span>
                <span>{{ $event->location }}, {{ $event->city }}</span>
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
                <p style="margin:0;color:var(--muted);font-size:14.5px;line-height:1.75;text-wrap:pretty">{{ $event->description }}</p>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:22px">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Date & time</div>
                        <div style="font-size:14px;font-weight:700">{{ $dateLong }}</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Format</div>
                        <div style="font-size:14px;font-weight:700">{{ $event->format?->label() ?? 'In person' }}</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Category</div>
                        <div style="font-size:14px;font-weight:700">{{ $event->category?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Organizer card --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--cyan));color:#fff;display:grid;place-items:center;font-weight:800">{{ mb_strtoupper(mb_substr($event->organizer?->name ?? 'E', 0, 1)) }}</div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px">Organizer</div>
                        <div style="font-size:15px;font-weight:700">{{ $event->organizer?->name ?? 'Evently' }}</div>
                    </div>
                    <div style="flex:1"></div>
                    <button type="button" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:13px;font-weight:700;padding:11px 16px;border-radius:11px;min-height:44px">Share</button>
                </div>
            </div>

            {{-- You may also like --}}
            @if ($related->isNotEmpty())
                <div>
                    <h2 style="margin:0 0 14px;font-size:18px;font-weight:800;letter-spacing:-.4px">You may also like</h2>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        @foreach ($related as $e)
                            <a href="{{ route('events.show', $e) }}" style="border:1px solid var(--border);border-radius:15px;overflow:hidden;background:var(--surface);cursor:pointer;display:block;text-decoration:none;color:inherit" class="ev-card">
                                <div style="height:96px;background:{{ $categoryGradients[$e->category?->slug] ?? 'linear-gradient(135deg,var(--primary),var(--cyan))' }}"></div>
                                <div style="padding:13px">
                                    <div style="font-size:14px;font-weight:700;margin-bottom:6px">{{ $e->title }}</div>
                                    <div style="font-size:12px;color:var(--muted);font-weight:600">{{ $e->starts_at?->format('D, j M') }} · {{ $e->city }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right aside (sticky) --}}
        <aside style="position:sticky;top:82px;display:flex;flex-direction:column;gap:16px">

            {{-- Booking card — ticketing tables don't exist yet, so this stays neutral --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:var(--shadow)">
                <div style="font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:14px">Tickets & booking</div>
                <div style="display:flex;flex-direction:column;gap:10px;border:1px dashed var(--border);border-radius:14px;padding:18px;text-align:center">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.8" stroke-linejoin="round" style="margin:0 auto">
                        <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-6z"></path>
                    </svg>
                    <div style="font-size:14px;font-weight:800;color:var(--text)">Booking is coming soon</div>
                    <div style="font-size:12.5px;color:var(--muted);font-weight:600;line-height:1.55">Tickets and check-in for this event will be available here shortly.</div>
                </div>
                <button type="button" disabled style="width:100%;margin-top:16px;border:0;cursor:not-allowed;background:var(--muted);color:#fff;font-weight:800;font-size:15px;padding:15px;border-radius:13px;min-height:52px;opacity:.65">Tickets coming soon</button>
                <p style="margin:10px 0 0;font-size:11.5px;color:var(--muted);text-align:center">Secure payment via Stripe · instant QR ticket</p>
            </div>

            {{-- Event details card (real dates/venue) --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:12px">
                    <span>Event details</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:11px">
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Starts</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->starts_at?->format('D, M j, Y · g:i A') }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Ends</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->ends_at?->format('D, M j, Y · g:i A') }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Venue</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->location }}, {{ $event->city }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Format</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->format?->label() ?? 'In person' }}</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
