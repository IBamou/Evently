@use('App\Helpers\Helper')
@php
    // Event picker for check-in: one card per event with live door stats.
    // Contract: $events is a Collection of ['event' => Event, 'stats' => ['checked_in','issued','remaining']].
    $events ??= collect();

    // Status â†’ [badgeBg, badgeFg] (canonical design pairs).
    $pickStatusMap = [
        'published' => ['rgba(22,163,74,.12)', 'var(--ok)'],
        'under_review' => ['rgba(217,119,6,.14)', 'var(--warn)'],
        'draft' => ['rgba(91,119,148,.16)', 'var(--muted)'],
        'cancelled' => ['rgba(220,38,38,.12)', 'var(--err)'],
    ];
    $pickBadge = fn ($value) => $pickStatusMap[$value] ?? ['rgba(91,119,148,.16)', 'var(--muted)'];
@endphp

<x-app-layout :activeNav="'scan'">

    <div style="max-width:1100px;margin:0 auto;padding:30px 26px 60px">
        <h1 style="font-size:28px;font-weight:800;letter-spacing:-.9px;margin:0 0 6px">Door check-in</h1>
        <p style="font-size:14.5px;color:var(--muted);margin:0 0 24px">Choose the event you're operating tonight.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            @forelse($events as $entry)
                @php
                    $event = $entry['event'];
                    $stats = $entry['stats'] ?? ['checked_in' => 0, 'issued' => 0, 'remaining' => 0];
                    $statusValue = $event->status->value;
                    [$badgeBg, $badgeFg] = $pickBadge($statusValue);
                    $isCancelled = $event->status->isCancelled();
                    $isPublished = $event->status->isPublished();
                @endphp
                <article style="background:var(--surface);border:1px solid var(--border);border-radius:18px;overflow:hidden;display:flex;flex-direction:column">
                    {{-- Header band: category gradient + status badge --}}
                    <div style="background:{{ Helper::categoryGradient($event->category?->slug) ?? 'linear-gradient(135deg,var(--primary),var(--cyan))' }};color:#fff;padding:16px 18px">
                        <div style="display:flex;align-items:flex-start;gap:10px">
                            <div style="flex:1;min-width:0">
                                <div style="font-size:16px;font-weight:800;letter-spacing:-.3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</div>
                                <div style="font-size:12.5px;font-weight:600;opacity:.85;margin-top:2px">{{ $event->starts_at?->format('D, j M Y') ?? 'TBA' }} &middot; {{ $event->city }}</div>
                            </div>
                            <span style="flex:0 0 auto;padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $badgeBg }};color:{{ $badgeFg }}">{{ $event->status->label() }}</span>
                        </div>
                    </div>

                    {{-- Door stats + CTA --}}
                    <div style="padding:18px;display:flex;flex-direction:column;flex:1">
                        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
                            <div style="display:flex;align-items:baseline;gap:10px">
                                <span style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $stats['checked_in'] }}</span>
                                <span style="font-size:12.5px;font-weight:700;color:var(--muted)">Checked in</span>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:10px">
                                <span style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $stats['issued'] }}</span>
                                <span style="font-size:12.5px;font-weight:700;color:var(--muted)">Issued</span>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:10px">
                                <span style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $stats['remaining'] }}</span>
                                <span style="font-size:12.5px;font-weight:700;color:var(--muted)">Remaining</span>
                            </div>
                        </div>
                        @if($isCancelled)
                            <div style="font-size:13px;font-weight:700;color:var(--err);text-align:center;padding:14px 0">This event is cancelled</div>
                        @elseif($isPublished)
                            <a href="{{ route('organizer.check-in.index', $event) }}" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:14px;min-height:46px;border-radius:12px;text-decoration:none;display:flex;align-items:center;justify-content:center">Open check-in</a>
                        @else
                            <div style="font-size:13px;font-weight:700;color:var(--muted);text-align:center;padding:14px 0">Only published events can be checked in</div>
                        @endif
                    </div>
                </article>
            @empty
                <div style="grid-column:1/-1;border:2px dashed var(--border);border-radius:18px;padding:60px 26px;text-align:center;background:var(--surface)">
                    <div style="font-size:17px;font-weight:800;margin-bottom:6px">No events yet</div>
                    <div style="font-size:14px;color:var(--muted);margin-bottom:16px">Create an event to start checking in tickets.</div>
                    <a href="{{ route('organizer.events.create') }}" style="display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:12px;font-size:13.5px;font-weight:700;text-decoration:none;min-height:44px">+ New event</a>
                </div>
            @endforelse
        </div>
    </div>

</x-app-layout>
