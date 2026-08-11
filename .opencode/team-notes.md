# Team Notes — Evently

## Setup / Environment
- Two parallel copies of the app: live one served by `php artisan serve` on **Port 8080 (http://localhost:8080)**, source of truth for browser verification; and Windows repo copy at `C:\Users\Simplon\Herd\Evently` (Bellbird/FileMaven repo w/ staged changes).
- **Every file edit must be applied to BOTH copies**: 1) `\\wsl.localhost\Ubuntu\home\simplon\projects\Evently\...` (live), 2) `C:\Users\Simplon\Herd\Evently\...` (repo).
- Vite dev server is running on Windows (`npm run dev`), hot-reloads the live page. No build needed.
- Browser DevTools (chrome-devtools MCP) attached to live site; use for verification.

## Project context
- Laravel (PHP 8.4) + Livewire v4 + Tailwind v3. Pest tests. Laravel/Herd not used for serving (custom 8080 setup).
- `home.blade.php` = single landing page: hero search (GET form → `?search=`), featured marquee section, All events section (id `all-events`, sidebar filters + results grid), newsletter.
- No learn-more modal yet; filters/venue panel done previously.

## Completed work
0. **Renamed `app/Dto/` → `app/DTOs/`** (user request): namespace `App\Dto\*` → `App\DTOs\*`; 5 files moved (SocialMarketing, MarketingResult, FieldTransformResult, EventDraftResult, Ai/AiProviderRoute); 2 consumers updated (AiGenerationService, AiProviderRouter). Classmap regenerated via `php composer.phar dump-autoload` (herd `composer.bat` wrapper HANGS — run the phar directly: `& "C:\Users\Simplon\.config\herd\bin\php84\php.exe" "C:\Users\Simplon\.config\herd\bin\composer.phar" dump-autoload`). Tests: 416 passed. Temp scripts removed.
1. **`?search=` filters homepage events** (GET to home; controller reads `search` query).
2. **Hero "Browse events" button** → routes to `/events`.
3. **Auto-scroll to results after search**: on load, if URL has non-empty `search` param, smooth-scroll to `#all-events` section (offset 84px for sticky header), respects `prefers-reduced-motion`.
   - ⚠️ Gotcha: script must run on `DOMContentLoaded` — it lives inside the featured section (before `#all-events` exists in DOM); direct IIFE found `null` target.
4. **Focus-ring fix on hero search card**: removed `outline:none` abuse + default Tailwind ring; uses `:focus-within` with primary-blue mix border + 4px soft ring (12% opacity). Verified live: focused shadow = `color(srgb 0.0823529 0.396078 0.847059 / 0.12) 0px 0px 0px 4px`.
   - ⚠️ When checking computed box-shadow via JS, browser serializes colors as `color(srgb ...)`, not `rgb(...)` — string matching on `0 0 0 4px` fails because computed values append `px` units and commas.

