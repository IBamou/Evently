<?php

namespace App\Http\Controllers\User;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * List tickets for the authenticated user, grouped by event.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Status filter — only accept exact TicketStatus enum values; silently ignore invalid values.
        $statusParam = request()->query('status');
        $status = in_array($statusParam, array_column(TicketStatus::cases(), 'value'), true)
            ? $statusParam
            : null;

        // Eager-load minimal event + ticketType columns. No pagination — safety cap 200.
        /** @var Builder $query */
        $query = $user->tickets()
            ->with(['event:id,title,slug,starts_at,ends_at,location,category_id', 'ticketType:id,name']);

        if ($status !== null) {
            $query->where('status', $status);
        }

        /** @var Collection $tickets */
        $tickets = $query->limit(200)->get();

        // Unfiltered status counts for the status pills (single query, like User\BookingController).
        /** @var Collection $statusCounts */
        $statusCounts = $user->tickets()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'all' => (int) $statusCounts->sum(),
            'valid' => (int) ($statusCounts[TicketStatus::Valid->value] ?? 0),
            'used' => (int) ($statusCounts[TicketStatus::Used->value] ?? 0),
            'cancelled' => (int) ($statusCounts[TicketStatus::Cancelled->value] ?? 0),
        ];

        // Group tickets by event_id.
        $grouped = $tickets->groupBy('event_id');

        /** @var Collection $eventGroups */
        $eventGroups = $grouped->map(function (Collection $eventTickets) {
            $firstTicket = $eventTickets->first();

            // Defensive: skip tickets with no event relationship (should not happen, but guard).
            if ($firstTicket?->event === null) {
                return null;
            }

            // Sort within group: valid first, then by created_at ascending (oldest first).
            $sortedTickets = $eventTickets->sortBy(function (Ticket $ticket) {
                return [
                    $ticket->status !== TicketStatus::Valid ? 1 : 0,
                    $ticket->created_at->timestamp,
                ];
            });

            return [
                'event' => $firstTicket->event,
                'tickets' => $sortedTickets->values(),
                'total' => $eventTickets->count(),
                'valid' => $eventTickets->where('status', TicketStatus::Valid)->count(),
                'used' => $eventTickets->where('status', TicketStatus::Used)->count(),
                'cancelled' => $eventTickets->where('status', TicketStatus::Cancelled)->count(),
            ];
        })->filter()->values();

        // Sort groups (single pass, compound key):
        //  Bucket 0 — Upcoming + "happening now": ascending by starts_at (soonest first).
        //  Bucket 1 — Past: descending by starts_at (most recent past just below upcoming).
        //  Bucket 2 — Null starts_at: bottom.
        // "Happening now" (starts_at <= now && ends_at >= now) is treated as upcoming per spec;
        // starts_at asc naturally puts it at the top of the upcoming bucket.
        $now = Carbon::now();

        $eventGroups = $eventGroups->sortBy(function (array $eventGroup) use ($now): array {
            /** @var Event $event */
            $event = $eventGroup['event'];
            $startsAt = $event->starts_at;

            if ($startsAt === null) {
                return [2, 0];
            }

            // Upcoming OR happening now (ends_at not yet passed).
            $isPast = $startsAt->isBefore($now)
                && ($event->ends_at === null || $event->ends_at->isBefore($now));

            if ($isPast) {
                // Past: bucket 1, descending by starts_at (negate so most recent = lowest key = first).
                return [1, -((int) $startsAt->timestamp)];
            }

            // Upcoming / happening now: bucket 0, ascending by starts_at.
            return [0, $startsAt->timestamp];
        })->values();

        return view('tickets.index', compact('eventGroups', 'counts', 'status'));
    }
}
