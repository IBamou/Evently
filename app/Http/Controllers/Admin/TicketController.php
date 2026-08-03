<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * List all tickets (admin).
     */
    public function index(Request $request): View
    {
        $query = Ticket::query()
            ->with(['user', 'event', 'ticketType', 'booking']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('event', fn ($e) => $e->where('title', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        $filters = $request->only(['event_id', 'status', 'search']);

        return view('admin.tickets', compact('tickets', 'filters'));
    }
}
