# 3-Agent Team Setup for OpenCode

> How to set up a 3-agent team in **OpenCode**: one orchestrator (**build**) + two coding specialists (**mimo**, **big-pickle**) that work in parallel on the same project.
> This guide is based on a working setup (Evently project). Everything below is copy-paste.

---

## 1. What you get

- **build** — the primary agent. It talks to you, reads the shared blackboard, splits your task into parallel chunks, dispatches them to mimo + big-pickle, then merges and reviews their work.
- **mimo** — coding specialist. Implements its assigned chunk, reports back.
- **big-pickle** — coding specialist. Same role, different name/personality/model.

All three share a **blackboard file** (`.opencode/team-notes.md`) so they stay in sync without stepping on each other.

---

## 2. Requirements

- [OpenCode](https://opencode.ai) installed (CLI or desktop).
- Model provider access for the models you want to use (you can swap any model below).
- Node.js if you use MCP servers like `chrome-devtools` (optional).

---

## 3. File 1 — `opencode.json` (project root) — REQUIRED

Create this file in your project root. It defines the three agents and any MCP servers.

```jsonc
{
    "$schema": "https://opencode.ai/config.json",
    "model": "opencode/deepseek-v4-flash-free",
    "agent": {
        "build": {
            "mode": "primary",
            "model": "opencode/deepseek-v4-flash-free",
            "prompt": "You are the orchestrator of a 3-agent team (build = you, mimo, big-pickle). Your job: coordinate so the team works together fast and efficiently.\n\nRules:\n- Before a task, read .opencode/team-notes.md; after each round, update it with progress and decisions.\n- Split large tasks into parallel, non-overlapping chunks and dispatch them to mimo and big-pickle via the Task tool in a single message.\n- Merge, review, and sanity-check their results yourself before presenting to the user.\n- You talk to the user; teammates report only to you. Be concise in your reports."
        },
        "mimo": {
            "mode": "all",
            "model": "opencode/mimo-v2.5-free",
            "description": "Mimo team member: coding specialist. Work on assigned chunks, share ideas, report back",
            "prompt": "You are Mimo, a coding specialist and member of a 3-agent team (build orchestrates, big-pickle and you implement). Work together fast and efficiently.\n\nRules:\n- Before starting a task, read .opencode/team-notes.md to sync on the shared blackboard.\n- Do your assigned part completely and independently. Do not wait for other agents.\n- After completing your part, append a short entry to .opencode/team-notes.md under 'Progress' with what you did, decisions, and open questions.\n- Always finish your final message with: (1) what you built, (2) any issues, (3) suggested next steps. Be concise.\n- If a task is ambiguous, make a sensible choice and note it on the blackboard."
        },
        "big-pickle": {
            "mode": "all",
            "model": "opencode/big-pickle",
            "description": "Big Pickle team member: coding specialist. Work on assigned chunks, share ideas, report back",
            "prompt": "You are Big Pickle, a coding specialist and member of a 3-agent team (build orchestrates, mimo and you implement). Work together fast and efficiently.\n\nRules:\n- Before starting a task, read .opencode/team-notes.md to sync on the shared blackboard.\n- Do your assigned part completely and independently. Do not wait for other agents.\n- After completing your part, append a short entry to .opencode/team-notes.md under 'Progress' with what you did, decisions, and open questions.\n- Always finish your final message with: (1) what you built, (2) any issues, (3) suggested next steps. Be concise.\n- If a task is ambiguous, make a sensible choice and note it on the blackboard."
        }
    },
    "mcp": {
        // Example: Laravel Boost (only if your project is Laravel)
        "laravel-boost": {
            "type": "local",
            "enabled": true,
            "command": ["php", "artisan", "boost:mcp"]
        }
        // Example: browser debugging
        // "chrome-devtools": {
        //     "type": "local",
        //     "command": ["npx", "-y", "chrome-devtools-mcp@latest"],
        //     "enabled": true
        // }
    }
}
```

**Notes:**
- `build` uses `"mode": "primary"` — that's what makes build the agent you talk to. mimo and big-pickle use `"mode": "all"`.
- Swap the `model` values for whatever you have access to. The agent names can also be renamed, just keep the `Task`-tool naming consistent.
- The `mcp` block is optional. Remove it if you don't need MCP servers (they're project-specific).

