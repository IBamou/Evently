# Evently

Evently is a Laravel-powered event management platform: organizers publish events with paid/free ticket types, users book tickets in a few clicks, and organizers check guests in with QR codes at the door. Includes an AI Event Copilot that drafts event pages and marketing copy.

## Stack

- Laravel 13 + Livewire 4 (Breeze, Tailwind CSS, Alpine.js)
- PHP 8.4, MySQL (SQLite for tests)
- Pest for testing, Pint for code style, PHPStan (Larastan) for static analysis
- Laravel AI (OpenRouter primary / Groq fallback) for the AI Event Copilot

## Setup

```bash
composer install
cp .env.example .env        # then configure DB + AI keys
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
herd services:start mysql redis  # or your local equivalents
php artisan serve               # if not using Herd
```

The app is served by Laravel Herd at `https://evently.test` (see `herd sites`).

### Queues

Bookings and AI generation are processed through the queue:

```bash
php artisan queue:work --queue=ai-copilot,default --timeout=150 --tries=2
```

The AI Copilot ships with real free-tier provider keys configured in `.env`
(`OPENROUTER_API_KEY`, `GROQ_API_KEY`). **Never switch the configured models to
paid ones** — the free models (`nvidia/nemotron-3-nano-30b-a3b:free`,
`openai/gpt-oss-20b`) keep the demo free of cost. Rate limits are enforced
server-side (50/day, 5/minute per user).

## Key Flows

- **Booking** — checkout validates quantity rules and server-side pricing, holds
  capacity with row locks, and guarantees idempotency per user+event+selection.
  Free bookings confirm instantly; paid bookings stay pending with a 15-minute
  payment window, then expire.
- **Payments (mock)** — payment confirmation is currently **simulated**: mock
  card details confirm a booking instantly without any charge. The mock path is
  gated behind `PAYMENTS_MOCK_CONFIRM` in `config/payments.php`; set it to
  `false` before wiring a real provider, which also blocks the manual
  confirm endpoint.
- **Tickets** — issued on confirmation with unique QR codes; organizers scan
  them at check-in.
- **AI Event Copilot** — organizers generate event drafts and marketing copy.
  Prompt inputs and outputs are persisted, provider errors are retried through
  the queue, and structured output failures are marked as permanent errors
  instead of silently succeeding.

## Testing

```bash
php artisan test                    # full suite
php artisan test --filter=Booking   # a single area
vendor/bin/pint --dirty             # code style
vendor/bin/phpstan analyse          # static analysis
```

## Roles

- **Attendees** — browse events, book tickets, manage their bookings (cancel,
  confirm mock payment), view QR tickets.
- **Organizers** — manage events and ticket types, view bookings, check guests
  in.
- **Admins** — manage events, bookings, and payments; can cancel any booking.

## Requirements Traceability

Core flows implement a requirements set (`REQ-BK-*` booking, `REQ-PY-*`
payments, `REQ-CN-*` cancellation/expiry, `REQ-TK-*` tickets) that is
enumerated in code comments and covered by the Pest suite in `tests/Feature/`.
