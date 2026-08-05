# Evently remediation guide

## Summary

This document translates the audit findings into concrete remediation actions. The goal is not to rewrite the application from scratch, but to preserve what is already strong and improve the areas most likely to create production issues as the platform grows.

## Priority actions

### 1) Keep the booking safety invariant as absolute

The booking flow is the most important domain rule in the app.

What to preserve:

- transaction wrapping
- `lockForUpdate()` on relevant inventory rows
- server-side capacity validation
- idempotency key checks
- duplicate submission rejection before new records are created

Recommended standard:

- inventory checks must never live only in the UI
- all checkout requests should be validated server-side before a booking is accepted
- all ticket-type quantity calculations should be anchored to the booking items and underlying inventory data, not client state

### 2) Standardize reporting with a dedicated analytics service

The current dashboards show a healthy amount of product logic, but they are also the clearest area of duplication.

Move common analytics into a service such as:

- `EventAnalyticsService`
- `DashboardSummaryService`
- `BookingStatusSummaryService`

This should centralize:

- status totals
- revenue calculations
- category breakdowns
- check-in stats
- ticket sold / remaining summaries

Why this matters:

- fewer controller-level inconsistencies
- easier testing of edge cases
- less data-access duplication
- easier future caching and performance tuning

### 3) Treat all AI input as untrusted and validate it aggressively

The AI feature is useful, but it sits at a new trust boundary.

Recommended controls:

- keep the feature behind the config flag and environment validation
- enforce strict input length and allowlist constraints for each operation
- sanitize provider response data before sending it to persistence or rendering
- audit logs should record request metadata, not raw prompt payloads or full provider responses unless explicitly required
- keep AI output as a draft state until a human approves or edits it

### 4) Keep provider configuration centralized and secret-safe

AI generation should not depend on ad hoc local assumptions.

Recommended approach:

- manage API keys in the environment or secure secret store
- keep provider selection centralized in config
- have a clear production fallback strategy and a documented alert path for provider downtime
- define explicit allowed providers and models, rather than letting arbitrary runtime configuration drift

### 5) Make validation boundaries more uniform across the app

The app already uses request validation well in several places. Continue this pattern everywhere.

Use a consistent standard:

- controllers should be thin
- route filters should be explicitly validated
- public, organizer, and admin inputs should use typed requests whenever the logic is non-trivial
- no price, status, quantity, or identity should be trusted from the client without server-side re-checking

### 6) Keep the role and policy boundary as the main security layer

The existing role middleware and policy system is a strong choice.

Recommended discipline:

- keep route access in middleware
- keep business authorization inside policies and model-level checks
- avoid scattered permission logic in view layers or controller logic
- treat admin/organizer checks as a first-class domain rule, not as a convenience helper

### 7) Improve accessibility and UX consistency as an ongoing product task

The visual quality is generally good, but it should be hardened for usability.

Priority recommendations:

- keyboard navigation for all interactive cards and filters
- visible focus styles on form controls
- consistent use of semantic labels and ARIA states on custom controls
- responsive behavior for small screens and tablet layouts
- more predictable spacing and component repetition across dashboards and event screens

### 8) Keep the codebase shaped around bounded domain services

This project has the right seeds for a domain-oriented architecture. Continue to move logic outward from the controller layer when it crosses domain boundaries.

Use this pattern:

- controller = transport layer
- form request = validation boundary
- policy = authorization boundary
- service = domain workflow
- model = data representation and relationships

## Suggested implementation roadmap

### Phase 1 — hardening

- audit AI logging and provider configuration
- enforce stricter AI output validation and approval flow
- review all input validation boundaries
- confirm status transition rules for event lifecycle and booking lifecycle

### Phase 2 — simplification

- centralize dashboard/reporting query logic
- reduce duplication across organizer/admin screens
- normalize naming and aggregation patterns
- add consistent test coverage around edge-case status transitions

### Phase 3 — scale-readiness

- add cache or summary tables for dashboards and repeated statistics
- measure queue throughput and provider latency for AI operations
- monitor failure rates and slow query trails in production
- tighten UX accessibility and responsive QA for all major journeys

## Short conclusion

The project does not need a full rewrite. It needs disciplined hardening and a stronger emphasis on operational consistency. The booking engine, role model, and service-layer structure are already the right foundations. The next phase should focus on AI safety, reporting centralization, and broader validation consistency.
