<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckInController extends Controller
{
    /**
     * Show the check-in event picker.
     *
     * Lets the organizer (or admin) choose which door they are operating
     * instead of silently targeting the first event.
     */
    public function picker(): View
    {
        $user = auth()->user();

        $events = $user->isAdmin()
            ? Event::query()->with('category:id,name,slug')->get()
            : $user->events()->with('category:id,name,slug')->get();

        $events = $events
            ->sortBy(function (Event $event): array {
                $startsAt = $event->starts_at;
                $stamp = (int) ($startsAt->timestamp ?? 0);

                // Bucket 0 = upcoming published (soonest first); bucket 1 = everything else (latest first).
                if ($event->status === EventStatus::Published && $startsAt?->isFuture() === true) {
                    return [0, $stamp];
                }

                return [1, -$stamp];
            })
            ->values();

        $doors = $events->map(fn (Event $event): array => [
            'event' => $event,
            'stats' => $this->statsFor($event),
        ]);

        return view('organizer.check-in.picker', ['events' => $doors]);
    }

    /**
     * Show the check-in page.
     */
    public function index(Event $event): View
    {
        $this->authorize('update', $event);

        $stats = $this->statsFor($event);

        $recentScans = $event->tickets()
            ->where('status', TicketStatus::Used->value)
            ->with(['ticketType'])
            ->orderBy('checked_in_at', 'desc')
            ->limit(10)
            ->get();

        return view('organizer.check-in.index', compact('event', 'stats', 'recentScans'));
    }

    /**
     * Live door stats: how many tickets were checked in, issued in total, and
     * still remaining. Reused by the page render and by the scan JSON response
     * so the view can refresh the counters without a full reload.
     */
    private function statsFor(Event $event): array
    {
        return [
            'checked_in' => $event->tickets()->where('status', TicketStatus::Used->value)->count(),
            'issued' => $event->tickets()->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value])->count(),
            'remaining' => $event->tickets()->where('status', TicketStatus::Valid->value)->count(),
        ];
    }

    /**
     * Scan a ticket code (POST).
     *
     * Supports both manual form (redirect back with flash) and camera fetch (JSON).
     */
    public function scan(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $event);

        $user = $request->user();

        $request->validate([
            'code' => ['required', 'string', 'max:48'],
        ]);

        $code = $request->input('code');

        $ticket = Ticket::where('code', $code)
            ->where('event_id', $event->id)
            ->with(['ticketType', 'user'])
            ->first();

        $wantsJson = $request->expectsJson() || $request->isXmlHttpRequest();

        if (! $ticket) {
            return $this->scanResponse($wantsJson, 'not_found', 'No ticket found for this event.', 404);
        }

        if ($event->status === EventStatus::Cancelled) {
            return $this->scanResponse($wantsJson, 'event_cancelled', 'This event has been cancelled.');
        }

        if ($ticket->status === TicketStatus::Cancelled) {
            return $this->scanResponse($wantsJson, 'cancelled', 'This ticket has been cancelled.');
        }

        if ($ticket->status === TicketStatus::Used) {
            $at = $ticket->checked_in_at?->format('g:i A') ?? 'previously';

            return $this->scanResponse($wantsJson, 'already_used', "This ticket was already checked in at {$at}.");
        }

        // Atomic conditional UPDATE (double-scan race protection per REQ-TK-004)
        $updated = DB::table('tickets')
            ->where('id', $ticket->id)
            ->where('event_id', $event->id)
            ->where('status', TicketStatus::Valid->value)
            ->update([
                'status' => TicketStatus::Used->value,
                'checked_in_at' => now(),
            ]);

        if ($updated === 0) {
            return $this->scanResponse($wantsJson, 'already_used', 'This ticket was just checked in by another scanner.');
        }

        $result = [
            'valid' => true,
            'result' => 'checked_in',
            'message' => 'Welcome in!',
            'ticket' => [
                'type' => $ticket->ticketType?->name ?? 'Ticket',
                'holder_name' => $ticket->user->name ?? 'Guest',
                'checked_in_at' => now()->toIso8601String(),
            ],
            'stats' => $this->statsFor($event),
        ];

        return $wantsJson
            ? response()->json($result)
            : redirect()->back()->with('checkin_success', $result);
    }

    private function scanResponse(bool $wantsJson, string $result, string $message, int $code = 422): JsonResponse|RedirectResponse
    {
        $payload = ['valid' => false, 'result' => $result, 'message' => $message];

        return $wantsJson
            ? response()->json($payload, $code)
            : redirect()->back()->withInput()->withErrors(['code' => $message]);
    }
}