4. **Focus ring removed entirely from hero search bar** (user request): deleted `.needs-focus:focus-visible` rule (2px outline with `!important` that beat the input's inline `outline:none` — use case was the "rectangle" seen while typing) and the `.hero-search:focus-within` border+4px ring block. `.hero-search` now only has static border+shadow; no caret/outline change while typing. Verified: computed styles identical before/during/after focus.
   - ⚠️ Keyboard a11y trade-off: no visible focus indicator for Tab users on the search bar.

## Notes / gotchas
- Computed styles via `getComputedStyle` serialize with explicit `px` — don't compare CSS strings as written.
- Edited files are `.blade.php` views only; no PHP changes this round.

## Refactor (2026-08-09, WSL session)
- **Split Admin/EventController (424 lines)**: moved dashboard screen + chart/category helpers to new `Admin/DashboardController` (pure code movement, route `admin.dashboard` re-pointed, views untouched). `EventController` now = events list + reports tab helpers + moderation actions. Committed `a6eb4bd`, NOT pushed. Full suite 416 green, PHPStan clean, Pint clean.
- **FiltersAndSorts trait** (inspired by user's ForgeCoreApi project): new `app/Traits/FiltersAndSorts.php` with `applySearch/applySort/perPage` — search+sort+paginate blocks removed from Public/Admin/Organizer `EventController::index()`. Grouped search closure preserved (cannot bypass visibility). Sort keeps `-field` prefix convention; per_page unified to max(1,min(50)). PHPStan level 8 clean via `@template TQuery of Builder|Relation` (HasMany vs Builder union). Verified: 416 tests green + all 9 live-page filter combos return 200. Committed locally, NOT pushed.
- ⚠️ ForgeCoreApi's `FiltersAndSorts` uses ungrouped `orWhere` (can bypass other where clauses) — Evently version groups the search; the ForgeCoreApi one is the only spot to improve there, not copy.

## CI fix (2026-08-09, WSL session)
- **GitHub CI was red**: 5 NewsletterTest failures, `Route [newsletter.store] not defined`. Root cause: commit `0ae4250` (DTO rename) accidentally deleted the newsletter route + controller import from `routes/web.php` (confirmed via `git show 0ae4250 -- routes/web.php`). Controller/model/migration/tests all still existed.
- **Fixed**: re-added `use App\Http\Controllers\Public\NewsletterController;` and `Route::post('/newsletter', ...)->name('newsletter.store');` in public routes. Verified: NewsletterTest 5 passed, full suite **416 passed (1218 assertions)** via sail. Committed locally, NOT pushed (user's choice — Windows copy/CI will pull later).
- **PUSHED 2026-08-09 20:27 UTC**: `main` → origin (`0ae4250..2fb6d65`), tree clean, origin/main in sync. Live site up on :8080 (sail containers restarted; opcache bind-mount error fixed by `sail down && sail up -d` — Docker Desktop had a stale mount after a restart).
- **WSL git push gotcha (fixed)**: no credential helper → pushes hang silently. Cached GitHub creds live in Windows GCM. Correct WSL config:
  `git config --global credential.helper "!'/mnt/c/Program Files/Git/mingw64/bin/git-credential-manager.exe'"`
  ⚠️ The `!` prefix + single quotes around the path are MANDATORY: without `!` git treats it as a git subcommand; without quotes the space in "Program Files" breaks sh.


## Bug audit (2026-08-09)
- **Bug fixed**: `NewsletterController.php` + `NewsletterSubscription.php` were deleted in repo working tree (unstaged) while routes/views still referenced them → form would 500 on fresh checkout. Restored from git (byte-identical to live WSL copy). Full suite passed: 416 tests, 1218 assertions.
- **Cleanup**: stray SQLite DB file `evently` at repo root (leftover from old sqlite default; app now uses MySQL w/ Sail, tests use `:memory:`). Deleted from root (backup at `C:\Users\Simplon\AppData\Local\Temp\opencode\evently.sqlite.bak`), `/evently` added to `.gitignore` on BOTH copies.
- **Cleanup**: duplicate `FORWARD_DB_PORT=3307` line in `.env` removed (both copies).
- **PHPStan (level 8, app/)**: clean, no errors. Run with `--memory-limit=1G` (128M default crashes workers). PHPStan over `\\wsl.localhost` UNC path hangs → run on Windows copy natively.
- **Frontend build was stale** (manifest 8/6 < app.css 8/8): ran `npm run build` (54s) — production assets refreshed.
- **Current branch `migration/prepare` pending changes**: DTO rename (`app/Dto` → `app/DTOs`, both copies consistent), removal of `.cursor`/`.codex`/`3-agent-team-setup.md`/`design-evently-home.html`, opcache ini, css + blade tweaks, `bootstrap/app.php`, `routes/web.php`. Nothing committed yet.
## HANDOFF (2026-08-09) — continue work here in WSL
- Working dir: /home/simplon/projects/Evently. Git repo on branch test-maintain @ 0ae4250, tracks origin, CLEAN tree. origin/main identical (0ae4250) — force-updated today.
- App runs in Docker via Laravel Sail (NO php on Windows/WSL host): `sail artisan ...` (alias in ~/.bashrc). Stack: evently-laravel.test-1 (:8080), mysql (:3307), vite (:5173), mailpit (:8025), redis, queue, scheduler.
- Live site: http://localhost:8080 (Windows side reachable).
- GOTCHAS (learned the hard way):
  * opencode.json is TRACKED (committed) - 3-agent team + laravel-boost MCP. In WSL the boost MCP (php artisan boost:mcp) fails: no php on WSL host PATH. Run opencode from the Windows repo copy for boost MCP features, or accept MCP errors in WSL.
  * .env must be LF in WSL — NEVER edit it via PowerShell Set-Content over \\wsl.localhost (writes CRLF → breaks sail) and NEVER tr -d "\r" from PowerShell (quoting eats letters; it deleted every 'r' in .env once).
  * Edit WSL files with LF endings: [System.IO.File]::WriteAllText($path, $text, UTF8 no BOM), or use wsl bash tools with proper quoting.
  * Windows composer.bat wrapper hangs → use `sail composer` in WSL or herd composer.phar directly on Windows.
  * PHPStan hangs over UNC/WSL long paths → run in Windows repo copy natively: `& "C:\Users\Simplon\.config\herd\bin\php84\php.exe" vendor/bin/phpstan analyse --memory-limit=1G` (level 8: clean).
  * Tests: `php artisan test` on Windows copy (herd PHP + sqlite :memory:): 416 passed. Can also run via sail in WSL.
- Two copies: WSL = LIVE (source of truth for running), C:\Users\Simplon\Herd\Evently = Windows repo copy. Sync via git (commit+push / pull) instead of manual file copying.
- Bug audit done 2026-08-09: NewsletterController restored (was deleted unstaged), evently sqlite artifact removed+gitignored, .env deduped, build refreshed, Pint clean. sealed in commit 0ae4250.
- Month: no AI keys in the app DB; AI copilot uses OPENROUTER key in .env (kept; do not commit .env).

## Progress

- **AiGenerationService refactor (2026-08-10, WSL, big-pickle)**: extracted shared `runAgentFlow(Agent, string, AiProviderRoute, array, callable)` from the three run* methods in `app/Services/Ai/AiGenerationService.php` (400 → 399 lines). Pure boilerplate movement: `prompt()` call + `decodeStructuredResponse()` now live in one place; each run* method keeps its agent construction args and DTO-mapping closure verbatim (incl. category validation, `array_values()` casts, `$data['language'] ?? ($inputs['target_language'] ?? 'en')`). Added `use Laravel\Ai\Contracts\Agent;` (verified `prompt()` on the interface). Nothing else touched — no new files. Verified: `--filter=Ai` 96 passed (327 assertions), full suite **416 passed (1218 assertions)**, Pint clean, PHPStan level 8 clean. NOT committed. Only applied to WSL live copy — Windows repo copy (C:\Users\Simplon\Herd\Evently) still needs the same change via git sync.

## starts_to boundary fix + AI service dedupe (2026-08-10) — DONE, local only
- **Bug fixed**: `starts_to` filter compared `starts_at <= 'Y-m-d'` against midnight → events later on the `to` day were wrongly excluded (asymmetric with `starts_from` which includes its whole day). Fix: `whereDate('starts_at', '<=', ...)` in BOTH `app/Http/Controllers/Public/EventController.php` and `app/Http/Controllers/Organizer/EventController.php`. 4 regression tests added (PublicEventsTest x2, OrganizerEventsTest x2): same-day-afternoon event included, next-day event excluded.
- **AiGenerationService dedupe** (runAgentFlow, big-pickle's work): 400 → 399 lines, 61+/62-, verified 96 Ai tests + full suite.
- Verified: full suite **420 passed (1228 assertions)**, Pint clean (5 files), PHPStan level 8 clean.
- Committed locally: `2a2dcc2` (starts_to fix), `c91e887` (runAgentFlow), `f2d0eb1` (team notes). **NOT pushed** — `main` is ahead of origin by 5 (`a6eb4bd` Admin split, `284ae7e` trait, `2a2dcc2`, `c91e887`, `f2d0eb1`). Awaiting user's call on push.
- ⚠️ Gotcha (again): wsl.exe mangles multi-line quoted commands (nested" quotes in commit messages got split — a garbage commit message resulted once). ALWAYS write complex bash to `/tmp/*.sh` (write via `\\wsl.localhost\Ubuntu\tmp\`) and run `wsl -d Ubuntu -- bash /tmp/script.sh`. Also: `git` in PowerShell over the UNC path hits "dubious ownership" — run git from WSL.
- ⚠️ mimo returned an EMPTY result and made zero changes on its first assignment (starts_to fix) — orchestrator re-did it directly. Verify teammate task results against the working tree (git status) before trusting the report.

## Per-agent generation services isolation (2026-08-10) — DONE, local only
- **New folder `app/Services/Ai/GenerationServices/`** (namespace `App\Services\Ai\GenerationServices`, user request: "Ai/GenerationServices/"): each AI agent's generation logic is now its own isolated service class:
  - `EventGenerator` (abstract base): owns the shared flow — `generate(inputs, route, config)` template + private `runAgentFlow()` (prompt + timeout) + `decodeStructuredResponse()`.
  - `EventDraftGenerator` (loads categories once into a property, reused for agent context + category_id validation), `EventMarketingGenerator`, `EventFieldTransformGenerator` — each implements `buildAgent()` / `promptText()` / `mapResult()` only.
- `AiGenerationService` (stays at `app/Services/Ai/`, now ~250 lines): slimmed to orchestration — `execute()` (routing/fallback/retry), persistence, telemetry, `statusPayload()`, and a `generatorFor(operation)` match dispatch (`generate_draft` / `generate_marketing` / default→transform).
- Verified: `--filter=Ai` 96 passed (327 assertions), full suite **420 passed (1228 assertions)**, Pint clean (5 files auto-fixed), PHPStan level 8 clean. Committed `ddee9ca` (5 files, +262/−151). NOT pushed — main ahead of origin by 7 now: `a6eb4bd`, `284ae7e`, `2a2dcc2`, `c91e887`, `f2d0eb1`, `62cfb3e`, `ddee9ca`.

## Marketing feature REMOVED (2026-08-10) — DONE, local only
- **User removed the standalone AI marketing generation feature** (social post / email subject / email intro, `POST ai/event-marketing`, `GenerateEventMarketingAgent`). Deleted end-to-end: route, `EventAiController::generateMarketing()`, `GenerateEventMarketingRequest`, `GenerateEventMarketingAgent`, `MarketingResult` DTO, `EventMarketingGenerator`, `EventCopilotPrompts::generateMarketing()`, and the AI copilot drawer's Marketing TAB (Alpine state `marketingType/marketingData/marketingBusy/marketingError`, `canMarket/marketing/marketingKey/copyMarketing/setMarketingType/regenerateMarketing`, session keys, tab button + panel HTML, `$aiRoutes['marketing']`, header contract comment).
- **KEPT**: draft agent's bundled `marketing` output (part of generate_draft's structured-output contract — prompts schema + draft tests still assert it). `EventDraftResult` + `SocialMarketing` DTO untouched.
- 7 tests removed (ValidationTest ×5, StructuredOutputTest ×1, AsyncGenerationTest ×1). Suite: **413 passed (1203 assertions)**; `--filter=Ai` 89 passed. Pint clean (1 file auto-fixed), PHPStan level 8 clean, `view:cache` compiles.
- Committed `98832f0` (12 files, +3/−444). NOT pushed — main ahead of origin by 9: `a6eb4bd`, `284ae7e`, `2a2dcc2`, `c91e887`, `f2d0eb1`, `62cfb3e`, `ddee9ca`, `6906e6c`, `98832f0`.

## Design-pattern cleanup (2026-08-10) — DONE, local only

### 1. DashboardService
- **Extracted** `app/Services/Organizer/DashboardService.php`: `buildDashboardData(User): array` encapsulates all dashboard queries (stats, revenue, tickets, check-in rate, orders, chart series, category bars) + private `chartSeries()` and `categoryBars()` helpers.
- **Organizer/EventController** slimmed **348 → 190 lines**: `dashboard()` is now 5 lines (calls service, passes data to view). Removed 8 unused model/enum imports (`BookingStatus`, `PaymentStatus`, `TicketStatus`, `Booking`, `BookingItem`, `Payment`, `Ticket`, `Collection`). Added constructor-injected `DashboardService`.

### 2. DispatchGenerationAction
- **Extracted** `app/Actions/Ai/DispatchGenerationAction.php`: invokable action that owns feature-flag check, rate-limit guard, `AiGenerationService::create()` + `storeInputs()`, `ProcessAiGenerationJob::dispatch()`, and the 202 accepted JSON response.
- **EventAiController** slimmed **162 → 100 lines**: `generateDraft()` and `transformField()` now delegate to `($this->dispatchGeneration)($request, 'operation', fn ($r) => $r->validated())`. Removed `ProcessAiGenerationJob`, `FormRequest` imports. Added constructor-injected `DispatchGenerationAction`.

### 3. AiGenerationPolicy
- **New** `app/Policies/AiGenerationPolicy.php` with `view()` and `feedback()` methods — both check `$generation->user_id === $user->id`. Auto-discovered by convention (`AiGeneration` → `AiGenerationPolicy`).
- **EventAiController** `status()` and `recordFeedback()` now use `$this->authorize('view', $generation)` and `$this->authorize('feedback', $generationModel)` instead of manual checks.
- Tests updated: `AsyncGenerationTest` "returns 404 for status of another user's generation" now expects 403 ("This action is unauthorized.") instead of 404; `UsageRecordingTest` "returns 403 for feedback on another user generation" message updated to match Laravel's default.

### Verification
- **413 passed** (1203 assertions) — all green.
- **PHPStan level 8**: clean (required precise generic type on `Collection<int, array{...}>` in DashboardService return type).
- **Pint**: clean (minor fixes to new files).
- Committed `ac5ca29` (7 files, +291/−241). NOT pushed — main ahead of origin by 12 now.

## Prompts move + Schemas for AI response validation (2026-08-10) — DONE, local only

### Prompts relocated
- `app/Ai/Prompts/EventCopilotPrompts.php` → `app/Prompts/EventCopilotPrompts.php` (namespace `App\Prompts`). Both agents updated to `use App\Prompts\EventCopilotPrompts;`. Old directory removed.

### New `app/Schemas/` directory
- **`app/Schemas/Contracts/AiSchema.php`** — interface with two methods:
  - `schema(JsonSchema $schema): array` — returns the JsonSchema array for the AI provider's structured output (same as what agents used to define inline).
  - `validate(array $data): array` — validates and coerces decoded AI response data; throws `\RuntimeException` on missing/invalid required fields.
- **`app/Schemas/EventDraftSchema.php`** — implements AiSchema. `schema()` returns the draft JSON structure (title, description, category_id, marketing object, missing_information). `validate()` checks title/description are non-empty strings, coerces category_id to null on non-int, defaults marketing sub-fields to empty strings on missing, filters missing_information to strings only.
- **`app/Schemas/FieldTransformSchema.php`** — implements AiSchema. `schema()` returns content/language/warnings. `validate()` checks content/language are non-empty strings, filters warnings to strings only.

### Integration
- **Agents** (`GenerateEventDraftAgent`, `TransformEventFieldAgent`): `schema()` now delegates to `$this->aiSchema()->schema($schema)`. Added `aiSchema(): AiSchema` method returning the appropriate schema instance from the container.
- **EventGenerator**: `decodeStructuredResponse()` now accepts an `AiSchema` parameter and calls `$schema->validate($data)` after JSON decode (including markdown fence fallback). Added abstract `schema(): AiSchema` to the base class.
- **Concrete generators** (`EventDraftGenerator`, `EventFieldTransformGenerator`): each implements `schema()` returning its AiSchema. `generate()` passes the schema through `runAgentFlow()`.

### Verification
- **413 passed** (1203 assertions) — all green.
- **PHPStan level 8**: clean.
- **Pint**: clean.
- Committed `c258f1c` (9 files, +201/−24). NOT pushed — main ahead of origin by 14.

## Hardcoded auth check removal + authorize-first (2026-08-10) — DONE, local only
- **BookingPolicy::confirm()** added (owner-only check). `BookingController::confirmPayment()` uses `$this->authorize('confirm', $booking)`.
- **BookingController::show()** view variables use `$user->can('cancel')` and `$user->can('confirm')`.
- Removed redundant null-user + `abort(401)` guards from EventAiController and DispatchGenerationAction.
- All `$this->authorize()` calls placed as first line in their methods.
- Committed `568b7bb`.

## AI usage limiting REMOVED + codebase cleanup (2026-08-10) — DONE, local only

### AI daily usage limiting removed
- **Deleted** `app/Traits/HasAiGenerationUsage.php` (canRunAiGeneration, aiUsageToday, aiUsageThisMinute).
- **Stripped** rate-limit methods from `AiGenerationRecorder` (getDailyCount, incrementDailyCount, getMinuteCount, incrementMinuteCount, reserveGenerationSlot). Kept only `record()`, `recordFeedback()`, `getGenerationByPublicId()`.
- **Removed** `canRunAiGeneration()` guard from `DispatchGenerationAction` — now just creates record, persists inputs, dispatches job.
- **Removed** `daily_limit` + `per_minute_limit` from `config/ai-event-copilot.php` and `.env.example`/`.env`.
- **Removed** `ai_rate_limited` and `ai_daily_limit_reached` error messages from `AiGenerationService::errorMessageFor()` and `ai-copilot.blade.php`.
- **Deleted** `tests/Feature/Ai/RateLimitTest.php` (3 tests). Removed 2 rate-limit tests from `UsageRecordingTest` and 2 from `AsyncGenerationTest`.
- Removed `HasAiGenerationUsage` from `User` model. Removed unused `AiGenerationRecorder` import from `AsyncGenerationTest`.
- Committed `75c6a86`.

### Newsletter feature fully removed
- Deleted controller, model, migration, route, blade section, test. 5 tests removed.

### EnsureAiEnabled middleware
- New `app/Http/Middleware/EnsureAiEnabled.php` checks `config('ai-event-copilot.enabled')`. Registered as `ai.enabled` in `bootstrap/app.php`. Applied to AI routes group.

### FiltersAndSorts trait extended
- Added `applyFilters()` method for simple equality + closure-based filter maps.
- `applySearch()` now accepts closures for relation searches (e.g. `fn ($q, $s) => $q->orWhereHas(...)`).
- 8 controllers refactored to use traits with closures.

### PHPStan ignoreErrors for generic trait
- Added `phpstan.neon` ignoreErrors for `missingType.generics` and `argument.type` on `FiltersAndSorts.php` — PHPStan cannot resolve generic Builder|Relation types when a trait is expanded into consuming classes.

### Verification
- **400 passed** (1166 assertions).
- **PHPStan level 8**: clean (via ignoreErrors for trait generics).
- **Pint**: clean.
- Committed `c0bd5d2`. NOT pushed — main ahead of origin by 16.

## Simplification pass (2026-08-11) — DONE, pushed

### DRY patterns extracted
- **CheckInController::scanResponse()**: consolidated 5 identical scan response blocks (valid=false + JSON/redirect) into a single private method.
- **Admin/EventController::runAction()**: consolidated 4 identical try/catch/redirect blocks (publish, reject, cancel, restore) into a single helper.
- **Organizer/EventController::runAction()**: same pattern with `callable` to handle different action signatures (update, cancel, submit).

### Admin Dashboard deduplication
- **Admin/DashboardController** reduced from 166 lines to 22 lines: deleted duplicated `chartSeries()` and `categoryBars()` methods, now delegates to `DashboardService::buildDashboardData()` (null $user = platform-wide mode).
- **DashboardService** updated to accept nullable `?User $user` — null queries all events (admin), non-null scopes to user's events (organizer). Uses `->when()` for conditional scoping.
- **checkInRate** type aligned: `null` when no tickets (admin), `float` with 1 decimal otherwise.

### Verification
- **400 passed** (1166 assertions) — all green.
- **Pint**: clean (5 files auto-fixed).
- Committed `3ae39eb`. Pushed to origin.