{{-- Ticket types management — grid-list language from admin/categories (11px/800/uppercase header,
     padding:13px 4px rows, chip badges, surface card chrome). --}}
<x-app-layout :activeNav="'oevents'">
    <main style="max-width:1100px;margin:0 auto;padding:32px 26px 60px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:6px">
            <div>
                <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Ticket Types — {{ $event->title }}</h1>
                <p style="margin:0;color:var(--muted);font-size:14.5px">Manage pricing and capacity for your event.</p>
            </div>
            <a href="{{ route('organizer.ticket-types.create', $event) }}" style="display:inline-flex;align-items:center;justify-content:center;padding:13px 20px;min-height:46px;box-sizing:border-box;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;white-space:nowrap">+ New ticket type</a>
        </div>

        @if (session('success'))
            <div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        <div role="table" aria-label="Ticket types" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div role="row" style="display:grid;grid-template-columns:1.6fr .7fr .6fr .8fr .7fr 1.4fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span role="columnheader">Name</span><span role="columnheader">Price</span><span role="columnheader">Quantity</span><span role="columnheader">Min / Max</span><span role="columnheader">Status</span><span role="columnheader" style="text-align:right">Actions</span>
            </div>
            @forelse($ticketTypes as $tt)
                <div role="row" style="display:grid;grid-template-columns:1.6fr .7fr .6fr .8fr .7fr 1.4fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $tt->name }}</div>
                        @if($tt->description)
                            <div style="font-size:11.5px;color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $tt->description }}</div>
                        @endif
                    </div>
                    <span role="cell" style="font-weight:700">{{ $tt->price > 0 ? number_format($tt->price, 0).' '.$tt->currency : 'Free' }}</span>
                    <div role="cell">
                        <div style="font-weight:700">{{ $tt->quantity }}</div>
                        <div style="margin-top:3px"><span style="padding:3px 9px;border-radius:8px;font-size:10.5px;font-weight:800;background:var(--chip);color:var(--primary)">{{ $tt->availableQuantity() }} left</span></div>
                    </div>
                    <span role="cell" style="color:var(--muted);font-weight:600">{{ $tt->min_per_booking }} / {{ $tt->max_per_booking }}</span>
                    <span role="cell">
                        @if($tt->is_active)
                            <span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;background:var(--chip);color:var(--primary)">Active</span>
                        @else
                            <span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;background:var(--surface2);color:var(--muted)">Inactive</span>
                        @endif
                    </span>
                    <div role="cell" style="display:flex;gap:6px;justify-content:flex-end;align-items:center;flex-wrap:wrap">
                        <a href="{{ route('organizer.ticket-types.edit', [$event, $tt]) }}" style="padding:7px 12px;border:1px solid var(--border);border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;color:var(--primary);background:var(--surface2);white-space:nowrap">Edit</a>
                        @if($tt->is_active)
                            <form action="{{ route('organizer.ticket-types.deactivate', [$event, $tt]) }}" method="POST" style="display:inline">@csrf
                                <button type="submit" style="padding:7px 12px;border:1px solid var(--border);border-radius:9px;font-size:12px;font-weight:700;background:var(--surface2);cursor:pointer;color:var(--muted);white-space:nowrap">Deactivate</button>
                            </form>
                        @else
                            <form action="{{ route('organizer.ticket-types.activate', [$event, $tt]) }}" method="POST" style="display:inline">@csrf
                                <button type="submit" style="padding:7px 12px;border:1px solid var(--border);border-radius:9px;font-size:12px;font-weight:700;background:var(--surface2);cursor:pointer;color:var(--ok);white-space:nowrap">Activate</button>
                            </form>
                        @endif
                        @unless($tt->bookingItems()->exists())
                            <form action="{{ route('organizer.ticket-types.destroy', [$event, $tt]) }}" method="POST" style="display:inline" x-on:submit.prevent="$dispatch('confirm-ask', { form: $event.target, title: 'Delete this ticket type?', message: 'This permanently deletes this ticket type. This action cannot be undone.', confirmLabel: 'Delete' })">@csrf @method('DELETE')
                                <button type="submit" style="padding:7px 12px;border:1px solid rgba(220,38,38,.25);border-radius:9px;font-size:12px;font-weight:700;background:rgba(220,38,38,.07);cursor:pointer;color:var(--err);white-space:nowrap">Delete</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div style="padding:36px 20px;text-align:center">
                    <div style="font-size:14px;font-weight:800">No ticket types yet</div>
                    <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:4px">Create your first ticket type for this event.</div>
                </div>
            @endforelse
        </div>
    </main>
</x-app-layout>
