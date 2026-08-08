# Evently — Mobile Door Experience, Ticket Presentation & Final Core Polish

## Branch

Work on:

`feat/mobile-door-experience`

Do not work directly on `main`.

---

# 1. Context

Evently is a Laravel 13 / PHP 8.4 event platform with:

* attendee event discovery
* authentication and roles
* organizer event management
* event approval workflow
* ticket types
* bookings
* mock payment flow
* QR ticket generation
* organizer check-in
* admin management
* AI Event Copilot
* Blade
* Alpine.js
* Tailwind/CSS
* Vite
* Pest 4
* Larastan
* Laravel Pint

The application's backend is already substantially implemented.

The purpose of this task is **not to rewrite Evently**.

The purpose is to make the two physical event-door workflows feel intentional, modern, responsive, usable, and professionally designed:

1. Organizer scans attendee tickets at the door.
2. Attendee presents a ticket QR at the door.

This task also includes a very small final correctness cleanup discovered during the previous hardening pass.

---

# 2. Important Scope Constraint

This is an MIT/project implementation.

Do NOT spend time on deployment or real-device network infrastructure.

Specifically, this task must NOT implement or investigate:

* HTTPS configuration
* Herd secure
* Herd share
* LAN access
* local DNS
* deployment
* hosting
* Docker deployment
* production domains
* SSL certificates
* PWA
* service workers
* offline synchronization
* offline check-in architecture
* device brightness control
* Screen Wake Lock
* real payment providers
* Stripe integration
* CMI integration
* Payzone integration
* push notifications

The UI must still be responsive and professionally usable at phone viewport sizes, but testing may be done using browser responsive/device emulation.

Do not turn infrastructure questions into blockers.

---

# 3. Source of Truth

Before implementation, read completely:

* this file
* `AGENTS.md`
* `.opencode/team-notes.md`

Then inspect the real current implementation.

Do not rely on assumptions from older branches.

Verify every statement below against the current repository before changing code.

---

# 4. Current UX Diagnosis

The current implementation has a solid backend but the door experience was originally designed like a desktop dashboard.

## Organizer check-in

Current page:

`resources/views/organizer/check-in/index.blade.php`

currently uses a desktop-oriented layout similar to:

```css
grid-template-columns: minmax(0, 1fr) 340px;
```

This means:

```text
Desktop
────────────────────────────────────────
Scanner                     Door stats
Scanner                     Recent scans
Manual input
────────────────────────────────────────
```

At phone width, those areas do not become a purpose-built door interface.

The page should instead prioritize:

```text
Phone
─────────────────────
Event / Door mode
─────────────────────
CAMERA

CAMERA

CAMERA
─────────────────────
Scan state/result
─────────────────────
Manual code fallback
─────────────────────
214 / 246 checked in
████████████████░░
Stats / recent scans
─────────────────────
```

The camera is the primary task.

Stats are secondary.

Navigation is tertiary.

---

# 5. Critical Functional Scanner Bug

This must be fixed as part of the task.

Current QR scanner behavior uses:

`autoStopOnSuccess = true`

inside:

`resources/js/qr.js`

After a successful QR decode, scanning stops.

The check-in page processes the ticket, but currently does not restart the scanner afterward.

That means the intended door workflow can become:

```text
Scan ticket 1
↓
success
↓
scanner stops
↓
cannot naturally scan ticket 2
```

This is unacceptable for a door workflow.

## Required behavior

The scanner must support continuous event-door operation:

```text
READY
↓
detect QR
↓
PROCESSING
↓
server response
↓
SUCCESS / WARNING / ERROR
↓
brief result feedback
↓
READY FOR NEXT TICKET
↓
scanner resumes
```

The same ticket must not repeatedly trigger while one request is processing.

Implement an explicit scanner/request state machine rather than relying on accidental timing.

Suggested states:

```text
idle
starting
ready
processing
success
warning
error
camera_unavailable
```

Names may differ.

The important requirement is deterministic behavior.

---

# 6. Prevent Duplicate Requests

The current page disables the manual submit button, but the entire scan workflow should have a shared in-flight guard.

While a check-in request is processing:

* ignore additional decoded QR values
* prevent duplicate manual submissions
* show a processing state
* do not fire concurrent check-in requests from the same page

After processing:

* clear the manual input
* restore controls
* resume scanning
* return to ready state

Do not weaken the server-side atomic double-scan protection.

