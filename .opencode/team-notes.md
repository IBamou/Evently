# Team Blackboard

Shared thinking space for the 3-agent team: **build** (orchestrator), **mimo**, **big-pickle**.

Rules:
- Read this file before starting any task.
- Append your updates under the matching section — never rewrite others' entries.
- Keep entries short: one or two lines each.

## Current Goal

**Port the user's design (design-evently-home.html, project root) into Blade — EXACT same views, sizes, spacing, colors.** User's proof-of-value request: Blade+Tailwind can replicate the design pixel-perfect.

**USER DIRECTIVE (latest):** UI ONLY — no DB, no migrations, no models, no routes/logic. All views come 1:1 from the design file. Static demo data inside views is fine (design's own sample data: BK-4C19A7, Yassine Benali, etc.). build will add minimal static preview routes at merge time so pages are viewable.

**ROLE SYSTEM:** Design's top banners = role previews (guest/user/organizer/admin). The design NEVER shows a sidebar (sidebarOn:false, navGroups:[]) — role differences are in the HEADER top-nav:
- guest: Events · Sign in · Create account (start view: events)
- user: Events · My bookings · My tickets · Profile (start: events)
- organizer: Dashboard · My events · Check-in · Browse · Profile (start: odash)
- admin: Admin · Dashboard · Check-in · Browse · Profile (start: admin)
- Avatars: guest G gray #5B7794→#8FAAC6 · user YB blue #0EA5E9→#1565D8 · organizer SL purple #7C3AED→#1565D8 · admin AD red #DC2626→#F59E0B
- Keep the role-tabs preview strip (like the design) so user can click through roles' views.
- Page headings: 28px/800/ls-.9px h1. Auth pages: 2-col (form max-w 400, h1 30px/800 + decorative right panel).

**WORKFLOW RULE (user mandate):** The 3 of us work TOGETHER — teammates don't just implement. Every round: ALL teammates ANALYZE first, share ideas/considerations on the blackboard, THEN implement informed by the discussion. build merges ideas into decisions.

Round 5 (implementation, UI only): mimo = guest+user views; big-pickle = layout overhaul + organizer/admin views.

## Ideas

Evently project base restored after miscommunication cleanup (build, 2026-07-31).
**User's intent:** keep the project setup, remove only the migrations & feature implementations.

## Ideas

_Team members append ideas here._

### mimo — Round 2 Analysis (layout + home fidelity audit)

#### 1. Fidelity Mismatches (prioritized)

**P1 — Sidebar width WRONG**: Design uses 224px expanded / 72px collapsed (JS line 1568). Mimo's layout uses 240px / 64px. Both expanded AND collapsed widths differ. Fix in app.blade.php.

**P2 — Header background broken in dark mode**: Layout hardcodes `rgba(244,249,255,.82)` (blue-tinted light). Design computes `s.dark ? "rgba(14,33,56,.85)" : "rgba(255,255,255,.88)"` — no blue tint, slightly more opaque. In dark mode, layout header is a milky light overlay. Need `--header-bg` CSS var in both :root and html[data-theme="dark"].

**P3 — Missing header top-nav bar**: Design has a horizontal nav (Events | My bookings | My tickets | Profile) between logo and spacer (lines 275-281), styled with bottom-border on active tab. Layout has just logo → spacer → right controls. This is a significant visual gap — the header feels empty.

**P4 — "Spots left" text NOT in design**: big-pickle added `spotsLabel` line on featured cards (line 214) and all-events cards (line 297). Design cards show only: title → date·city/venue → price. This is an additive deviation. Recommend removing OR marking as intentional enhancement.

**P5 — Featured card title margin**: Design h3 has `margin:0 0 9px`, date line has `margin-bottom:10px`. Our h3 has `margin:0 0 9px` ✓, but date line has `margin-bottom:6px` (home line 211) vs design's 10px. 4px spacing diff on featured cards.

**P6 — Card hover style in inline `<style>`**: home.blade.php defines `.ev-card` hover in a `<style>` block (lines 4-7). This is fragile — should be in app.css. Also risks specificity conflicts with Tailwind utilities if used later.

**P7 — `::selection` colors hardcoded light-mode-only**: app.css line 64 uses `background:#BFDCFF; color:#0B2545` — won't adapt to dark mode. Design has same issue, so it's "faithful" but worth a dark-mode override for polish.

**P8 — Newsletter placement differs from design**: Design puts newsletter INSIDE `<main>` (only on home page). Layout puts it OUTSIDE `<main>` (appears on every page). Minor for now, but event detail / bookings pages will show newsletter too.

#### 2. Integration Risks

**Slot/wrapper**: ✅ No conflict. Layout provides `<main>{{ $slot }}</main>` with no padding, home injects sections starting flush. Matches design.

**Duplicate sidebar/header**: ✅ None detected. Clean separation.

**CSS specificity chain**: Tailwind Preflight (`@tailwind base`) → app.css custom resets → Tailwind utilities → inline styles (highest). This order is correct. Inline styles in both layout and home always win. `@tailwindcss/forms` plugin applies lower-specificity base styles that get overridden by our inline inputs. ✅

**Dark mode interplay**: ⚠️ Three issues:
  - Header background hardcoded (see P2 above)
  - Alpine `x-data` sets `document.documentElement.dataset.theme` AFTER body renders → FOUC (flash of wrong theme). Need a blocking `<script>` in `<head>` to prevent this.
  - `::selection` won't adapt (see P7)

**`@tailwindcss/forms`**: The plugin applies borders/bg to inputs. Our inline styles override with `border:0;background:none`. ✅ No conflict. But our `input:focus-visible` rule (app.css line 59) uses `outline: 2px solid var(--primary)` which competes with forms plugin's focus ring. Our CSS loads after `@tailwind base` so it wins. ✅

**Sidebar state shared between layout and home**: Layout manages sidebar collapse via Alpine `x-data`. Home doesn't need sidebar state — clean. ✅

#### 3. UX/Technical Ideas

**Idea 1 — FOUC prevention**: Add a tiny blocking `<script>` in `<head>` of app.blade.php:
```html
<script>
  (function(){var t=localStorage.getItem('theme');if(t==='dark')document.documentElement.dataset.theme='dark';})()
</script>
```
This runs before body renders, preventing flash of light mode when user prefers dark. ~50 bytes, zero dependencies.

**Idea 2 — Clean Livewire/Alpine split for interactivity**:
| Feature | Layer | Why |
|---|---|---|
| Theme toggle | Alpine | localStorage + DOM only, no server |
| Sidebar collapse | Alpine | Width toggle, no server |
| Grid/list toggle | Alpine | Client-side display change |
| Search input | Livewire | Server-side query, wire:model.live.debounce.300ms |
| Category filters | Livewire | Server-side filtering, wire:model |
| Price range slider | Alpine+Livewire | Alpine throttles UI, Livewire sends final value |
| Sort dropdown | Livewire | Server-side re-query |
| Pagination | Livewire | wire:click, URL state |
| Favorite heart | Livewire | Server state + optimistic UI via Alpine |
| Newsletter subscribe | Livewire | Server-side validation + email storage |

**Idea 3 — Accessibility fixes worth making now**:
- Filter "checkboxes" are `<button>` elements — add `role="checkbox"` + `aria-checked` (or use real `<input type="checkbox">` with `sr-only` label)
- Event cards are `<article>` with hover but no link/keyboard nav — wrap in `<a>` or add `tabindex="0"` + `role="link"` + keyboard handler
- Favorite buttons need `aria-pressed="true/false"`
- Design's `--muted` (#5B7794) on `--bg` (#F4F9FF) = ~3.4:1 contrast ratio — fails WCAG AA for normal text. This is a design-level issue, not implementation. Flag to user but keep as-is to match design.

**Idea 4 — Move `.ev-card` to app.css**: The hover transition in home.blade.php's `<style>` block should move to app.css under a `.ev-card` utility. Cleaner, composable, no inline `<style>` in partials.

**Idea 5 — Responsive breakpoints (future)**: Neither design nor current port has responsive handling. At minimum need: sidebar as overlay drawer on mobile (<768px), hero grid 2→1 col, featured 4→2→1, events grid 3→2→1. Not blocking for pixel-perfect proof but essential for production.

#### 4. Recommendations for Round 3

**mimo's files to change**:
1. `app.blade.php` — Fix sidebar width to 224px/72px (design values)
2. `app.blade.php` — Add `--header-bg` CSS variable usage instead of hardcoded rgba
3. `app.blade.php` — Add header top-nav bar (horizontal nav tabs between logo and spacer)
4. `app.blade.php` — Add FOUC prevention script in `<head>`
5. `app.blade.php` — Make sidebar conditional (hidden for guest, shown for logged-in users) — or decide to always show
6. `app.css` — Add `--header-bg` token to :root and dark mode
7. `app.css` — Move `.ev-card` hover from home.blade.php inline style

**big-pickle's files to change**:
1. `home.blade.php` — Remove "spots left" line from featured + all-events cards (not in design)
2. `home.blade.php` — Fix featured card date-line margin-bottom from 6px to 10px
3. `home.blade.php` — Remove inline `<style>` block (hover moves to app.css)
4. Future: Add `role="checkbox"` + `aria-checked` to filter buttons for accessibility

### big-pickle — Round 1 addendum (verified facts + distinct findings)

**⚠️ CRITICAL (mimo's audit says "Slot/wrapper ✅ No conflict" — I VERIFIED this is wrong):**
- `Route::view('/', 'home')` renders `home.blade.php` DIRECTLY, bypassing the layout entirely. curl of http://evently.test returns a bare fragment: no `<!DOCTYPE>`, no `<head>`, no Vite CSS (page is completely unstyled), no sidebar/header/newsletter/footer. The whole shell is missing. The `{{ $slot }}` layout only activates if home is rendered through it.
- Verified via `Blade::render('<x-app-layout>…')` probe: Laravel 12 resolves `x-app-layout` → `layouts/app.blade.php` fine (anonymous-component fallback to layouts/). So Round 3 fix = wrap home content in `<x-app-layout>` (dashboard.blade.php already does this). No new component file needed.
- Same probe output shows Livewire's injected `<style>` already includes `[x-cloak]{display:none}` → x-cloak is fine, no CSS needed (contradicts any earlier worry).
- Mojibake I saw in raw response (4.9★ → `4.9�~.`) is ONLY because the layout's `<meta charset="utf-8">` is absent — self-resolves once wired. Not a source-file problem.

**Fidelity deltas mimo didn't list:**
- F6: Sidebar collapse button renders `Collapse`/`Expand` text even when collapsed → clipped label at 64px (nav has overflow:hidden). Wrap label in `x-show="expanded"` + show chevron icon.
- F7: Header missing role-preview tabs (design `roleTabs`, lines 285-290). Recommend deliberate DROP for public home (prototype affordance) — needs decision.
- F8: Tickets badge hardcoded "3"; design shows badge only `if hasCart` (line 296-298).
- F9: Hero search `<input type="search">` → webkit native clear (×) on hover shifts layout; use `type="text"` (design input has no type).
- On sidebar width: HTML only has `{{ sidebarW }}` placeholder — I can't verify 224/72 from the HTML; trusting mimo's JS reading (line 1568). Flag for user confirmation with the original screenshot.

**Integration risks (additional):**
- `dashboard.blade.php` passes `<x-slot name="header">` — new layout has no `@isset($header)` block → silently dropped. Content still renders. Tidy in a later round.
- z-index chain verified safe: header z-40 > featured z-2 > hero waves; filter sidebar sticky top:82px = header 66 + 16, matches design. No sidebar/hero collision (sidebar has no z-index but never overlaps the main column).
- No duplication: design intentionally has logo in sidebar AND header — layout matches. Home doesn't duplicate search/buttons from header. Keyframes in app.css match design 1:1 (checked).
- `--chip` dark token `#12294552` (8-digit hex w/ alpha) matches design exactly — keep, don't "fix".

**UX ideas (complementing mimo's):**
- Livewire state map = design bindings 1:1: `q` (wire:model.live.debounce.250ms), chips active set, filterGroups checked flags, `maxPrice` (debounced slider), `sort` (wire:change), `viewMode`, `page` (wire:click + `aria-current="page"`), favorites, `hasResults`. Single `HomePage`/`EventExplorer` component → later swap PHP array for DB query. Alpine stays ONLY for sidebar collapse, dark mode, grid/list transition.
- Favorites: session-backed array (guests) → pivot table later; `aria-pressed` + optimistic heart fill.
- Focus rings: global `:focus-visible` in app.css is OVERRIDDEN by inline `outline:none` on inputs. Add a `.needs-focus:focus-visible{outline:2px solid var(--primary)!important}` class (or similar) so inputs stay focusable without changing visuals.
- Whole-card click: title → real `<a>`/button so cards are keyboard-reachable (design makes whole article clickable).
- Contrast: `--muted` on `--bg` ≈ 3.4:1 fails AA — keep for fidelity, flag to user (agrees with mimo's Idea 3).

**My Round-3 recommendations (delta vs mimo's):**
- **My files**: (1) wrap home in `<x-app-layout>` — THE fix; (2) `type="text"` search; (3) keep "spots left" unless user confirms removal (it was an explicit task requirement in Round 1); (4) fix featured date margin 6→10px (mimo's P5 — correct, I missed it); (5) keep `.ev-card` inline OR move to app.css — happy either way since it's mimo's file (defer).
- **mimo's files**: (1) header bg → `color-mix(in srgb, var(--surface) 82%, transparent)` (token-derived, works both modes) OR design's exact rgba pair + `--header-bg` token — decide; (2) collapse-button label fix; (3) top-nav (P3); (4) FOUC script (mimo's Idea 1 — agree); (5) conditional badge.
- **Shared**: add `@isset($header)` back to layout for dashboard compat (tiny).

### big-pickle — Round 4 role extraction (organizer + admin)

**⚠️ CRITICAL design facts (verified, lines):**
- `sidebarOn:false` is HARDCODED (L1568) and `navGroups:[]` (L1571) — the sidebar NEVER renders for ANY role and has NO nav groups/items in the design. Role-shell differentiation is 100% via the HEADER top-nav + role badge + avatar. The "organizer sees MAIN+ORGANIZER groups / admin sees ADMIN group" assumption is NOT in the design. mimo's always-on sidebar is a divergence → decision needed (hide for fidelity, or keep as enhancement + invent nav items).
- Header top-nav IS role-dependent (not the same 4 tabs for everyone): organizer = Dashboard|My events|Check-in|Browse|Profile; admin = Admin|Dashboard|Check-in|Browse|Profile. Active tab fw800 + var(--primary) + 2px bottom border; inactive fw600 + var(--text). Guest/user navs differ (mimo's side).
- "Search users" input is on the ADMIN Users tab — NOT on scan. Scan has a ticket-reference input. "Platform dashboard" = odash-as-admin (h1), separate from "Admin console" (admin route).
- Profile h1 for org+admin = "Account settings" (user = "My profile").

**Shell deltas org vs admin:** sidebar (dead in design; markup exists): logo + role chip ("Organizer"/"Admin", 10px/800 uppercase, chip bg/primary fg) + user card — org: SL, gradient #7C3AED→#1565D8, Salma Lahlou/salma@rabatlive.ma; admin: AD, #DC2626→#F59E0B, Admin Evently/admin@evently.ma — + collapse btn (12px/600, surface2, radius 10, 38px). Header: 40px round gradient avatar + initials, tickets btn+badge, theme toggle, roleTabs strip (prototype affordance, drop?). Shared page container: max-w 1380px, pad 30px 26px 60px (create 960, scan 1100). H1 pattern everywhere: 28px/800/ls-.9px + sub 14.5px muted.

**odash** (org "Welcome back, Salma"; admin "Platform dashboard"): header row = h1+sub ("Live sales across your events · last {range}") + range segmented tabs (7 days/30 days/12 months, pad 4/radius 12, btns 9px 14px/40px/12.5px/700) + "+ New event" btn (gradient primary→dark, white 14px/700, 13px 20px, radius 12, 46px). 4 KPI cards (grid 4×, gap 16, radius 17, pad 18): icon 32px chip + label 12px/700 uppercase; value 27px/800/ls-1px; delta 12px/700 — Revenue +12.4% ok, Tickets sold +8.1% ok, Live events 6 "2 awaiting approval" warn, Check-in rate 87% "214 scanned tonight" muted. Charts (1.6fr/1fr): "Revenue & tickets" bar chart h190px, 2 bars/pt (gradient primary→cyan / chip+border), legend 9px squares, labels 11px/700; "Sales by category" 5×8px pills (primary/cyan/teal/#7C3AED/#F59E0B). "Recent orders" table: cols 1.4fr 1.6fr .7fr .8fr .8fr, header 11px/800 uppercase muted, rows 13.5px, 30px circle avatars, status badges (Paid ok/Pending warn/Refunded err, 5px 10px radius 8, 11px/800 uppercase), Export CSV btn (12.5px/700, 10px 15px, radius 10, 40px).

**oevents** ("My events" org / "All events" admin; sub "Publish, edit and track every event you run."): header row + New event btn. Status pills All/Published/Pending/Draft: 40px, 9px 15px, radius 11, 13px/700, count span opacity .6, active primary. Table (radius 18, pad 20) cols 2fr 1fr 1fr 1.3fr .9fr 1fr (Event/Date/Price/Sold/Status/Actions): event cell = 38px gradient square radius 10 + title 700 + city 11.5px muted; Sold = "1,840 / 2,400" 12px/700 + 6px progress gradient primary→cyan; badges Published ok/Pending warn/Draft muted; Actions = 3 icon btns 34px radius 9 (view muted / edit primary / delete err).

**create** (4-step wizard, max-w 960px, card radius 18 pad 26): back link "← Back to my events" 13px/700 muted; h1 + sub "Submitted events go to an admin for approval before going live." Step circles 30px (done primary/#fff, cur chip/primary bd primary, future transparent/muted) + 2px connectors + labels 13px. Step1: title, desc textarea rows4, Category+Format selects (48px, radius 12, 14.5px), cover dropzone (1.5px dashed radius 14 pad 30, "Drop an image or click to upload" 13.5px/700, "JPG or PNG, 1600×900 recommended" 12px). Step2: start/end datetime-local, Venue+City (1.4fr/1fr), venue preview 170px gradient + ping + label chip. Step3: tier rows (radius 14 pad 16, cols 1.6fr 1fr 1fr 40px) name/price(MAD)/qty 44px radius 10 + × err btn; "+ Add ticket tier" dashed primary; summary chip: Total capacity 17px/800 + Max revenue 17px/800 primary. Step4: cover preview 180px (cat chip + title 24px/800 white) + 6 mini-cards grid 3× (label 11px/800 uppercase + value 14px/700). Footer: Back (opacity .5 step1) / Save draft / Continue→"Submit for approval" (gradient fw800). Defaults: cat Music, fmt In person, city Casablanca, tiers GA 200×500 + VIP 450×80.

**scan** ("Door check-in", max-w 1100px; sub "Scan attendee QR codes or type a reference manually."): grid camera card (1fr) + aside 340px. Camera: aspect 4/3 radius 15, radial #123B66→#071426, 2px white corner frame, cyan scanline 2.4s+glow, QR svg 120px opacity .28, caption 12.5px/700. Manual row: ref input (flex1, 48px, radius 12, ph "BK-4C19A7-1") + Check in btn (primary fw800, 13px 24px, radius 12, 48px). Result card (radius 14 pad 16, ok/err border+bg): 38px circle icon, title 15px/800, sub 12.5px. Aside: "Tonight at the door" (22px/800 values + 12.5px/700 labels; 8px gradient progress) + "Recent scans" (8px dot ok/err, code 13px/700, when 11.5px muted, max 6). Stats: 214 in / 246 issued / 32 left.

**admin** ("Admin console", max-w 1380px; sub "Moderate events, manage accounts and keep the platform healthy."): segmented tabs Approvals/Users/Reports (pad 5 radius 13, btns 10px 16px/42px/radius 9/13px/700, active primary). Approvals: card rows (radius 16 pad 18) 64px grad cover + title 16px/700 + "org · date · city" 12.5px + Capacity (11px uppercase + 15px/800) + status badge (Pending warn/Approved ok/Rejected err) + Reject (err border .35/.07 bg, 13px/800, 11px 16px, radius 11, 44px) + Approve (ok bg, 11px 18px). Users: search "Search users by name or email…" (flex1 44px radius 11) + Invite user btn (primary, 13.5px/700, 12px 18px, radius 11, 44px); table cols 1.6fr 1.2fr 1fr .9fr 1fr — 32px avatars #0EA5E9→#1565D8, role badge (chip/primary uppercase), status Active ok/Suspended err, Suspend/Reactivate btn (12.5px/700, 9px 14px, radius 10, 38px); 6 seed users. Reports: 4 KPI cards (Gross 4.82M MAD +18% MoM, Active 12,940 +640, Organizers 186 "9 pending KYC" warn, Refund 1.8% −0.3% MoM; value 27px/800) + "Top cities by ticket volume" 5 bars (Casa 14.2k/Rabat 11.8k/Marrakech 9.1k/Tanger 6.4k/Salé 2.6k tix, gradient primary→cyan).

**Team decisions needed:** (1) Sidebar: hide per design (sidebarOn:false) vs keep mimo's always-on — if kept, define per-role nav items (design has none); (2) Header top-nav per role: implement role-dependent partial; drop roleTabs preview strip for production? (3) Shared table partial (11px/800 uppercase header + 13.5px rows, configurable grid cols) across odash/oevents/admin; (4) Shared status-badge + action-icon-button styles; (5) "Account settings" vs "My profile" title switch — mimo's profile page ownership.

## Decisions

- Three-agent team active: build (orchestrator) + mimo + big-pickle, dispatched in parallel via Task tool.
- IMPORTANT: always present a plan and get user approval BEFORE dispatching agents or writing app code.
- Stack: Laravel 12.64 + Blade + Livewire 4 + Breeze (blade auth) + Vite + Tailwind 4. MySQL db `evently` (root / **see local .env — never commit secrets**). One codebase.
- Only framework migrations exist (users, cache, jobs). NO custom migrations/models/features.

## Progress

- build: Laravel scaffold restored, Livewire + Breeze installed, npm build ok, `evently` DB recreated, framework migrations migrated.
- build: Pest 3.8 installed (pest:install done, 25 tests passing). laravel/boost 2.4 installed + `boost:install` ran (MCP servers added for OpenCode/Cursor/Codex).
- big-pickle: Created `resources/views/home.blade.php` (hero + featured + all events with filter sidebar) as pixel-port of design-evently-home.html via inline styles copied 1:1; route `/` → `Route::view('/', 'home')`. Verified: php -l clean, route:list OK, page renders HTTP 200 at http://evently.test (Vite manifest OK, no errors).
- big-pickle decisions: (1) prices in MAD (design is Morocco-based, slider labels "0 MAD"/"600 MAD"); (2) added small "spots left" line on cards (task requirement; warn/err/muted by count); (3) uses only tokens/keyframes already in app.css — no duplication; (4) content assumes layout provides `<main>` wrapper (Breeze default does); (5) newsletter/footer = mimo's chunk, not in home.blade.php; (6) hardcoded PHP array, 12 events, 6 visible + 3-col grid + pagination like design hint.
- mimo: **CSS done** — `resources/css/app.css` now has all design tokens (:root light + dark), Google Fonts import for Plus Jakarta Sans, all keyframe animations (wave, bob, drift, glow, up, spin, marq, rise, scanline, ping), and prefers-reduced-motion.
- mimo: **Layout done** — `resources/views/layouts/app.blade.php` is a `{{ $slot }}` component layout with: collapsible sidebar (240px↔64px, Alpine x-data, transition .22s), sticky header (blur 14px, height 66px, max-w 1380px), newsletter section (blue gradient card, email input + subscribe), footer (border-top, copyright). All exact dimensions from the design. Dark mode toggle persisted in localStorage.
- mimo: Updated `tailwind.config.js` fontFamily.sans to 'Plus Jakarta Sans' (was Figtree). Build passes, PHP lint clean.

### mimo — Round 3 Implementation (layout + css only)

**app.blade.php (8 changes):**
1. Sidebar width: 240→224px expanded, 64→72px collapsed (design JS values). .22s transition kept.
2. Header bg: replaced hardcoded `rgba(244,249,255,.82)` with `var(--header-bg)`.
3. Added header top-nav: `Events | My bookings | My tickets | Profile` tabs between logo and spacer. 13.5px/600 weight, 22px gap, active tab primary+2px bottom border, inactive muted+transparent border.
4. Collapse button: wrapped "Collapse" text in `x-show="expanded"` + added chevron icon (SVG, rotates 180° when collapsed) always visible.
5. Tickets badge: now conditional — `@if(session('cart_count', 0) > 0)` shows badge with count, else omitted.
6. Added FOUC prevention script in `<head>` before Vite/Livewire tags.
7. Added `@isset($header){{ $header }}@endisset` in header for Breeze dashboard compat.
8. Removed newsletter section (big-pickle will move it into home.blade.php).

**app.css (3 additions):**
9. Added `--header-bg: rgba(255,255,255,.88)` to `:root` and `--header-bg: rgba(14,33,56,.85)` to dark theme.
10. Moved `.ev-card` transition + hover rules from home.blade.php inline style (with comment).
11. Added `.needs-focus:focus-visible` utility with `!important` to beat inline `outline:none`.

**Verification:** `php -l` both files clean, `pint --dirty` passed.
**Did NOT touch:** home.blade.php, routes, other layout files.
- big-pickle: Round-3 impl done in `home.blade.php` — wrapped whole page in `<x-app-layout>` (curl now returns `<!DOCTYPE html>` + sidebar/header markup); removed "spots left" from featured + all-events cards (kept `spots` data); featured date-line margin 6→10px; hero search `type="text"`; deleted inline `.ev-card` `<style>` block (mimo owns app.css rules); added newsletter section (design-exact: gradient 120deg, wave SVG `wave 13s`, email input + Subscribe) at end of page; a11y: `aria-pressed` on 10 save buttons (data-driven via new `fav` key, fill `#DC2626`/`none` per design favFill), `role=checkbox`+`aria-checked` on all 14 filter options, `needs-focus` on search/chips/filters/range/sort/view-toggle/newsletter input (26 elements), `aria-current="page"` on active pagination. Verified: php -l clean, HTTP 200, newsletter count 1 (mimo's layout copy gone — no dup). Note: added `needs-focus` to newsletter email input + sort/view toggles as a small consistent extension of decision 7 — flag if unwanted. `.needs-focus` CSS rule still missing from app.css (mimo's task) — class is in markup but inert until it lands.

### big-pickle — Round 5 Implementation (layout overhaul + organizer/admin views)

**Files created/edited:**
1. `resources/views/layouts/app.blade.php` — **sidebar REMOVED entirely** (design `sidebarOn:false`, `navGroups:[]`). Alpine now dark-mode only. Added props: `$activeRole` (default `guest`), `$navRole` (default `guest`), `$avatarRole` (default `guest`), `$activeNav` (default = role start route: guest/user→events, organizer→odash, admin→admin).
2. `resources/views/organizer/dashboard.blade.php` — odash, activeNav 'odash'.
3. `resources/views/organizer/events.blade.php` — oevents, activeNav 'oevents'.
4. `resources/views/organizer/events/create.blade.php` — create wizard, activeNav 'oevents'.
5. `resources/views/organizer/scan.blade.php` — scan, activeNav 'scan'.
6. `resources/views/admin/index.blade.php` — admin, activeNav 'admin'.

**Key specs (all 1:1 from design):**
- Role tabs strip (header, before theme toggle): surface2/1px border, radius 11, pad 4; buttons 12px/700, pad 7px 11px, radius 8 — active `var(--primary)`/#fff, inactive transparent/`var(--muted)`. Hrefs: guest/user→`/preview/events`, organizer→`/preview/odash`, admin→`/preview/admin`.
- Header nav per role (design L1575-1583 labels/order): 13.5px/600, gap 22px, active = primary + 2px bottom border, inactive muted. Nav items without a preview route (Sign in, Create account, My bookings, My tickets, Check-in, Profile, Browse) use `href="#"` — **build can wire them** at merge.
- Avatar per role: G gray `#5B7794→#8FAAC6`, YB blue `#0EA5E9→#1565D8`, SL purple `#7C3AED→#1565D8`, AD red `#DC2626→#F59E0B` (40px, 14px/700). Tickets button hidden for guest (directive), badge only when `session('cart_count') > 0`.
- Kept: FOUC script, dark toggle, `{{ $slot }}`, `@isset($header)`, footer. No newsletter (home owns it). **Did NOT touch** routes, home, auth, app.css.
- odash: h1 28px/800 "Welcome back, Salma", range pills (30 days active), +New event 46px gradient; 4 KPIs (Revenue 8,673,560 MAD / Tickets 51,278 / Live 6 / Check-in 87%); chart W1-W5 dual bars (rev 49/86/55/92/61%, tix 71/65/59/53/47% — design's computed values for range 30d); cat pills (Music 21%, Business 7%, Tech 2%, Art 3%, Sports 84% — design's own sums); orders table 6 rows (cols 1.4fr/1.6fr/.7fr/.8fr/.8fr), badges ok/warn/err, Export CSV.
- oevents: status pills All(6)/Published(3)/Pending(2)/Draft(1) — All active (design default eTab); table cols 2fr/1fr/1fr/1.3fr/.9fr/1fr, 38px gradient thumbs, sold + 6px gradient progress, 3 icon actions (view muted/edit primary/delete err).
- create: 4-step indicator (step 1 current = chip/primary/1.5px primary border; connectors 2px), step-1 content (title, desc rows4, Category Music + Format In person selects, cover dropzone 1.5px dashed), footer Back (opacity .5) / Save draft / Continue. All 4 steps' content is static — only step 1 rendered (design default cstep:1).
- scan: camera 4/3 radial `#123B66→#071426`, 2px white corner frame, cyan scanline (scanline keyframe), **QR = design's `qrPath("scanner")` computed deterministically via PHP port** (pixel-identical), opacity .28 fill #9BD3F2; input `BK-4C19A7-1` + Check in; result card static OK ("Welcome in · BK-4C19A7-1 · General admission · valid"); aside 340px: 214/246/32 + 87% progress; scan log 6 rows (design has 4 in state — extended with `BK-77B210-2` 6 min, `BK-8F21C4-1` 9 min, both design refs).
- admin: h1 "Admin console", segmented tabs Approvals active + **all three sections stacked** (Approvals 4 cards 64px grads/Pending review badges/Reject+Approve; Users search "Search users by name or email…" + Invite + 5-col table 6 users role badges/Suspend; Reports 4 KPIs — Gross 4.82M MAD, Active 12,940, Organizers 186, Refund 1.8% "−0.3% MoM" — + Top cities bars Casa 100/Rabat 83/Marrakech 64/Tanger 45/Salé 18%).

**Verification:** `php -l` × 6 clean; `php artisan view:cache` passes; all 6 views render via `view()->render()` (home included — guest nav + role tabs OK); Pint `--dirty` passed.

**Open notes for build:** (1) nav items with `#` hrefs need preview routes at merge (ubookings/tickets/profile/scan/oevents/login/register); (2) create wizard renders step 1 only (design default) — other steps' markup exists in design but was not ported per UI-only chunk; can add if wanted; (3) role tabs "User" href is `/preview/events` per directive (mimo's events preview).

### big-pickle — FINAL REVIEW (layout + organizer/admin, 2026-07-31, review-only)

**Verified vs design source (Downloads/Evently.html, computed values re-derived):**
- odash KPIs verified by recomputing from design EVENTS/ORDERS data: Revenue 8,673,560 MAD = Σ(price×sold) e1-6 ✓; Tickets 51,278 ✓; chart W1-W5 heights + MAD/ticket labels match the `rev=30+((i*37+cc)%68)` formula exactly ✓; catBars = CATS sums (9,660/3,100/790/1,398/38,000, pct of 45,000) ✓; orders table = ORDERS 1:1 ✓.
- oevents rows/statuses verified: evStatus = `id%5→Draft, id%3→Pending, else Published` — all 6 rows + pill counts (6/3/2/1) ✓; GRAD thumbs match design map ✓.
- admin: 4 PENDING rows + 6 USERS rows + Reports KPIs/cityBars all match design arrays 1:1 ✓. create/scan markup match design (cstep:1 = step 1 only; scan result = OK state for BK-4C19A7-1).
- Shell: 66px header, --header-bg rgba(255,255,255,.88)/dark rgba(14,33,56,.85), h1 28px/800/ls-.9px, KPI 27px/800, body Plus Jakarta Sans, bg #F4F9FF, no sidebar, footer on all pages, role tabs + per-role nav + avatars (SL purple/AD red) all verified in-browser + HTTP 200 ×6, no console errors.

**Issues found (all minor, no blockers):**
1. Dead-link CTAs: odash+oevents "+ New event" href="#"; create "← Back to my events" href="#" → should be /preview/create and /preview/oevents (E-check fails).
2. Header nav styling differs from design: design = 14px/600, gap 4px, ml 8px, buttons pad 10px 14px min-h 44px, INACTIVE color var(--text); our layout = 13.5px, gap 22px, ml 12px, pad 0 0 2px, inactive var(--muted). Round-4 note said 13.5px/gap22 — actual design HTML contradicts it.
3. Scan QR NOT pixel-identical (team note claim wrong): design JS multiplies in float64 (h*1103515245 > 2^53 → precision loss); PHP port uses exact ints → different random pattern (design 323 cells / 4299 chars vs port 325 / 4323; ~128 cells differ). Fix: mirror JS semantics or hardcode JS-computed path.
4. Admin nav "Dashboard" → /preview/odash?role=admin still shows "Welcome back, Salma"; design shows "Platform dashboard" for admin (dashTitle per role). Same for oevents title ("All events" vs "My events").
5. Admin page stacks Approvals+Users+Reports sections; design renders only the active segmented tab's section.
6. Console warn on every page: "Detected multiple instances of Alpine running" — app.js Alpine.start() + Livewire 4's bundled Alpine. Functionality OK.
7. create: a11y issue "form field element should have an id or name (count: 3)" — selects/inputs lack id/name (also blocks future form posts).
8. Responsive ~900px: header overflows horizontally (~106px page overflow, header content exceeds 66px bar, no wrap). KPI grid stays 4-across (design repeat(4,…) so faithful) but squeezes fine. Table columns get tight.

## Open Questions


- **mimo R5**: Auth pages are standalone (own full HTML shell) — they include header logo + the 2-col grid but NOT the shared `<x-app-layout>` header/sidebar/footer. This means auth pages won't have the sticky header or role tabs. If big-pickle wants the header on auth pages, we'd need a separate auth layout component. Flag for decision.
- **mimo R5**: Register role selector uses Alpine.js `x-data` for the Attend events / Create events toggle — this is purely visual (no form submission of the role value). The `role` Alpine state isn't wired to a hidden input. If backend needs it, add `<input type="hidden" name="role" :value="role">`. Flag for build.
- **mimo R5**: Profile `@props(['pageTitle'])` — big-pickle can pass `:page-title="'Account settings'"` for organizer/admin roles via the layout or controller. Currently standalone `@props` with default.
- **mimo R5**: All views use static demo data (no DB). When routes + controllers are added, the `@php` blocks should be replaced with actual Eloquent queries.


- big-pickle: awaiting home.blade.php + route. mimo's layout is ready for content.
- **mimo**: Should sidebar be ALWAYS visible (current behavior) or hidden for guest role (design default `sidebarOn:false`)? Design only shows sidebar for logged-in roles. Decision affects layout structure.
- **mimo**: Header top-nav (Events|My bookings|...) — include in layout for all pages, or make it a per-page Blade component? Design shows it role-dependent.
- **mimo**: "Spots left" on cards — keep as intentional enhancement (big-pickle's decision) or remove to match design exactly? Both valid, user preference needed.
- **mimo**: Newsletter placement — keep in layout (every page) or move to home.blade.php only (matches design)? Design has it inside `<main>` only on home route.
- **mimo**: Design's header bg is `rgba(255,255,255,.88)` light / `rgba(14,33,56,.85)` dark. Should we use these exact values or create semantic `--header-bg` tokens?
- **big-pickle**: CRITICAL — home renders WITHOUT the layout (Route::view bypasses `{{ $slot }}`). Round 3 must wrap home in `<x-app-layout>`. Who owns this: build (route) or big-pickle (view wrapper)? Recommend: big-pickle wraps the view; route stays `view('home')`-style via controller or closure.
- **big-pickle**: Sidebar width 224/72 (mimo's JS reading) vs current 240/64 — HTML only shows `{{ sidebarW }}` placeholder. Confirm against original screenshot before changing.
- **big-pickle**: "Spots left" on cards — was an explicit Round-1 task requirement (mine). Keep, or remove for pixel-perfection? User call.
- **big-pickle**: Role-preview tabs in header — design has them, but they're a prototype affordance. Drop for public home?
- **big-pickle**: Newsletter in layout (all pages) vs home only (design) — same Q as mimo's; needs one decision, not two.

### mimo — Round 5 Implementation (guest + user views, UI-only)

**Files created (7 views):**

1. **`resources/views/events/show.blade.php`** — Event detail page. Ported from design rDetail (lines 503-598). Max-w 1380px, hero image h320px with gradient + dark overlay, back link, category/status badges, 38px/800 title, date·venue meta, 2-col content grid (left: About card + Organizer card + related; right: sticky booking widget + sales progress). Booking widget has 3 ticket rows (GA/VIP/Early bird) with qty stepper (−/count/+), subtotal/fee/total, disabled CTA, Stripe note. All static demo data from design (Saad Lamjarred Concert, 1840/2400 sold). Wrapped in `<x-app-layout>`.

2. **`resources/views/auth/login.blade.php`** — Standalone (no `<x-app-layout>`). Design's 2-col layout: left = form (logo + h1 "Welcome back" 30px/800/ls-1px + email/password + gradient CTA + links), right = decorative gradient panel (glow circle, "EVENTLY PLATFORM" label, h2 34px, perks with checkmarks, wave). `@csrf` + form POST to `route('login')`. Error display via `@error`.

3. **`resources/views/auth/register.blade.php`** — Same 2-col standalone layout. Left: h1 "Create your account", 4 fields (name/email/password/confirm), role selector (2-col grid with Alpine.js `x-data="{ role: 'user' }"` for Attend events / Create events toggle — primary border+chip bg when active), CTA, link. Right: same decorative panel.

4. **`resources/views/auth/forgot-password.blade.php`** — Same 2-col standalone. Left: h1 "Reset your password", email input + "Send reset link" CTA + "← Back to sign in" link. Right: same decorative panel. Session status display for flash message.

5. **`resources/views/bookings/index.blade.php`** — My bookings. Max-w 1100px, h1 28px/800, sub 14.5px/muted. Filter tabs (All 4 / Confirmed 2 / Pending 1 / Cancelled 1) with active primary styling. 4 booking cards matching design: gradient thumb 58x58, event title 16px/700, date·city·ref 12.5px/muted, qty+total, status badge (Confirmed=ok/Pending=warn/Cancelled=err), Details button. Static demo data (BK-4C19A7, BK-77B210, BK-2E90FF, BK-19AA31).

6. **`resources/views/bookings/show.blade.php`** — Booking detail. Max-w 1000px, back link, main card with gradient header strip (120deg primary-dark→primary, "BOOKING REFERENCE" 11px/800/uppercase, ref BK-4C19A7 26px/800, status badge). 2-col body: left = event info + 2 ticket rows (QR SVG 60x60, type, code, status badge) + activity timeline (3 dots + labels). Right aside = payment card (subtotal/fee/total/status) + "Cancel booking" err button + refund note. Static demo data from design.

7. **`resources/views/tickets/index.blade.php`** — My tickets. Max-w 1100px, h1 + sub. Auto-fill grid `minmax(320px,1fr)`. 5 ticket cards: gradient header band (event title, date·venue), body with QR SVG 104x104, ticket type + reference + status badge. Used tickets at opacity .6 with muted badge. Static demo data from design (mix of Valid/Used).

8. **`resources/views/profile/edit.blade.php`** — Profile. Max-w 820px. `@props(['pageTitle' => 'My profile'])` for role reuse. Card 1: 64x64 avatar (gradient #0EA5E9→#1565D8, "YB" initials), name/email, role badge (chip bg, primary fg), 2-col form (Full name + Email inputs 46px h, surface2 bg, rounded 11px), "Save changes" primary btn. Card 2: "Change password" h2 16px/800, 3-col grid (Current/New/Confirm password), "Update password" surface2 btn. Form POST to profile.update + password.update routes.

**home.blade.php fidelity fixes (3 edits):**
- Hero chips: replaced category labels (Music/Festival/...) with design's contextual filters: Today / This weekend / Free / Online / Evening / Near me
- Hero stats: updated from "1.2K+/850K+/4.9★" to design's "124/38K/4.8★"
- Live label: updated from "1,200+ events live now" to "12 events live in Casablanca, Rabat & beyond"
- Filter groups: changed from Category/Location/Format to design's Categories/Format/Time of day with correct options
- Sort options: changed from Newest/Price... to design's Recommended/Date (soonest)/Date (latest)/Price (low→high)/Price (high→low)
- Results label: changed from "All events (N)" to "N events found"

**Verification:** `php -l` all 9 files clean, `pint --dirty` passed.

**Did NOT touch:** routes, app.css, app.blade.php, organizer/admin views, dashboard.blade.php.

---

### mimo — Round 4 Role Extraction (guest + user)

#### CRITICAL DESIGN FINDING: sidebarOn is ALWAYS false

The design JS (line 1568) hardcodes `sidebarOn: false` and `navGroups: []` for ALL roles. The sidebar HTML exists but never renders. **All navigation in the design prototype uses the header top-nav only.** The sidebar is a design concept, not implemented. Our layout's `@auth` sidebar with nav items is an ENHANCEMENT over the design, not a fidelity gap.

---

#### 1. Guest View — Deltas vs Our home.blade.php

| Aspect | Design (guest) | Our home.blade.php | Delta |
|--------|---------------|-------------------|-------|
| **Sidebar** | Hidden (`sidebarOn:false`) | Hidden (`@auth` gate) | ✅ Match |
| **Header top-nav** | Events · Sign in · Create account (3 items) | Events · My bookings · My tickets · Profile (4 items) | ❌ MISMATCH — our layout shows user nav for guests |
| **Role preview tabs** | Guest/User/Organizer/Admin buttons in header | Not present | ⚠️ Prototype affordance — omit intentionally |
| **Header avatar** | Gray gradient `#5B7794→#8FAAC6`, initial "G" | Blue gradient, initial "G" via `@auth` fallback | ❌ Wrong gradient for guest |
| **Hero live badge** | "12 events live in Casablanca, Rabat & beyond" | "1,200+ events live now" | Content differs (mock data) |
| **Hero stats** | 124 / 38K / 4.8★ | 1.2K+ / 850K+ / 4.9★ | Content differs (mock data) |
| **Hero chips** | Today / This weekend / Free / Online / Evening / Near me | Music / Festival / Conference / Workshop / Expo / Sports | ❌ Different chip types — ours are categories, design's are contextual filters |
| **Filter sidebar groups** | Categories · Format · Time of day | Category · Location · Format | ❌ Different filter groups |
| **Sort options** | Recommended / Date (soonest) / Date (latest) / Price (low→high) / Price (high→low) | Newest / Price: Low to High / Price: High to Low / Most Popular / Date | Different labels |
| **Results label** | "X events found" | "All events (12)" | Different phrasing |
| **Newsletter** | Inside `<main>` (guest-only section) | Inside layout outside `<main>` — shows on every page | ❌ Placement differs (P8 from Round 2) |
| **Footer** | Same structure | Same structure | ✅ Match |

**Summary**: Structurally similar, but header top-nav and hero chips are meaningfully different. The header nav is the most important fidelity gap — guests should see "Events | Sign in | Create account" not the user nav items.

---

#### 2. User Shell Changes vs Guest

| Aspect | Guest | User |
|--------|-------|------|
| **Sidebar** | Hidden | Visible (@auth gate) |
| **Header top-nav** | Events · Sign in · Create account | Events · My bookings · My tickets · Profile |
| **Header avatar** | Gray "G" gradient | Blue `#0EA5E9→#1565D8`, "YB" initials |
| **Tickets badge** | Hidden (`hasCart:false` default) | Shows count when items in cart |
| **Sidebar nav groups** | N/A | Design: `navGroups:[]` (empty). Our layout: MAIN (Home, Browse, My bookings, My tickets) — enhancement |
| **Sidebar user card** | N/A | Name: "Yassine Benali", email: "yassine@example.com", avatar gradient `#0EA5E9→#1565D8`, initial "YB" |
| **Workspace badge** | N/A | "User" label, chip bg, primary fg |

**User sidebar card details (design lines 251-259):**
- Avatar: 34×34px circle, gradient bg, weight 700, size 13px
- Name: 13px, weight 700, truncate with ellipsis
- Email: 11px, muted color, truncate

---

#### 3. Per-Route Specs (guest + user routes)

**EVENTS (route: events)** — See section 1 above. Same page for both guest and user; only shell differs.

---

**DETAIL (route: detail)**
- Page: max-w 1380px, centered, pad 22px 26px 60px
- Back link: "← Back to events" (13px/w700/muted), margin-bottom 12px
- Hero image: h 320px, rounded-20px, overflow hidden, category gradient bg
  - Dark gradient overlay (bottom 70%)
  - Badges row: category + status (11px/800/uppercase, white or ok/err bg)
  - Title: 38px/800, white, letter-spacing -1.2px, max-width 22ch
  - Meta: date · venue, city (14px/600, white/.9)
- Content grid: 2-col (`minmax(0,1fr) 360px`), gap 26px, mt 26px
  - **Left col** (stack):
    - "About" card (surface, rounded-18px, pad 24px): h2 18px/800, desc 14.5px/muted/lh1.75, 3 facts grid (surface2, rounded-13px): label 11px/800/uppercase + value 14px/700
    - Organizer card: avatar 46×46 circle (gradient), "ORGANIZER" label 11px/800/uppercase, name 15px/700, "Share" button (surface2/border/radius-11px)
    - "You may also like": h2 18px/800, 3-col grid of mini cards (96px gradient, 14px/700 title, 12px/muted date·city)
  - **Right aside** (sticky top 82px):
    - Booking widget (surface, rounded-18px, shadow, pad 20px):
      - "SELECT TICKETS" 12px/800/uppercase/muted
      - Ticket rows (rounded-14px, pad 14px): name 14px/700 + stock 12px/muted, price 14px/800/primary, qty controls: − (36×36/border/surface2) count (15px/800) + (36×36/primary/white)
      - Divider → Subtotal/Service fee (13px/600/muted) + Total (17px/800)
      - CTA: full-width, 52px h, rounded-13px, gradient primary→primary-dark, 15px/800, disabled state muted/.65
      - Note: "Secure payment via Stripe · instant QR ticket" (11.5px/muted/center)
    - Sales progress card: "Tickets sold" + bar (h9px/rounded-99px/chip bg, gradient fill), urgency text 12px/muted

---

**LOGIN (route: login)**
- Layout: `min-height:calc(100vh - 66px)`, 2-col equal grid
- Left (centered, max-w 400px):
  - h1: 30px/800/ls -1px "Welcome back"
  - Sub: 14.5px/muted "Sign in to manage your bookings and tickets."
  - Fields (stack, gap 14px): Email (email), Password (password) — each label 12.5px/700 + input min-h 48px/pad 13px 15px/border/surface2/rounded-12px/14.5px
  - CTA: gradient primary→primary-dark, 15px/800, rounded-13px, min-h 52px
  - Links: "Forgot password?" + "Create an account" (13px/700/primary, flex space-between)
- Right panel: gradient (160deg, primary-dark → primary 50% → cyan), overflow hidden
  - "EVENTLY PLATFORM" 11px/800/uppercase/opacity.8
  - h2: 34px/800, "Every ticket, every attendee, one calm dashboard."
  - Desc: 15px/lh1.7/opacity.85
  - Perks: checkmark circles (24×24, rgba white .2) + text 14px/600
  - Wave decoration at bottom

---

**REGISTER (route: register)**
- Same 2-col layout as login
- Left: h1 "Create your account", sub "Book events or start selling tickets in minutes."
  - Fields: Full name (text), Email (email), Password, Confirm password (4 fields)
  - **Role selector** ("I want to…"): 2-col grid of role cards
    - "Attend events" (sub: "Book & keep tickets") — selected = primary border + chip bg
    - "Create events" (sub: "Sell & check in")
  - CTA: "Create account"
  - Link: "Already have an account? Sign in"

---

**FORGOT PASSWORD (route: forgot)**
- Same 2-col layout
- Left: h1 "Reset your password", sub "We'll email you a secure reset link."
  - Fields: Email only
  - CTA: "Send reset link"
  - Link: "← Back to sign in"

---

**MY BOOKINGS (route: ubookings)**
- Page: max-w 1100px, centered, pad 34px 26px 60px
- h1: 28px/800/ls -.9px "My bookings"
- Sub: 14.5px/muted "Every order you placed on Evently, newest first."
- **Filter tabs** (flex, gap 10px, mb 18px): All(4) · Confirmed · Pending · Cancelled
  - Pill buttons: min-h 40px, pad 9px 15px, border/bg/color states, 13px/700, count opacity .6
- **Booking cards** (stack, gap 12px):
  - Article: surface, border, rounded-16px, pad 18px, flex row/center, gap 18px
    - Thumbnail: 58×58px, rounded-14px, category gradient
    - Info: event name 16px/700, date · city · ref 12.5px/muted/600
    - Right: qty label 11px/muted/800/uppercase + total 16px/800
    - Badge: 11.5px/800/uppercase/rounded-9px (Confirmed=ok, Pending=warn, Cancelled=err)
    - "Details" button (border/surface2/13px/700)
- **Empty state**: dashed border, rounded-18px, pad 60px, centered
  - "No bookings yet" 17px/800, "Browse events to get started" 14px/muted
  - CTA: primary bg/white/700
- Header: top-nav "My bookings" active

---

**BOOKING DETAIL (route: booking)**
- Page: max-w 1000px, centered, pad 34px 26px 60px
- Back: "← Back to bookings" (same style as detail back link)
- **Main card**: surface, border, rounded-20px, overflow hidden
  - **Header strip**: gradient primary-dark→primary, pad 26px, flex row, gap 20px
    - "BOOKING REFERENCE" 11px/800/uppercase/opacity.78
    - Ref "BK-4C19A7" 26px/800/ls -.6px
    - Status badge: rounded-10px, rgba white .2, 12px/800/uppercase
  - **Body**: 2-col grid (`minmax(0,1fr) 300px`), pad 26px
    - **Left**:
      - Event title: 21px/800, date · venue 13.5px/muted/600
      - "TICKETS" 12px/800/uppercase/muted
      - Ticket rows (gap 10px): border, rounded-14px, pad 14px, flex row
        - QR SVG: 60×60, white bg, rounded-6px
        - Type 14px/700 + code 12px/muted
        - Status badge 11px/800/uppercase/rounded-8px
      - "ACTIVITY" 12px/800/uppercase/muted
      - Timeline (stack): dot (10×10 circle/primary/box-shadow chip) + label 13.5px/700 + when 12px/muted
    - **Right aside**:
      - Payment card (surface2, rounded-16px, pad 18px): subtotal/fee/total/status rows
      - "Cancel booking" button (err color, translucent err bg, 13.5px/800)
      - Refund note 11.5px/muted

---

**MY TICKETS (route: tickets)**
- Page: max-w 1100px, centered, pad 34px 26px 60px
- h1: 28px/800/ls -.9px "My tickets"
- Sub: 14.5px/muted "Show the QR code at the door. Works offline."
- **Grid**: auto-fill, `minmax(320px, 1fr)`, gap 16px
- **Ticket cards**:
  - Article: surface, border, rounded-18px, overflow hidden
    - Header band: category gradient bg, white text, pad 16px 18px
      - Event name: 16px/800/ls -.3px
      - Date · venue: 12.5px/600/opacity.85
    - Body: pad 18px, flex row, gap 18px
      - QR: 104×104 SVG, white bg, rounded-8px, padding 2px
      - Details stack (gap 9px):
        - "TICKET TYPE" 11px/800/uppercase/muted + type 14px/700
        - "REFERENCE" 11px/800/uppercase/muted + code 13.5px/700
        - Status badge: self-start, 6px 11px pad, rounded-8px, 11px/800/uppercase
    - Used tickets: opacity .6, badge muted

---

**PROFILE (route: profile)**
- Page: max-w 820px, centered, pad 34px 26px 60px
- h1: 28px/800/ls -.9px, "My profile" (user) or "Account settings" (organizer/admin)
- **Card 1 — Profile** (surface, rounded-18px, pad 24px):
  - Avatar row (flex, gap 16px, mb 22px):
    - 64×64 circle (gradient bg), initial 24px/800/white
    - Name: 18px/800
    - Email: 13px/muted/600
    - Spacer
    - Role badge: rounded-9px, chip bg, primary fg, 11.5px/800/uppercase
  - Form: 2-col grid (1fr 1fr), gap 14px
    - Full name input + Email input (min-h 46px, pad 12px 14px, border, surface2 bg, rounded-11px, 14px)
  - "Save changes" button: primary bg, white, 14px/700, rounded-12px, min-h 46px
- **Card 2 — Password** (surface, rounded-18px, pad 24px):
  - Title: "Change password" 16px/800
  - 3-col grid: Current / New / Confirm (password inputs, same style)
  - "Update password" button: surface2 bg, border, 14px/700, rounded-12px

---

#### 4. Team Decisions Needed

1. **Header top-nav must be role-dependent**: Guest → Events | Sign in | Create account. User → Events | My bookings | My tickets | Profile. Our layout currently hardcodes the user version for all. This needs a `@auth` / `@guest` conditional or pass role data to the layout.

2. **Sidebar nav should be role-gated**: The current sidebar shows MAIN + ORGANIZER items for ALL authenticated users. For user role, only MAIN group should show (Home, Browse, My bookings, My tickets). ORGANIZER group should only appear for organizer+ roles. Need role-aware nav or separate sidebar partials.

3. **Avatar initials**: Design shows full initials ("YB" for Yassine Benali). Our layout uses `substr(name, 0, 1)` → shows only "Y". Should we use full initials to match design?

4. **Guest avatar gradient**: Design uses gray `#5B7794→#8FAAC6` for guest. Our layout uses the same primary→cyan gradient for everyone. Minor fidelity gap.

5. **Hero chips are fundamentally different**: Design uses contextual quick filters (Today, Free, Online, Evening, Near me). Our home uses category labels (Music, Festival, etc.). These serve different UX purposes. Decision: match design's contextual chips, or keep our category chips?

6. **Newsletter placement**: Design puts newsletter inside `<main>` only on the home page. Our layout puts it outside `<main>` so it appears on every page. Already flagged in P8 (Round 2). Need to decide: move to home.blade.php only (design match) or keep in layout.

7. **Which views share layout vs are standalone?**: 
   - All 14 routes should share the shell (header + footer + conditional sidebar)
   - Auth pages (login/register/forgot) use the shell but the header top-nav changes
   - The right decorative panel on auth pages is page-specific content (in `{{ $slot }}`)
   - Detail, ubookings, booking, tickets, profile are all `$slot` content inside the same shell

### build � Round 5 merge results (2026-07-31)

- Merged both round-5 branches of work: layout props wired in ALL views. Organizer views pass activeRole/navRole/avatarRole/activeNav; user views (bookings/index, bookings/show, tickets/index, profile/edit) now patched to pass user props. events/show + home keep guest default (matches design).
- Added 15 static preview routes under /preview (View-only, no DB): events, events/atlantis-live, ubookings, booking, tickets, profile, odash, oevents, create, scan, admin, login, register, forgot.
- Verified in Chrome DevTools (evently.test):
  - Home: no sidebar ?, role tabs Guest/User/Organizer/Admin ?, guest nav (Events�Sign in�Create account) ?, hero chips contextual (Today/This weekend/Free/Online/Evening) ?
  - Hero height = 494px EXACTLY matching design (line-height:normal fix in app.css confirmed working)
  - odash: "Welcome back, Salma" h1 28px, organizer nav ?, range pills (7/30 days, 12 months), Export CSV, + New event, KPI 8,673,560 MAD ?
  - admin: "Admin console", admin nav ?, segmented Approvals/Users/Reports, 4 Approve/Reject rows, Invite user, Suspend buttons, search users input ?
  - tickets: "My tickets", user nav ?, refs BK-4C19A7 & BK-77B210 ?
- npm run build clean (9s), php -l clean, Pest: 25 passed (61 assertions).
- Status: ALL 14 design pages now ported + viewable. Next: user visual review; commit when user confirms.

### build � Guest vs User fix (2026-07-31, user reported "user and guest are not the same")

- ROOT CAUSE: role tabs all pointed at shell-less pages; the events page always rendered GUEST props. In the design (L1588) tabs switch role+route: guest?events, user?events, organizer?odash, admin?admin � and the events page shows the role's OWN shell.
- Fixed in layouts/app.blade.php: roleTabs hrefs (User?/preview/events?role=user), nav items now keyed via routePaths map (no more '#' dead links: Sign in?login, Create account?register, My bookings?ubookings, My tickets?tickets, Profile?profile, Check-in?scan, My events?oevents), logo + nav links + tickets button get ?role= passthrough (roleSuffix).
- DESIGN FIDELITY: tickets header button is UNCONDITIONAL in design (L290) � removed our guest-hide gate; now shows for all roles like the design.
- home.blade.php + events/show.blade.php: read request('role') (whitelisted) and pass activeRole/navRole/avatarRole props. Content identical for guest/user (design L1588 confirms); only shell differs.
- Event cards (featured + grid) now clickable ? detail with role preserved; fav button stops propagation; back links preserve role.
- VERIFIED in browser: guest=Events�Sign in�Create account + G gray avatar; user=Events�My bookings�My tickets�Profile + YB blue avatar; organizer shell on Browse (SL); detail keeps role; back link /preview/events?role=user. Tests 25 passed, view:cache clean.

### build - Setup guide written (2026-07-31)

- User asked for a guide so a friend can replicate the 3-agent team setup. Created `3-agent-team-setup.md` (project root): explains the pattern, includes copy-paste `opencode.json` (agents build/mimo/big-pickle + prompts), `.opencode/team-notes.md` blackboard template, optional `AGENTS.md`, workflow rules, troubleshooting, and a checklist for the friend.
- Verified the real config lives in project `opencode.json` (agents defined there, not global) + global `~/.config/opencode/opencode.json` only has chrome-devtools MCP.

### build � Final review round (2026-07-31): audit results + fixes

- Team-wide audit (mimo: guest/user pages; big-pickle: layout+org/admin): NO blockers. All 14 pages PASS WITH MINOR or better.
- FIXED (build):
  1. Mojibake (triple-encoded UTF-8/CP1252) in bookings/index, bookings/show, tickets/index, profile/edit � byte-level repair of 5 corrupt sequences; 0 remaining; "H�tel Sofitel" now correct.
  2. Auth pages (login/register/forgot) now render INSIDE x-app-layout like the design (header w/ role tabs + guest nav, activeNav login/register/forgot) instead of standalone HTML; internal links ? /preview/*.
  3. Header nav styled per design L275-281: 14px, gap 4px, pad 10px 14px, min-h 44px, active 800/primary/2px underline, inactive 600/--text.
  4. Alpine double-instance warning: removed Alpine.start() from app.js (Livewire 4 bundles Alpine); bundle 94.8?48.6 kB; dark toggle + register role selector still work.
  5. Dead CTAs wired: +New event (odash, oevents)?/preview/create; Back to my events?/preview/oevents; Details buttons (ubookings)?/preview/booking; booking back link preserves ?role=user.
  6. Admin console: only active segmented tab's section renders (?tab=Users|Reports, default Approvals) � matches design aTab.
  7. odash dashTitle per design L1593: ?role=admin ? "Platform dashboard" (verified), else "Welcome back, Salma".
- KNOWN ACCEPTED (documented, not blockers): scan QR pattern differs from design's JS float64 math (~128 of 325 cells) � invisible to human eye; header overflow at <900px (design behaves identically); create wizard is step 1 only (design default cstep:1); event-card role param reviewed as false-positive (code includes ?role=, verified live).
- VERIFIED live in Chrome: login shows shell+active "Sign in", tickets page zero mojibake, admin ?tab switching, Platform dashboard title, Alpine selector, dark mode, 25 tests pass, build clean.
