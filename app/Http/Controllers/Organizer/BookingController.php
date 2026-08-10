<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Traits\FiltersAndSorts;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    use FiltersAndSorts;

    /**
     * List bookings + attendees for an event.
     */
    public function index(Event $event, Request $request): View
    {
        $this->authorize('update', $event);

        $query = $event->bookings()
            ->with(['user', 'items.ticketType'])
            ->withCount('tickets');

        $this->applyFilters($query, $request, [
            'status' => 'status',
        ]);

        $this->applySearch($query, $request, [
            'reference',
            fn ($q, $search) => $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")),
        ]);

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