Client-side guards are UX enhancements, not security guarantees.

---

# 7. Remove Bad Mobile Autofocus Behavior

The manual ticket-code input currently uses:

```html
autofocus
```

That is appropriate for keyboard-oriented desktop operation but bad for a camera-first mobile workflow because mobile browsers may immediately open the on-screen keyboard.

Remove unconditional autofocus.

Desired behavior:

## Desktop/manual fallback

The user can explicitly select manual input and type immediately.

## Mobile/camera mode

The camera remains visually dominant and the keyboard does not appear unexpectedly.

If manual-entry mode is opened, focus the field then.

---

# 8. Correct Camera State Messaging

Do not display:

`Camera active`

until the camera actually starts successfully.

The current placeholder/viewfinder should have state-aware messaging.

Examples:

### Starting

`Starting camera…`

### Ready

`Ready to scan`

### Processing

`Checking ticket…`

### Success

`Checked in`

### Already used

`Already checked in`

### Invalid

`Ticket not valid`

### Camera unavailable

`Camera unavailable — enter the ticket code manually.`

Use actual state.

Do not lie to the user.

---

# 9. Organizer Door Mode — UX Goal

The check-in screen should feel like a dedicated operational tool.

It should NOT feel like another organizer analytics page.

Primary design priorities:

1. speed
2. clarity
3. large camera
4. unmistakable result states
5. quick recovery
6. large touch targets
7. low visual clutter

---

# 10. Organizer Door Mode — Mobile Header

For phone-sized layouts, simplify the top of the check-in experience.

Do not force the entire organizer desktop navigation to compete with the scanner.

A focused header may contain:

```text
← Events                  Door mode
Tech Conference 2026
Casablanca · Tonight
```

or an equivalent compact design consistent with Evently.

Requirements:

* easy way back to event picker
* event title visible
* current context obvious
* no unnecessarily large desktop navigation occupying the main viewport
* retain access to the normal application without creating a separate application

Do not rewrite the entire global navigation system.

Implement the smallest reusable focus/door mode needed.

---

# 11. Camera Area

The scanner should be the visual anchor.

On mobile:

* full available content width
* appropriate 4:3 or near-square scanning area
* maintain a clear QR target frame
* no tiny camera caused by adjacent columns
* rounded Evently surface
* strong dark camera background
* subtle scan line
* readable scanner-state label
* no excessive decoration

Suggested approximate mobile composition:

```text
┌────────────────────────────┐
│                            │
│                            │
│       ┌────────────┐       │
│       │            │       │
│       │  QR target │       │
│       │            │       │
│       └────────────┘       │
│                            │
│       Ready to scan        │
└────────────────────────────┘
```

Do not hard-code a fixed 250px scan box that exceeds small available viewport dimensions.

Make QR scan-box sizing responsive where required.

Preserve the existing preference for the environment/back camera.

Do not rewrite `html5-qrcode` integration unless required for correct behavior.

---

# 12. Scan Result UX

Current small inline banners are not strong enough for a door operator.

After a scan, the result must be immediately recognizable even with peripheral vision.

## Successful check-in

Use:

* strong green state
* clear check icon
* `Checked in`
* attendee name
* ticket type
* optional time

Example:

```text
✓ CHECKED IN

Sara El Amrani
VIP Ticket · 19:42
```

## Already used

Use warning/amber rather than treating it identically to an invalid ticket.

Example:

```text
! ALREADY CHECKED IN

This ticket was used at 19:31.
```

## Cancelled / invalid / not found

Use error/red.

Example:

```text
✕ TICKET NOT VALID

No ticket found for this event.
```

## Network/server failure

Use a distinct retry-oriented message.

Do not clear important error feedback instantly.

Result states should be:

* readable
* large
* close to the scanner
* accessible
* not dependent only on color

Use text + icon + color.

---

# 13. Result Accessibility

The result region must support assistive technologies.

Use appropriate:

* `aria-live`
* `role="status"` or `role="alert"` depending on severity
* semantic labels
* focus behavior where appropriate

Do not aggressively steal focus after every scan.

Manual code input must have a visible or accessible label.

Buttons must have meaningful accessible names.

---

# 14. Touch Targets

Interactive elements used during the door flow should have approximately:

`44px+`

minimum practical touch size.

Examples:

* back/change-event button
* manual entry toggle
* check-in submit
* dismiss action
* stats disclosure
* recent-scans disclosure

