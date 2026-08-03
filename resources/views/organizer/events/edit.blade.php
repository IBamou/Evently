@php
    // City suggestions â€” no cities table exists, so the design's suggestion list is kept.
    $cities = ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Chefchaouen', 'SalÃ©'];

    $statusBadge = [
        'draft' => ['rgba(91,119,148,.14)', 'var(--muted)'],
        'under_review' => ['rgba(217,119,6,.14)', 'var(--warn)'],
        'published' => ['rgba(22,163,74,.12)', 'var(--ok)'],
        'cancelled' => ['rgba(220,38,38,.12)', 'var(--err)'],
    ];
    [$badgeBg, $badgeFg] = $statusBadge[$event->status->value] ?? ['var(--chip)', 'var(--muted)'];
@endphp

<x-app-layout :activeNav="'oevents'">

    <div style="max-width:960px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('organizer.events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to my events</a>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px">
            <h1 style="margin:0;font-size:28px;font-weight:800;letter-spacing:-.9px">Edit event</h1>
            <span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $badgeBg }};color:{{ $badgeFg }}">{{ $event->status->label() }}</span>
            <div style="flex:1"></div>
            <a href="{{ route('organizer.ticket-types.index', $event) }}" style="border:1px solid var(--border);background:var(--surface);cursor:pointer;font-size:12.5px;font-weight:700;padding:10px 15px;border-radius:10px;min-height:40px;text-decoration:none;color:var(--text);display:inline-flex;align-items:center">Ticket types</a>
        </div>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">{{ $event->title }}</p>

        @if (session('success'))
            <div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom:20px;padding:13px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:600">
                <strong>Please fix the following:</strong>
                <ul style="margin:6px 0 0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li style="margin-bottom:2px">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('organizer.events.update', $event) }}" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:26px">
            @csrf
            @method('PATCH')

            {{-- Basics --}}
            <div style="display:flex;flex-direction:column;gap:16px">
                <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Event title</span><input type="text" name="title" value="{{ old('title', $event->title) }}" placeholder="e.g. Casablanca Jazz Night" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Description</span><textarea name="description" rows="4" placeholder="What should attendees expect?" required style="padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none;resize:vertical;line-height:1.6">{{ old('description', $event->description) }}</textarea></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Category</span>
                        <select name="category_id" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $event->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Format</span>
                        <select name="format" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                            @foreach(\App\Enums\EventFormat::cases() as $format)
                                <option value="{{ $format->value }}" @selected(old('format', $event->format?->value) == $format->value)>{{ $format->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                {{-- Date & venue --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Venue / location</span><input type="text" name="location" value="{{ old('location', $event->location) }}" placeholder="e.g. Complexe Mohammed V" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">City</span>
                        <select name="city" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                            @foreach($cities as $city)
                                <option value="{{ $city }}" @selected(old('city', $event->city) === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Starts at</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Ends at</span><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                </div>

                <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Cover image URL <span style="color:var(--muted);font-weight:600">(optional)</span></span><input type="url" name="banner_url" value="{{ old('banner_url', $event->banner_url) }}" placeholder="https://example.com/cover.jpg" style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
            </div>

            {{-- Footer: Cancel / Save --}}
            <div style="display:flex;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
                <a href="{{ route('organizer.events.index') }}" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:48px;text-decoration:none;color:inherit;display:inline-flex;align-items:center">Cancel</a>
                <div style="flex:1"></div>
                <button type="submit" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:14px;padding:13px 24px;border-radius:12px;min-height:48px">Save changes</button>
            </div>
        </form>
    </div>

</x-app-layout>
