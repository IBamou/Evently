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

## CI fix (2026-08-09, WSL session)
- **GitHub CI was red**: 5 NewsletterTest failures, `Route [newsletter.store] not defined`. Root cause: commit `0ae4250` (DTO rename) accidentally deleted the newsletter route + controller import from `routes/web.php` (confirmed via `git show 0ae4250 -- routes/web.php`). Controller/model/migration/tests all still existed.
- **Fixed**: re-added `use App\Http\Controllers\Public\NewsletterController;` and `Route::post('/newsletter', ...)->name('newsletter.store');` in public routes. Verified: NewsletterTest 5 passed, full suite **416 passed (1218 assertions)** via sail. Committed locally, NOT pushed (user's choice — Windows copy/CI will pull later).

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