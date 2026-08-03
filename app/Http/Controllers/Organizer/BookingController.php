<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * List bookings + attendees for an event.
     */
    public function index(Event $event, Request $request): View
    {
        $this->authorize('update', $event);

        $query = $event->bookings()
            ->with(['user', 'items.ticketType'])
            ->withCount('tickets');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        // Attendees: flat ticket list per REQ-TK-007
        $attendees = $event->tickets()
            ->with(['user', 'ticketType', 'booking'])
            ->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'attendees_page');

        return view('organizer.bookings.index', compact('event', 'bookings', 'attendees'));
    }
}
