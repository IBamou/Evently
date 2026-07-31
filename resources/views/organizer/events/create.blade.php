@php
    // ── Static demo data, ported 1:1 from design-evently-home.html (cstep 1, default form state) ──
    $cats = ['Music', 'Business', 'Tech', 'Art', 'Sports', 'Food & Drinks'];
    $fmts = ['In person', 'Online', 'Hybrid'];
    $cities = ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Chefchaouen', 'Salé'];

    $steps = [
        ['n' => '1', 'label' => 'Basics', 'current' => true],
        ['n' => '2', 'label' => 'Date & venue', 'current' => false],
        ['n' => '3', 'label' => 'Tickets', 'current' => false],
        ['n' => '4', 'label' => 'Review', 'current' => false, 'last' => true],
    ];
@endphp

<x-app-layout :activeRole="'organizer'" :navRole="'organizer'" :avatarRole="'organizer'" :activeNav="'oevents'">

    <div style="max-width:960px;margin:0 auto;padding:30px 26px 60px">
        <a href="/preview/oevents" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to my events</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Create an event</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Submitted events go to an admin for approval before going live.</p>

        {{-- Step indicator: 30px circles + 2px connectors --}}
        <div style="display:flex;gap:0;margin-bottom:24px;align-items:center">
            @foreach($steps as $i => $st)
                <div style="display:flex;align-items:center;gap:10px;{{ !empty($st['last']) ? 'flex:0 0 auto' : 'flex:1' }}">
                    <button type="button" style="display:flex;align-items:center;gap:9px;border:0;background:none;cursor:pointer;padding:0">
                        <span style="width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-size:12.5px;font-weight:800;background:{{ $st['current'] ? 'var(--chip)' : 'transparent' }};color:{{ $st['current'] ? 'var(--primary)' : 'var(--muted)' }};border:1.5px solid {{ $st['current'] ? 'var(--primary)' : 'var(--border)' }}">{{ $st['n'] }}</span>
                        <span style="font-size:13px;font-weight:{{ $st['current'] ? '800' : '600' }};color:{{ $st['current'] ? 'var(--text)' : 'var(--muted)' }};white-space:nowrap">{{ $st['label'] }}</span>
                    </button>
                    @if(empty($st['last']))
                        <div style="flex:1;height:2px;background:var(--border);min-width:12px"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:26px">
            {{-- Step 1 — Basics --}}
            <div style="display:flex;flex-direction:column;gap:16px">
                <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Event title</span><input type="text" placeholder="e.g. Casablanca Jazz Night" style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Description</span><textarea rows="4" placeholder="What should attendees expect?" style="padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none;resize:vertical;line-height:1.6"></textarea></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Category</span>
                        <select style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                            @foreach($cats as $c)<option value="{{ $c }}" @selected($c === 'Music')>{{ $c }}</option>@endforeach
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:7px"><span style="font-size:12.5px;font-weight:700">Format</span>
                        <select style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                            @foreach($fmts as $f)<option value="{{ $f }}" @selected($f === 'In person')>{{ $f }}</option>@endforeach
                        </select>
                    </label>
                </div>
                <div>
                    <div style="font-size:12.5px;font-weight:700;margin-bottom:8px">Cover image</div>
                    <div style="border:1.5px dashed var(--border);border-radius:14px;padding:30px;text-align:center;background:var(--surface2)">
                        <div style="font-size:13.5px;font-weight:700;margin-bottom:5px">Drop an image or click to upload</div>
                        <div style="font-size:12px;color:var(--muted)">JPG or PNG, 1600&times;900 recommended</div>
                    </div>
                </div>
            </div>

            {{-- Footer: Back / Save draft / Continue --}}
            <div style="display:flex;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
                <button type="button" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:48px;opacity:.5">Back</button>
                <div style="flex:1"></div>
                <button type="button" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:48px">Save draft</button>
                <button type="button" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:14px;padding:13px 24px;border-radius:12px;min-height:48px">Continue</button>
            </div>
        </div>
    </div>

</x-app-layout>