Avoid small icon-only hit areas.

---

# 15. Manual Entry UX

Manual entry is a fallback, not the visual primary action.

On mobile, consider an expandable section:

```text
Camera unavailable?
[ Enter ticket code manually ]
```

Expanded:

```text
Ticket code
[ T-XXXXXXXXXX          ]

[ Check in ]
```

On wider layouts it may remain more directly visible.

Do not hide manual fallback so deeply that staff cannot recover when camera scanning fails.

Use responsive behavior, not two completely separate implementations.

---

# 16. Door Stats

Current stats consume a permanent 340px sidebar.

Redesign them as secondary contextual information.

Important metrics:

* checked in
* issued
* remaining
* progress percentage

On mobile, consider a compact summary such as:

```text
214 / 246 checked in                  87%
██████████████████████░░░
32 remaining
```

Then put detailed information behind an optional disclosure/card.

On desktop, the current two-column concept may remain if improved.

Mobile does not need three giant number rows.

---

# 17. Recent Scans

Recent scans should support the operator, not distract them.

Improve information hierarchy.

Useful recent-scan row:

```text
● Sara El Amrani
  VIP Ticket                     19:42
```

Currently initial server-rendered scans only eager-load the ticket type.

If displaying holder names requires safely eager-loading the ticket owner/user relation, make that small query improvement.

Avoid N+1 queries.

Keep recent scans to a small bounded number.

On mobile:

* collapsible/disclosure is acceptable
* do not push the camera below a long activity list

---

# 18. Event Picker UX

File:

`resources/views/organizer/check-in/picker.blade.php`

The existing grid is a reasonable foundation.

Improve it specifically for door usage.

## Priorities

Published upcoming/current events should be easiest to find.

Cards should emphasize:

* event title
* date/time
* location/city
* status
* check-in progress
* primary `Open check-in` / `Open door mode` CTA

Do not display three vertically repetitive metrics if a compact progress summary communicates the same information better.

Suggested card hierarchy:

```text
TECH CONFERENCE 2026
Today · 19:00
Casablanca

214 / 246 checked in
████████████████░░ 87%

[ Open door mode ]
```

Cancelled and non-published events should remain clearly unavailable.

Mobile:

* one card per row
* generous touch CTA
* no horizontal overflow

---

# 19. Attendee Ticket Experience — Separate Two Jobs

The current `My tickets` page mixes:

1. finding/managing tickets
2. physically presenting a QR to staff

Those are different user jobs.

Do not try to make every list item a giant QR.

Instead:

## My Tickets

Optimized for:

* browsing tickets
* seeing event
* seeing status
* finding the correct ticket
* opening presentation mode

## Ticket Presentation

Optimized for:

* one ticket
* one large QR
* very high scanability
* minimal distractions

---

# 20. Add Dedicated Ticket Presentation Route

Create a proper attendee ticket presentation endpoint.

Recommended route shape:

```text
GET /tickets/{ticket}
```

Name appropriately, for example:

```text
tickets.show
```

Use the existing authenticated user route group.

## Authorization

A user must NEVER be able to view another user's ticket by changing the URL.

Use server-side ownership enforcement.

Prefer non-disclosing `404` behavior for foreign tickets where it fits the application's current ownership conventions.

Do not rely on the My Tickets page hiding links.

Add regression tests for:

* owner can view ticket
* another attendee cannot view ticket
* guest cannot view ticket
* invalid ticket ID returns expected not-found behavior

---

# 21. Ticket Presentation Page

Create a dedicated presentation page.

Possible file:

`resources/views/tickets/show.blade.php`

or equivalent.

## Primary mobile design

```text
← My tickets

TECH CONFERENCE 2026
Saturday, Aug 15 · Casablanca

┌──────────────────────────────┐
│                              │
│                              │
│          LARGE QR            │
│          280–320px           │
│                              │
│                              │
└──────────────────────────────┘

T-AB12CD34EF

VIP TICKET
VALID

Ticket 1 of 3

[ Previous ]           [ Next ]
```

Exact dimensions should be responsive.

Do not make a 320px QR overflow a 320px viewport.

Use CSS such as:

```text
min()
clamp()
max-width
aspect-ratio
```

appropriately.

---

# 22. QR Presentation Design

QR codes should always have:

* white background
* sufficient quiet zone
* strong dark modules
* no transparent decorative layer over the code
* no gradient inside the code
* no animation affecting the code
* high contrast
* generous surrounding whitespace

The QR is functional first.

Branding belongs around it, not inside it.

---

# 23. Ticket Status Behavior

The presentation page must clearly communicate ticket status.

## Valid

Strong positive state:

`VALID · Ready for check-in`

## Used

Clearly indicate:

`USED`

and when checked in if available.

The QR may remain visible as a record, but the UI must make clear it should not be accepted again.

## Cancelled

Clearly indicate:

`CANCELLED`

Do not visually present a cancelled ticket like a valid door pass.

Use status, icon, and text — not only color.

---

# 24. Ticket Code Fallback

Display the human-readable ticket code beneath the QR.

Example:

`T-ABC123XYZ0`

This helps manual check-in if scanning fails.

Make it:

* easy to read
* sufficiently large
* high contrast
* letter-spaced carefully if beneficial

Do not mutate the actual code.

---

# 25. Multiple Tickets

A booking/event may contain multiple attendee tickets.

Presentation mode should allow moving between tickets without returning to the list each time.

Provide:

* previous
* next
* ticket position

Example:

`Ticket 2 of 4`

Only navigate among tickets the authenticated user owns.

Prefer tickets belonging to the same event/group when entering from an event ticket group.

Do not expose foreign IDs.

---

# 26. My Tickets List Redesign

Current QR size is 104×104 and each row includes QR + metadata + status.

Improve the page hierarchy.

The list should answer:

* Which event?
* When?
* Where?
* Which ticket?
* Is it valid?
* How do I show it?

A good compact mobile item might be:

```text
VIP Ticket                              VALID
T-ABC123XYZ

[ Show ticket ]
```

The event accordion/group remains a good pattern.

Do not necessarily remove small QR previews if they remain useful on wider layouts, but the primary door action must be `Show ticket`.

On mobile, presentation CTA is more important than a tiny decorative QR.

---

# 27. Remove False Offline Claim

The current page says:

`Works offline.`

There is no service worker/offline application architecture.

Remove that claim.

Do not implement PWA/offline support in this task.

Use accurate copy such as:

`Open a ticket to show its QR at the door.`

---

# 28. Booking Detail QR

`resources/views/bookings/show.blade.php`

currently renders QR codes around 60×60.

Do not try to turn the booking detail page into the final door screen.

Instead:

* keep booking detail informational
* make ticket entries link to the dedicated presentation page
* use `Show ticket` as the clear action
* QR preview may remain small or be removed if redundant

This avoids maintaining multiple competing ticket-presentation UIs.

---

# 29. Mobile Navigation Strategy

Do NOT redesign the entire Evently navigation in this branch.

The existing shell already provides basic responsive safeguards.

Instead implement focused behavior where necessary.

## Organizer check-in

Door mode should minimize nonessential navigation on narrow screens.

## Ticket presentation

Presentation mode should minimize header/footer distractions on narrow screens.

Possible implementation:

* a reusable `focusMode`/`presentationMode` layout option
* page-specific focused shell behavior
* equivalent low-risk solution

Do not duplicate the full `<head>`/theme system into multiple unrelated layouts unless genuinely necessary.

Do not break desktop navigation or existing header tests.

---

# 30. General Responsive CSS Strategy

A major reason the current feature pages are difficult to adapt is heavy inline styling.

For the pages touched by this task, move important responsive layout rules into maintainable CSS classes.

Use:

`resources/css/app.css`

or clearly scoped page styles consistent with the repository conventions.

Do not rewrite every Evently view.

Scope refactoring to:

* check-in picker
* check-in door screen
* tickets index
* ticket presentation
* booking-ticket presentation link
* small shell/focus-mode changes if necessary

Use descriptive classes such as:

```text
door-page
door-layout
door-scanner
door-state
door-stats
ticket-list
ticket-card
ticket-present
ticket-present__qr
```

Naming may differ.

Avoid replacing inline styles merely for stylistic purity.

Move styles where it enables responsiveness and maintainability.

---

# 31. Responsive Breakpoints

Design and verify at least:

```text
375px
390px
430px
768px
1024px
desktop >= 1200px
```

The exact CSS breakpoints may differ.

Requirements at narrow widths:

* no horizontal page overflow
* camera is not squeezed
* QR does not overflow
* buttons remain reachable
* event titles truncate/wrap appropriately
* stats stack correctly
* nav does not cover content
* presentation page remains centered
* manual input remains usable
* status pills do not force broken widths

---

# 32. Visual Design Direction

Keep the established Evently visual identity:

* Plus Jakarta Sans
* blue primary
* cyan accent
* soft light background
* white surfaces
* dark mode support
* rounded cards
* restrained shadows
* strong status colors

Do not introduce an unrelated neon/gaming design.

Door mode should feel:

* confident
* fast
* clean
* operational
* premium
* calm under pressure

Ticket presentation should feel:

* trustworthy
* simple
* premium
* highly scannable

---

# 33. Dark Mode

All new views must work in existing dark mode.

Important exception:

The actual QR canvas/surface must remain white for scan reliability.

Do not make the QR background dark just because the application theme is dark.

Status colors must retain readable contrast.

---

# 34. Motion

Use motion only where it communicates state.

Allowed examples:

* subtle scanning line
* short success appearance
* progress transitions
* disclosure chevron

Do not create long animation sequences that slow door work.

Respect:

```css
@media (prefers-reduced-motion: reduce)
```

The project already has reduced-motion handling.

Preserve it.

---

# 35. No Decorative Overload

Avoid:

* giant hero blocks
* marketing illustrations
* excessive gradients
* glassmorphism everywhere
* large empty decorative cards
* tiny low-contrast text
* multiple competing CTAs
* unnecessary dashboard statistics in door mode

This is operational UI.

Functionality and hierarchy come first.

---

# 36. Final Core Hardening Correction — Booking Expiration

Before or alongside the UI implementation, fix the remaining booking-expiration race correctly.

Current command:

`app/Console/Commands/ExpireBookings.php`

still:

1. selects candidate rows
2. determines expired IDs
3. later updates by ID

The final UPDATE must not be able to overwrite a booking that became confirmed between selection and update.

Use authoritative concurrency control.

Preferred approach:

* transaction
* row lock using `lockForUpdate()` on each/chunk of target bookings
* re-check:

  * status is pending
  * expires_at is still expired
* expire only while holding the lock
* cancel pending payments/tickets consistently inside the transaction

`BookingService::confirmPayment()` already uses booking row locking.

The expiration process should serialize using the same authoritative booking row.

Required invariant:

```text
If confirmation gets the lock first:
booking remains confirmed.

If expiration gets the lock first:
booking becomes expired and later confirmation fails.
```

Also fix command reporting.

Current count increments using the number of candidates, which can differ from the number actually expired.

Report the number of rows that truly transitioned to `expired`.

Add/strengthen tests.

Do not claim a concurrency test simulates "mid-run" if it only changes status before the command starts.

---

# 37. Remove False Stripe Branding

The application currently uses mock payments but the footer still says:

`Powered by Stripe`

Remove it.

Use accurate neutral footer copy.

Example:

```text
Prices in MAD
```

No payment-provider branding should appear until a real provider exists.

Do not add Stripe.

---

# 38. Do Not Regress Existing Backend Security

Preserve all previous hardening work, including:

* scoped TicketType relationships
* authorization
* idempotent booking creation
* booking capacity locking
* ticket atomic check-in
* AI durable inputs
* AI retry semantics
* AI context limits
* payment mock gate
* date invariants
* ticket type invariants
* pagination bounds

Do not weaken backend rules for UI convenience.

---

# 39. Check-In Backend Consistency

Review whether check-in page/scan endpoints consistently enforce the event lifecycle expected by the picker.

The picker says only published events can be checked in.

Direct URLs must not accidentally bypass the intended business rule.

If the current event lifecycle makes this impossible already, document it.

If a real inconsistency exists, fix it with tests.

Do not invent new event statuses.

---

# 40. Testing Requirements

Add regression tests for backend behavior and rendered UX contracts where appropriate.

At minimum cover:

## Ticket presentation

* owner can open ticket
* foreign ticket cannot be viewed
* guest requires authentication
* valid ticket presentation renders large QR container
* ticket code appears
* ticket status appears
* event context appears
* navigation among owned tickets does not expose foreign tickets

## Ticket list

* Show ticket links point to owned ticket presentation
* invalid/used/cancelled status remains represented correctly
* false `Works offline` copy is removed

## Check-in

Preserve existing tests for:

