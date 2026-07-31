@php
    // ── Static demo data, ported 1:1 from design-evently-home.html (admin role, aTab "Approvals") ──
    $pending = [
        ['title' => 'Atlas Techno Night', 'org' => 'Atlantic Nights', 'date' => 'Fri, 4 Jul 2026', 'city' => 'Casablanca', 'cap' => '1,200', 'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)'],
        ['title' => 'Fintech Founders Breakfast', 'org' => 'Casa Devs', 'date' => 'Tue, 8 Jul 2026', 'city' => 'Casablanca', 'cap' => '120', 'grad' => 'linear-gradient(135deg,#0C4A6E,#0EA5E9)'],
        ['title' => 'Agadir Surf Open', 'org' => 'Surf Maroc', 'date' => 'Sat, 12 Jul 2026', 'city' => 'Agadir', 'cap' => '3,000', 'grad' => 'linear-gradient(135deg,#064E3B,#22C55E)'],
        ['title' => 'Tagine Masterclass', 'org' => 'Casa Events', 'date' => 'Sun, 20 Jul 2026', 'city' => 'Marrakech', 'cap' => '60', 'grad' => 'linear-gradient(135deg,#7C2D12,#F59E0B)'],
    ];

    $users = [
        ['name' => 'Yassine Benali', 'initial' => 'YB', 'email' => 'yassine@example.com', 'role' => 'User'],
        ['name' => 'Salma Lahlou', 'initial' => 'SL', 'email' => 'salma@rabatlive.ma', 'role' => 'Organizer'],
        ['name' => 'Imane Tazi', 'initial' => 'IT', 'email' => 'imane@example.com', 'role' => 'User'],
        ['name' => 'Mehdi Alaoui', 'initial' => 'MA', 'email' => 'mehdi@gitex.africa', 'role' => 'Organizer'],
        ['name' => 'Admin Evently', 'initial' => 'AE', 'email' => 'admin@evently.ma', 'role' => 'Admin'],
        ['name' => 'Omar Cherkaoui', 'initial' => 'OC', 'email' => 'omar@example.com', 'role' => 'User'],
    ];

    $kpis = [
        ['label' => 'Gross volume', 'value' => '4.82M MAD', 'delta' => '+18% MoM', 'deltaFg' => 'var(--ok)'],
        ['label' => 'Active users', 'value' => '12,940', 'delta' => '+640 this month', 'deltaFg' => 'var(--ok)'],
        ['label' => 'Organizers', 'value' => '186', 'delta' => '9 pending KYC', 'deltaFg' => 'var(--warn)'],
        ['label' => 'Refund rate', 'value' => '1.8%', 'delta' => '&#8722;0.3% MoM', 'deltaFg' => 'var(--ok)'],
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

    @php
        // Design aTab (L1595): only the active segment's section is rendered. Default "Approvals".
        $aTab = request('tab', 'Approvals');
        $aTab = in_array($aTab, ['Approvals', 'Users', 'Reports'], true) ? $aTab : 'Approvals';
    @endphp

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Admin console</h1>
        <p style="margin:0 0 22px;color:var(--muted);font-size:14.5px">Moderate events, manage accounts and keep the platform healthy.</p>

        {{-- Segmented tabs: only the active section renders (design aTab "Approvals" default) --}}
        <div style="display:flex;gap:6px;padding:5px;background:var(--surface);border:1px solid var(--border);border-radius:13px;margin-bottom:20px;width:fit-content;flex-wrap:wrap">
            @foreach(['Approvals', 'Users', 'Reports'] as $tab)
                <a href="{{ $tab === 'Approvals' ? '/preview/admin' : '/preview/admin?tab=' . $tab }}"
                   style="border:0;cursor:pointer;padding:10px 16px;min-height:42px;border-radius:9px;font-size:13px;font-weight:700;background:{{ $tab === $aTab ? 'var(--primary)' : 'transparent' }};color:{{ $tab === $aTab ? '#fff' : 'var(--muted)' }};text-decoration:none;display:inline-flex;align-items:center">{{ $tab }}</a>
            @endforeach
        </div>

        @if($aTab === 'Approvals')
        {{-- Approvals --}}
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:26px">
            @foreach($pending as $p)
                <article style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                    <div style="width:64px;height:64px;border-radius:14px;background:{{ $p['grad'] }};flex:0 0 auto"></div>
                    <div style="flex:1;min-width:200px">
                        <div style="font-size:16px;font-weight:700;letter-spacing:-.2px">{{ $p['title'] }}</div>
                        <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:4px">{{ $p['org'] }} &middot; {{ $p['date'] }} &middot; {{ $p['city'] }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:11px;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.6px">Capacity</div>
                        <div style="font-size:15px;font-weight:800">{{ $p['cap'] }}</div>
                    </div>
                    <span style="padding:6px 12px;border-radius:9px;font-size:11.5px;font-weight:800;text-transform:uppercase;background:rgba(217,119,6,.14);color:var(--warn)">Pending review</span>
                    <div style="display:flex;gap:8px">
                        <button type="button" style="border:1px solid rgba(220,38,38,.35);background:rgba(220,38,38,.07);color:var(--err);cursor:pointer;font-size:13px;font-weight:800;padding:11px 16px;border-radius:11px;min-height:44px">Reject</button>
                        <button type="button" style="border:0;background:var(--ok);color:#fff;cursor:pointer;font-size:13px;font-weight:800;padding:11px 18px;border-radius:11px;min-height:44px">Approve</button>
                    </div>
                </article>
            @endforeach
        </div>

        @endif

        @if($aTab === 'Users')
        {{-- Users --}}
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
            @foreach($kpis as $k)
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:17px;padding:18px">
                    <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px">{{ $k['label'] }}</div>
                    <div style="font-size:27px;font-weight:800;letter-spacing:-1px">{{ $k['value'] }}</div>
                    <div style="font-size:12px;font-weight:700;color:{{ $k['deltaFg'] }};margin-top:5px">{!! $k['delta'] !!}</div>
                </div>
            @endforeach
        </div>
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
