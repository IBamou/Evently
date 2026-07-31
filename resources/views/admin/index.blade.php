@php
    // ── Defensive defaults — this view also renders bare via /dashboard (no data passed). ──
    $underReview ??= collect();
    $events ??= null;
    $trashed ??= null;
    $stats ??= [];
    $organizers ??= collect();

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

    // Design aTab (L1595): only the active segment's section is rendered. Default "Approvals".
    $aTab = request('tab', 'Approvals');
    $aTab = in_array($aTab, ['Approvals', 'Events', 'Users', 'Reports'], true) ? $aTab : 'Approvals';

    // Reports KPIs — real platform stats when the controller provided them, otherwise design demo numbers.
    $reportCards = isset($stats['total'])
        ? [
            ['label' => 'Total events', 'value' => number_format($stats['total']), 'delta' => 'across the platform', 'deltaFg' => 'var(--muted)'],
            ['label' => 'Published', 'value' => number_format($stats['published']), 'delta' => 'live right now', 'deltaFg' => 'var(--ok)'],
            ['label' => 'Under review', 'value' => number_format($stats['under_review']), 'delta' => 'awaiting moderation', 'deltaFg' => 'var(--warn)'],
            ['label' => 'Categories', 'value' => number_format($stats['categories']), 'delta' => 'configured', 'deltaFg' => 'var(--muted)'],
        ]
        : [
            ['label' => 'Gross volume', 'value' => '4.82M MAD', 'delta' => '+18% MoM', 'deltaFg' => 'var(--ok)'],
            ['label' => 'Active users', 'value' => '12,940', 'delta' => '+640 this month', 'deltaFg' => 'var(--ok)'],
            ['label' => 'Organizers', 'value' => '186', 'delta' => '9 pending KYC', 'deltaFg' => 'var(--warn)'],
            ['label' => 'Refund rate', 'value' => '1.8%', 'delta' => '−0.3% MoM', 'deltaFg' => 'var(--ok)'],
        ];

    // Static demo data, ported 1:1 from design-evently-home.html — no users/order tables exist yet.
    $users = [
        ['name' => 'Yassine Benali', 'initial' => 'YB', 'email' => 'yassine@example.com', 'role' => 'User'],
        ['name' => 'Salma Lahlou', 'initial' => 'SL', 'email' => 'salma@rabatlive.ma', 'role' => 'Organizer'],
        ['name' => 'Imane Tazi', 'initial' => 'IT', 'email' => 'imane@example.com', 'role' => 'User'],
        ['name' => 'Mehdi Alaoui', 'initial' => 'MA', 'email' => 'mehdi@gitex.africa', 'role' => 'Organizer'],
        ['name' => 'Admin Evently', 'initial' => 'AE', 'email' => 'admin@evently.ma', 'role' => 'Admin'],
        ['name' => 'Omar Cherkaoui', 'initial' => 'OC', 'email' => 'omar@example.com', 'role' => 'User'],
    ];

    $cityBars = [
        ['label' => 'Casablanca', 'value' => '14,200 tix', 'pct' => '100%'],
        ['label' => 'Rabat', 'value' => '11,800 tix', 'pct' => '83%'],
        ['label' => 'Marrakech', 'value' => '9,100 tix', 'pct' => '64%'],
        ['label' => 'Tanger', 'value' => '6,400 tix', 'pct' => '45%'],
        ['label' => 'Salé', 'value' => '2,600 tix', 'pct' => '18%'],
    ];
@endphp

