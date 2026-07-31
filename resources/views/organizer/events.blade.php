@php
    // Category cover gradients (mirrors home.blade.php, keyed by category slug).
    $categoryGradients = [
        'music' => 'linear-gradient(135deg,#1565D8,#0EA5E9)',
        'business' => 'linear-gradient(135deg,#D97706,#F59E0B)',
        'tech' => 'linear-gradient(135deg,#7C3AED,#0EA5E9)',
        'art' => 'linear-gradient(135deg,#14B8A6,#0EA5E9)',
        'sports' => 'linear-gradient(135deg,#0EA5E9,#14B8A6)',
        'food-drinks' => 'linear-gradient(135deg,#DC2626,#F59E0B)',
    ];
    $rowGrad = fn ($event) => $categoryGradients[$event->category?->slug] ?? 'linear-gradient(135deg,#1E3A8A,#7C3AED)';

    // Status → [badgeBg, badgeFg]
    $statusBadge = [
        'draft' => ['rgba(91,119,148,.14)', 'var(--muted)'],
        'under_review' => ['rgba(217,119,6,.14)', 'var(--warn)'],
        'published' => ['rgba(22,163,74,.12)', 'var(--ok)'],
        'cancelled' => ['rgba(220,38,38,.12)', 'var(--err)'],
    ];
    $badgeFor = fn ($status) => $statusBadge[$status] ?? ['var(--chip)', 'var(--muted)'];

    // Filter pills: All + one per status. Counts come from the controller ($counts)
    // so the view stays free of queries.
    $activeStatus = $filters['status'] ?? 'all';
    $pills = [['value' => 'all', 'label' => 'All', 'count' => $counts['all'] ?? 0]];
    foreach ($statuses as $value => $label) {
        $pills[] = ['value' => $value, 'label' => $label, 'count' => $counts[$value] ?? 0];
    }
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
            <a href="{{ route('organizer.events.create') }}" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:46px;text-decoration:none;display:inline-flex;align-items:center">+ New event</a>
        </div>

        @if (session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        {{-- Status pills --}}
        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            @foreach($pills as $p)
                @php
                    $active = $activeStatus === $p['value'];
                    $query = array_merge(request()->except(['page', 'status']), $p['value'] === 'all' ? [] : ['status' => $p['value']]);
                @endphp
                <a href="{{ route('organizer.events.index', $query) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $active ? 'var(--primary)' : 'var(--border)' }};background:{{ $active ? 'var(--primary)' : 'var(--surface)' }};color:{{ $active ? '#fff' : 'var(--text)' }};border-radius:11px;cursor:pointer;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center">{{ $p['label'] }} <span style="opacity:.6;margin-left:5px">{{ $p['count'] }}</span></a>
            @endforeach
        </div>

        {{-- Events table --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1.3fr .9fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span>Event</span><span>Date</span><span>Price</span><span>Sold</span><span>Status</span><span style="text-align:right">Actions</span>
            </div>
            @forelse($events as $event)
                @php
                    [$badgeBg, $badgeFg] = $badgeFor($event->status->value);
                    // Per-status actions (contract): edit + submit only for drafts,
                    // view only for published, cancel only for published, delete only for drafts/pending.
                @endphp
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1.3fr .9fr 1fr;gap:12px;padding:14px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div style="display:flex;align-items:center;gap:11px;min-width:0">
                        <span style="width:38px;height:38px;flex:0 0 auto;border-radius:10px;background:{{ $rowGrad($event) }}"></span>
                        <div style="min-width:0">
                            @if ($event->status->isPublished())
                                <a href="{{ route('events.show', $event) }}" style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-decoration:none;color:inherit;display:block">{{ $event->title }}</a>
                            @else
                                <span style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;color:var(--muted)">{{ $event->title }}</span>
                            @endif
                            <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $event->city }}</div>
                        </div>
                    </div>
                    <span style="color:var(--muted);font-weight:600">{{ $event->starts_at?->format('D, j M Y') ?? '—' }}</span>
                    {{-- No ticket/pricing tables exist yet — honest placeholder. --}}
                    <span style="font-weight:700;color:var(--muted)">—</span>
                    <span style="font-size:12px;font-weight:700;color:var(--muted)">—</span>
                    <span><span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $badgeBg }};color:{{ $badgeFg }}">{{ $event->status->label() }}</span></span>
                    <div style="display:flex;gap:6px;justify-content:flex-end">
                        @if ($event->status->isPublished())
                            <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener" title="View" aria-label="View" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--muted);text-decoration:none">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"></path></svg>
                            </a>
                        @endif
                        @if ($event->status->isDraft())
                            <a href="{{ route('organizer.events.edit', $event) }}" title="Edit" aria-label="Edit" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--primary);text-decoration:none">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L20 8l-4-4L4 16z"></path></svg>
                            </a>
                        @endif
                        @if ($event->status->isDraft())
                            <form method="POST" action="{{ route('organizer.events.submit', $event) }}">
                                @csrf
                                <button type="submit" title="Submit for review" aria-label="Submit for review" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--warn)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
                                </button>
                            </form>
                        @endif
                        @if ($event->status->isPublished())
                            <form method="POST" action="{{ route('organizer.events.cancel', $event) }}" onsubmit="return confirm('Cancel this event? Attendees will be notified.')">
                                @csrf
                                <button type="submit" title="Cancel event" aria-label="Cancel event" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--warn)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M5.6 5.6l12.8 12.8"></path></svg>
                                </button>
                            </form>
                        @endif
                        @if ($event->status->isDraft() || $event->status->isUnderReview())
                            <form method="POST" action="{{ route('organizer.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete" aria-label="Delete" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--err)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"></path></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding:44px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No events found</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600;margin-bottom:16px">Events you create will appear here.</div>
                    <a href="{{ route('organizer.events.create') }}" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:13.5px;padding:12px 18px;border-radius:12px;min-height:44px;text-decoration:none;display:inline-flex;align-items:center">+ Create your first event</a>
                </div>
            @endforelse
        </div>

        <div style="margin-top:20px">{{ $events->links() }}</div>
    </div>

</x-app-layout>
