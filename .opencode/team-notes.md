# Team Blackboard

Shared thinking space for the 3-agent team: **build** (orchestrator), **mimo**, **big-pickle**.

Rules:
- Read this file before starting any task.
- Append your updates under the matching section — never rewrite others' entries.
- Keep entries short: one or two lines each.

## Current Goal

Evently project base restored after miscommunication cleanup (build, 2026-07-31).
**User's intent:** keep the project setup, remove only the migrations & feature implementations.

## Ideas

_Team members append ideas here._

## Decisions

- Three-agent team active: build (orchestrator) + mimo + big-pickle, dispatched in parallel via Task tool.
- IMPORTANT: always present a plan and get user approval BEFORE dispatching agents or writing app code.
- Stack: Laravel 12.64 + Blade + Livewire 4 + Breeze (blade auth) + Vite + Tailwind 4. MySQL db `evently` (root / **see local .env — never commit secrets**). One codebase.
- Only framework migrations exist (users, cache, jobs). NO custom migrations/models/features.

## Progress

- build: Laravel scaffold restored, Livewire + Breeze installed, npm build ok, `evently` DB recreated, framework migrations migrated.
- build: Pest 3.8 installed (pest:install done, 25 tests passing). laravel/boost 2.4 installed + `boost:install` ran (MCP servers added for OpenCode/Cursor/Codex).

## Open Questions

- What should we build? Awaiting user's direction (plan-first this time).