* valid scan
* not found
* cancelled
* already used
* atomic double scan
* admin permissions
* user denial
* event picker

Add tests for any changed server behavior.

For JS scanner lifecycle, use the project's existing JS testing infrastructure if one exists.

If no JS unit-test setup exists, do NOT introduce a large JS testing framework solely for one file.

Instead:

* keep scanner state logic small and deterministic
* verify frontend build
* perform browser/manual responsive verification if a browser tool is available
* document the exact scanner lifecycle checked

## Expiration

Add strong tests proving:

* confirmed booking remains confirmed when expiration runs
* expired booking cannot later be confirmed
* only actually expired rows count toward command output
* payment/ticket states stay consistent

---

# 41. Browser / Responsive Verification

If a browser inspection tool is already available, use it.

Verify the changed screens at:

* 375px
* 390px
* 430px
* 768px
* desktop

Do not install infrastructure or deployment tooling.

Check:

* no horizontal overflow
* scanner full width
* results visible without awkward scrolling
* manual input usable
* stats secondary
* QR large and centered
* ticket presentation hierarchy
* dark mode
* desktop behavior

If no browser tool is available, do code-level responsive review and document that browser rendering was not available.

Do not fabricate visual verification.

---

# 42. Performance

Do not make QR libraries global.

The existing lazy-loading approach in `qr.js` is good.

Preserve:

* lazy `qrcode` loading
* lazy `html5-qrcode` loading
* loading only on QR-related pages

Adding a ticket presentation page should continue using the same lazy QR module.

Do not bundle these libraries into every Evently page.

---

# 43. Query Performance

If recent-scans UI now needs attendee names, eager-load them safely.

Avoid:

```text
recent scans
→ one query per ticket user
```

Use eager loading.

Ticket presentation should load only necessary related data.

Do not introduce unbounded ticket queries.

---

# 44. Security

Audit all new routes and interactions.

Especially:

* ticket ownership
* organizer event ownership
* admin check-in behavior
* ticket IDs in next/previous navigation
* escaped JS-injected strings
* CSRF
* manual check-in validation

Do not put private ticket data into pages another user can load.

Do not trust client state.

---

# 45. Coding Style

Follow current Evently/Laravel conventions.

Prefer:

* thin controllers
* Form Requests where validation justifies one
* server-side authorization
* Blade for the current UI
* Alpine/vanilla JS for lightweight interaction
* CSS classes for responsive layouts

Do not introduce:

* React
* Vue
* Inertia
* another CSS framework
* another icon library unless already present and necessary

---

# 46. File Organization

Expected files that may be touched include, but are not limited to:

```text
resources/views/organizer/check-in/index.blade.php
resources/views/organizer/check-in/picker.blade.php
resources/views/tickets/index.blade.php
resources/views/tickets/show.blade.php
resources/views/bookings/show.blade.php
resources/views/layouts/app.blade.php
resources/js/qr.js
resources/css/app.css
app/Http/Controllers/User/TicketController.php
app/Http/Controllers/Organizer/CheckInController.php
app/Console/Commands/ExpireBookings.php
routes/web.php
tests/Feature/CheckInTest.php
tests/Feature/TicketTest.php
tests/Feature/ExpireBookingsTest.php
```

This is not permission to change every file listed unnecessarily.

Inspect first.

---

# 47. Team Blackboard

Update:

`.opencode/team-notes.md`

The old July UI-port goal is historical.

The Current Goal must clearly state:

```text
Implement Evently mobile door experience and attendee ticket presentation according to docs/prompt.md.
```

Also explicitly state:

```text
NO HTTPS / deployment / LAN / PWA work in this phase.
```

Do not allow old historical directives to override this task.

---

# 48. Baseline Verification

Before modifying code, record:

```bash
git status
git branch --show-current
php artisan test --compact
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build
```

Do not overwrite unrelated user changes.

---

# 49. Implementation Sequence

Recommended sequence:

## Phase A — Final correctness cleanup

* booking expiry/payment serialization
* actual expired count
* tests
* false Stripe footer

## Phase B — Shared responsive/focus foundations

* page-specific responsive CSS
* optional focus/door presentation shell behavior
* preserve desktop navigation

## Phase C — Organizer door experience

* event picker polish
* camera-first layout
* scanner state machine
* continuous scanning
* processing lock
* manual fallback
* strong result states
* compact stats
* recent scans

## Phase D — Attendee ticket presentation

