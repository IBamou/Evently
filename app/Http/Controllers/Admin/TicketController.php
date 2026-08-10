<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Traits\FiltersAndSorts;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    use FiltersAndSorts;

    /**
     * List all tickets (admin).
     */
    public function index(Request $request): View
    {
        $query = Ticket::query()
            ->with(['user', 'event', 'ticketType', 'booking']);

        $this->applyFilters($query, $request, [
            'event_id' => 'event_id',
            'status' => 'status',
        ]);

        $this->applySearch($query, $request, [
            'code',
            fn ($q, $search) => $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")),
            fn ($q, $search) => $q->orWhereHas('event', fn ($e) => $e->where('title', 'like', "%{$search}%")),
        ]);

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        $filters = $request->only(['event_id', 'status', 'search']);

        return view('admin.tickets', compact('tickets', 'filters'));
    }
}