<x-app-layout :activeRole="'admin'" :navRole="'admin'" :avatarRole="'admin'" :activeNav="'admin'">

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:6px">
            <h1 style="margin:0;font-size:28px;font-weight:800;letter-spacing:-.9px">Admin console</h1>
            <a href="{{ route('admin.categories.index') }}" style="border:1px solid var(--border);background:var(--surface);cursor:pointer;font-size:12.5px;font-weight:700;padding:10px 15px;border-radius:10px;min-height:40px;text-decoration:none;color:var(--text);display:inline-flex;align-items:center">Manage categories</a>
        </div>
        <p style="margin:0 0 22px;color:var(--muted);font-size:14.5px">Moderate events, manage accounts and keep the platform healthy.</p>

        @if (session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        {{-- Segmented tabs: only the active section renders (design aTab "Approvals" default) --}}
        <div style="display:flex;gap:6px;padding:5px;background:var(--surface);border:1px solid var(--border);border-radius:13px;margin-bottom:20px;width:fit-content;flex-wrap:wrap">
            @foreach(['Approvals', 'Events', 'Users', 'Reports'] as $tab)
                <a href="{{ route('admin.events.index', $tab === 'Approvals' ? [] : ['tab' => $tab]) }}"
                   style="border:0;cursor:pointer;padding:10px 16px;min-height:42px;border-radius:9px;font-size:13px;font-weight:700;background:{{ $tab === $aTab ? 'var(--primary)' : 'transparent' }};color:{{ $tab === $aTab ? '#fff' : 'var(--muted)' }};text-decoration:none;display:inline-flex;align-items:center">{{ $tab }}</a>
            @endforeach
        </div>

        @if($aTab === 'Approvals')
        {{-- Approvals — real events under review --}}
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:26px">
            @forelse($underReview as $event)
                <article style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                    <div style="width:64px;height:64px;border-radius:14px;background:{{ $rowGrad($event) }};flex:0 0 auto"></div>
                    <div style="flex:1;min-width:200px">
                        <div style="font-size:16px;font-weight:700;letter-spacing:-.2px">{{ $event->title }}</div>
                        <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:4px">{{ $event->organizer?->name }} &middot; {{ $event->starts_at?->format('D, j M Y') }} &middot; {{ $event->city }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:11px;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.6px">Format</div>
                        <div style="font-size:15px;font-weight:800">{{ $event->format?->label() }}</div>
                    </div>
                    <span style="padding:6px 12px;border-radius:9px;font-size:11.5px;font-weight:800;text-transform:uppercase;background:rgba(217,119,6,.14);color:var(--warn)">Pending review</span>
                    <div style="display:flex;gap:8px">
                        <form method="POST" action="{{ route('admin.events.reject', $event) }}">
                            @csrf
                            <button type="submit" style="border:1px solid rgba(220,38,38,.35);background:rgba(220,38,38,.07);color:var(--err);cursor:pointer;font-size:13px;font-weight:800;padding:11px 16px;border-radius:11px;min-height:44px">Reject</button>
                        </form>
                        <form method="POST" action="{{ route('admin.events.publish', $event) }}">
                            @csrf
                            <button type="submit" style="border:0;background:var(--ok);color:#fff;cursor:pointer;font-size:13px;font-weight:800;padding:11px 18px;border-radius:11px;min-height:44px">Approve</button>
                        </form>
                    </div>
                </article>
            @empty
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:40px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No events awaiting approval</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">Submitted events will show up here for moderation.</div>
                </div>
            @endforelse
        </div>
        @endif

        @if($aTab === 'Events')
        {{-- All events — real data, with cancel/delete/restore --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;margin-bottom:26px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
                <h2 style="margin:0;font-size:16px;font-weight:800">All events</h2>
                <div style="flex:1"></div>
                <form method="GET" action="{{ route('admin.events.index') }}" style="display:flex;gap:8px;flex-wrap:wrap">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search events&hellip;" aria-label="Search events" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
                    <select name="status" aria-label="Status" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\EventStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <select name="organizer_id" aria-label="Organizer" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
                        <option value="">All organizers</option>
                        @foreach($organizers as $organizer)
                            <option value="{{ $organizer->id }}" @selected(($filters['organizer_id'] ?? '') == $organizer->id)>{{ $organizer->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Filter</button>
                </form>
            </div>

            @if($events && $events->count())
                <div style="display:grid;grid-template-columns:2fr 1fr .8fr 1fr .8fr 1.3fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                    <span>Event</span><span>Date</span><span>Category</span><span>Organizer</span><span>Status</span><span style="text-align:right">Actions</span>
                </div>
                @foreach($events as $event)
                    @php
                        [$badgeBg, $badgeFg] = $badgeFor($event->status->value);
                    @endphp
                    <div style="display:grid;grid-template-columns:2fr 1fr .8fr 1fr .8fr 1.3fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                        <div style="display:flex;align-items:center;gap:11px;min-width:0">
                            <span style="width:34px;height:34px;flex:0 0 auto;border-radius:9px;background:{{ $rowGrad($event) }}"></span>
                            <div style="min-width:0">
                                <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</div>
                                <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $event->city }}</div>
                            </div>
                        </div>
                        <span style="color:var(--muted);font-weight:600">{{ $event->starts_at?->format('D, j M Y') ?? '—' }}</span>
                        <span style="font-weight:600;color:var(--muted)">{{ $event->category?->name }}</span>
                        <span style="font-weight:600;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->organizer?->name }}</span>
                        <span><span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $badgeBg }};color:{{ $badgeFg }}">{{ $event->status->label() }}</span></span>
                        <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
                            @if ($event->status->isUnderReview())
                                <form method="POST" action="{{ route('admin.events.publish', $event) }}">
                                    @csrf
                                    <button type="submit" style="border:0;background:var(--ok);color:#fff;cursor:pointer;font-size:12px;font-weight:800;padding:8px 12px;border-radius:9px;min-height:34px">Publish</button>
                                </form>
                            @endif
                            @if ($event->status->isPublished())
                                <form method="POST" action="{{ route('admin.events.cancel', $event) }}">
                                    @csrf
                                    <button type="submit" style="border:1px solid rgba(220,38,38,.35);background:rgba(220,38,38,.07);color:var(--err);cursor:pointer;font-size:12px;font-weight:800;padding:8px 12px;border-radius:9px;min-height:34px">Cancel</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete" aria-label="Delete" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--err)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
                <div style="margin-top:16px">{{ $events->links() }}</div>
            @elseif($events)
                <div style="padding:32px 20px;text-align:center">
                    <div style="font-size:14px;font-weight:800">No events match these filters</div>
                    <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:4px">Try clearing the search or filters.</div>
                </div>
            @endif
        </div>

        {{-- Trashed events --}}
        @if($trashed && $trashed->count())
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                <h2 style="margin:0 0 16px;font-size:16px;font-weight:800">Recently deleted</h2>
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                    <span>Event</span><span>Organizer</span><span>Status</span><span style="text-align:right">Actions</span>
                </div>
                @foreach($trashed as $event)
                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                        <span style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $event->title }}</span>
                        <span style="color:var(--muted);font-weight:600">{{ $event->organizer?->name }}</span>
                        <span style="color:var(--muted);font-weight:600">{{ $event->status->label() }}</span>
                        <div style="display:flex;justify-content:flex-end">
                            <form method="POST" action="{{ route('admin.events.restore', $event->id) }}">
                                @csrf
                                <button type="submit" style="border:1px solid var(--border);background:var(--surface2);color:var(--primary);cursor:pointer;font-size:12px;font-weight:800;padding:8px 12px;border-radius:9px;min-height:34px">Restore</button>
                            </form>
                        </div>
                    </div>
                @endforeach
                <div style="margin-top:16px">{{ $trashed->links() }}</div>
            </div>
        @endif
        @endif

        @if($aTab === 'Users')
        {{-- Users — static demo (no user-management endpoints yet) --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;margin-bottom:26px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <input type="text" placeholder="Search users by name or email&hellip;" aria-label="Search users" style="flex:1;min-height:44px;padding:12px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                <button type="button" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13.5px;padding:12px 18px;border-radius:11px;min-height:44px">Invite user</button>
            </div>
            <div style="display:grid;grid-template-columns:1.6fr 1.2fr 1fr .9fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span>User</span><span>Email</span><span>Role</span><span>Status</span><span style="text-align:right">Actions</span>
            </div>
            @foreach($users as $u)
                <div style="display:grid;grid-template-columns:1.6fr 1.2fr 1fr .9fr 1fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0">
                        <span style="width:32px;height:32px;flex:0 0 auto;border-radius:50%;background:linear-gradient(135deg,#0EA5E9,#1565D8);color:#fff;display:grid;place-items:center;font-size:11.5px;font-weight:800">{{ $u['initial'] }}</span>
                        <span style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['name'] }}</span>
                    </div>
                    <span style="color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['email'] }}</span>
                    <span><span style="padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:var(--chip);color:var(--primary)">{{ $u['role'] }}</span></span>
                    <span style="font-weight:700;color:var(--ok)">Active</span>
                    <div style="display:flex;gap:8px;justify-content:flex-end">
                        <button type="button" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:12.5px;font-weight:700;padding:9px 14px;border-radius:10px;min-height:38px">Suspend</button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        @if($aTab === 'Reports')
        {{-- Reports --}}
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:18px">
            @foreach($reportCards as $k)
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:17px;padding:18px">
                    <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px">{{ $k['label'] }}</div>
                    <div style="font-size:27px;font-weight:800;letter-spacing:-1px">{{ $k['value'] }}</div>
                    <div style="font-size:12px;font-weight:700;color:{{ $k['deltaFg'] }};margin-top:5px">{{ $k['delta'] }}</div>
                </div>
            @endforeach
        </div>
        {{-- Top cities: design sample data (no ticket tables yet) --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px">
            <h2 style="margin:0 0 18px;font-size:16px;font-weight:800">Top cities by ticket volume</h2>
            <div style="display:flex;flex-direction:column;gap:14px">
                @foreach($cityBars as $b)
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:700;margin-bottom:6px"><span>{{ $b['label'] }}</span><span style="color:var(--muted)">{{ $b['value'] }}</span></div>
                        <div style="height:8px;border-radius:99px;background:var(--chip);overflow:hidden"><div style="height:100%;width:{{ $b['pct'] }};border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--cyan));transition:width .8s ease"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

</x-app-layout>