---

## 4. File 2 — `.opencode/team-notes.md` — REQUIRED

This is the team's shared blackboard. Create `.opencode/team-notes.md` (the `.opencode` folder goes in your project root):

```markdown
# Team Blackboard

Shared thinking space for the 3-agent team: **build** (orchestrator), **mimo**, **big-pickle**.

Rules:
- Read this file before starting any task.
- Append your updates under the matching section — never rewrite others' entries.
- Keep entries short: one or two lines each.

## Current Goal

<What the team is working on right now, and the user's latest directive.>

## Ideas

_Team members append ideas here. (Analysis, risks, proposals before implementing.)_

## Decisions

<Decisions that were made, so everyone follows the same rules.>

## Progress

<Round-by-round log: who did what, verified with what commands.>

## Open Questions

<Anything waiting for the user or build to decide.>
```

---

## 5. File 3 — `AGENTS.md` (project root) — OPTIONAL but recommended

OpenCode reads `AGENTS.md` for project-wide instructions given to **every** agent. Useful for:
- Stack & versions (e.g., Laravel 12, Tailwind, Pest…)
- Code conventions (naming, structure, where files go)
- Verification rules (e.g., "run `pint --dirty` and `php artisan test` before finishing")
- "Search docs before changing code" rules

Your friend can copy a trimmed version of this project's `AGENTS.md` and adapt it.

---

## 6. How the workflow runs (the pattern that works)

1. **You ask build for something** (a feature, a fix, a port of a design…).
2. **build plans first** — reads `team-notes.md`, splits the work into **parallel, non-overlapping chunks**, and presents the plan for your approval.
3. **You approve** → build dispatches the chunks to **mimo** and **big-pickle in a single message** (both start at the same time).
4. **They implement independently**: each reads the blackboard, does its part, then appends a short "Progress" entry (what was built, decisions, open questions).
5. **build merges & reviews** their results, sanity-checks (runs tests/lint), fixes conflicts, and reports back to you.
6. Next round repeats — the blackboard keeps everyone's memory.

**Ground rules that keep it working:**
- Chunks must not overlap (e.g., "mimo = CSS + layout", "big-pickle = home page + routes").
- Teammates report only to build, never directly to you.
- Never rewrite others' blackboard entries — append only.
- build gets your approval before dispatching agents or writing app code.

---

## 7. Troubleshooting / tips

| Problem | Fix |
|---|---|
| Agents can't find `mimo` / `big-pickle` | Make sure `opencode.json` is in the **project root** and the file is valid JSONC. Restart OpenCode. |
| Wrong models | Change the `model` value per agent in `opencode.json`. |
| Agents step on each other | Split chunks by file/folder, not by feature. Update the blackboard more often. |
| build forgets the blackboard | The prompt rules are in `opencode.json` — they're instructions, not law. Remind build or strengthen the prompt. |
| MCP server errors | MCP blocks are per-project. Only include servers that exist in the project (e.g., Laravel Boost needs a Laravel app). |
| Works in one project, not another | `opencode.json`, `.opencode/team-notes.md` and `AGENTS.md` are all per-project files. Copy all three into the new project. |

---

## 8. Checklist for your friend

- [ ] `opencode.json` in project root (agents defined, models swapped)
- [ ] `.opencode/team-notes.md` created
- [ ] `AGENTS.md` adapted to their project (optional)
- [ ] MCP servers adjusted (Laravel Boost only for Laravel projects)
- [ ] OpenCode restarted, ask build to introduce itself and confirm it can see mimo + big-pickle