* secure show route
* dedicated page
* large QR
* code fallback
* ticket status
* previous/next owned tickets
* My Tickets CTA
* booking detail CTA

## Phase E — Accessibility/responsiveness

* touch sizes
* aria-live
* labels
* dark mode
* mobile layouts

## Phase F — Full verification

* targeted tests
* entire test suite
* Pint
* PHPStan
* frontend build
* responsive browser review if available

---

# 50. Git Quality

Use the branch:

`feat/mobile-door-experience`

Suggested logical commits:

```text
fix: finish booking expiry concurrency hardening

feat: add responsive organizer door mode

fix: make QR scanner reusable across consecutive scans

feat: add attendee ticket presentation

refactor: improve QR feature responsive styles

test: cover door and ticket presentation flows

docs: update Evently door experience notes
```

Exact grouping may differ.

Do not merge to `main`.

Do not push unless explicitly instructed.

---

# 51. Required Final Verification

Run:

```bash
composer dump-autoload
php artisan test --compact
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build
```

If formatting changes are required:

```bash
vendor/bin/pint
vendor/bin/pint --test
```

Report exact results.

---

# 52. Final UX Acceptance Criteria

## Organizer — 375px phone viewport

Must have:

* no horizontal overflow
* large full-width camera
* no permanent 340px sidebar
* no keyboard opening automatically
* prominent ready/processing/result state
* manual fallback available
* stats compact
* large touch controls
* repeated ticket scanning works without page refresh

## Organizer — desktop

Must retain:

* comfortable scanner size
* useful stats
* recent scans
* manual fallback
* existing Evently visual language

## Attendee — 375px phone viewport

Must have:

* clear My Tickets hierarchy
* prominent Show ticket action
* dedicated ticket view
* very large scannable QR
* ticket code
* event information
* status
* no overflowing QR
* easy previous/next navigation when applicable

## Dark mode

All surrounding UI works correctly.

QR surface remains white.

---

# 53. Definition of Done

This task is complete only when:

* booking expiration can no longer overwrite a legitimately confirmed booking
* expiration command count is accurate
* false Stripe branding is gone
* mobile check-in no longer uses a squeezed desktop two-column layout
* the scanner works for consecutive tickets
* duplicate client requests are guarded
* manual input no longer autofocuses on page load
* camera messaging reflects actual state
* scan results are operationally obvious
* stats are secondary on mobile
* recent scans remain useful
* event picker is responsive
* attendee has a secure dedicated ticket presentation route
* foreign tickets cannot be presented
* presentation QR is large and responsive
* ticket code is visible
* used/cancelled tickets are clearly differentiated
* previous/next owned ticket navigation works where appropriate
* My Tickets links into presentation mode
* booking detail links into presentation mode
* false offline claim is removed
* relevant changed views work on narrow and desktop layouts
* dark mode remains usable
* QR dependencies remain lazy-loaded
* existing backend hardening remains intact
* full Pest suite passes
* Pint passes
* PHPStan passes
* npm build passes

---

# 54. Required Final Report

At completion return:

## Branch

Exact branch.

## Status

`COMPLETE`, `PARTIAL`, or `BLOCKED`.

## Baseline

Report initial:

* tests
* Pint
* PHPStan
* build

## UX Problems Verified

For each:

```text
Problem
Evidence
Fix
```

## Organizer Door Mode

Explain:

* responsive layout
* scanner lifecycle
* scan feedback
* manual fallback
* stats
* recent scans
* mobile behavior

## Attendee Ticket Presentation

Explain:

* route
* authorization
* QR design
* status
* navigation
* list integration

## Core Correctness Fix

Explain exactly how booking expiration and payment confirmation now serialize safely.

Do not just say "race fixed."

Describe the locking/state transition.

## Accessibility

List improvements.

## Responsive Verification

List viewport sizes actually checked.

Do not claim sizes you did not render/test.

## Tests

List changed/added tests and exact results.

## Files Changed

Group into:

* backend
* views
* JavaScript
* CSS
* tests
* documentation

## Verification

Exact results for:

```text
composer dump-autoload
Pest
Pint
PHPStan
npm build
```

## Remaining Limitations

Do not mention deployment/HTTPS as unfinished work for this MIT phase.

Only report limitations relevant to the implemented application.

## Final Git State

Report:

```bash
git status
git branch --show-current
```

Do not push or merge.
