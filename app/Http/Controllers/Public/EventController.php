<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Ticket;
use App\Traits\FiltersAndSorts;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    use FiltersAndSorts;

    /**
     * Display a listing of published events (public).
     */
    public function index(Request $request): View
    {
        $query = Event::query()
            ->where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->with(['organizer:id,name', 'category:id,name,slug']);

        // Grouped search so it can NEVER bypass visibility.
        $this->applySearch($query, $request, ['title', 'description']);

        $this->applyFilters($query, $request, [
            'city' => 'city',
            'category' => fn ($q, $v) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $v)),
            'format' => fn ($q, $v) => in_array($v, ['in_person', 'online'], true) ? $q->where('format', $v) : null,
            'starts_from' => fn ($q, $v) => $q->where('starts_at', '>=', $v),
            'starts_to' => fn ($q, $v) => $q->whereDate('starts_at', '<=', $v),
            'max_price' => fn ($q, $v) => $q->whereHas('ticketTypes', fn ($tq) => $tq->where('price', '<=', (float) $v)),
        ]);

        // Time-of-day filter (based on starts_at hour)
        if ($request->filled('time')) {
            match ($request->input('time')) {
                'morning' => $query->whereTime('starts_at', '>=', '00:00:00')->whereTime('starts_at', '<', '12:00:00'),
                'afternoon' => $query->whereTime('starts_at', '>=', '12:00:00')->whereTime('starts_at', '<', '18:00:00'),
                'evening' => $query->whereTime('starts_at', '>=', '18:00:00'),
                default => null,
            };
        }

        // Whitelisted sort (default starts_at asc).
        $this->applySort($query, $request, ['starts_at', 'created_at', 'title'], 'starts_at');

        // Paginate (max 50, min 1)
        $perPage = $this->perPage($request);
        $events = $query->paginate($perPage);

        // Featured: all upcoming published events (horizontal scroll on home page)
        $featured = Event::query()
            ->where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->with(['organizer:id,name', 'category:id,name,slug'])
            ->get();

        // Categories with published-event counts (for the sidebar filter)
        // Hide categories that have zero published events (faker junk)
        $categories = Category::withCount(['events as published_count' => function ($q): void {
            $q->where('status', EventStatus::Published)->whereNull('deleted_at');
        }])->orderBy('name')->get()
            ->filter(fn ($cat) => $cat->published_count > 0)
            ->values();

        // Distinct city values of published events
        $cities = Event::where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();

        // Real hero stats — computed from database
        $upcomingCount = Event::where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->where('starts_at', '>', now())
            ->count();

        $ticketsSold = Ticket::whereHas('event', function ($q): void {
            $q->where('status', EventStatus::Published)->whereNull('deleted_at');
        })->count();

        $heroStats = [
            ['value' => number_format($upcomingCount), 'label' => 'Upcoming events'],
            ['value' => number_format($ticketsSold), 'label' => 'Tickets sold'],
        ];

        $filters = [
            'search' => $request->input('search'),
            'city' => $request->input('city'),
            'category' => $request->input('category'),
            'format' => $request->input('format'),
            'time' => $request->input('time'),
            'sort' => $request->input('sort', 'starts_at'),
            'per_page' => $perPage,
            'max_price' => $request->input('max_price'),
            'starts_from' => $request->input('starts_from'),
            'starts_to' => $request->input('starts_to'),
        ];

        return view('home', compact('events', 'featured', 'categories', 'filters', 'cities', 'heroStats'));
    }

    /**
     * Display the specified published event.
     */
    public function show(Event $event): View
    {
        abort_unless($event->isPublished() && ! $event->trashed(), 404);

        $event->load(['organizer:id,name', 'category:id,name,slug']);

        // Load active ticket types with computed availability
        $ticketTypes = $event->ticketTypes()
            ->where('is_active', true)
            ->get()
            ->map(fn ($tt) => [
                'id' => $tt->id,
                'name' => $tt->name,
                'description' => $tt->description,
                'price' => $tt->price,
                'currency' => $tt->currency,
                'min_per_booking' => $tt->min_per_booking,
                'max_per_booking' => $tt->max_per_booking,
                'available_quantity' => $tt->availableQuantity(),
                'is_sales_open' => $tt->isSalesOpen(),
                'sales_start_at' => $tt->sales_start_at,
                'sales_end_at' => $tt->sales_end_at,
            ]);

        // "You may also like": other upcoming published events in the same category.
        $related = Event::query()
            ->published()
            ->where('category_id', $event->category_id)
            ->whereKeyNot($event->getKey())
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        // Sales progress: issued tickets count + total capacity across ticket types.
        // ticket_types.quantity IS the capacity (no separate capacity column).
        $sold = $event->tickets()->count();
        $capacity = (int) $event->ticketTypes()->sum('quantity');

        return view('events.show', compact('event', 'related', 'ticketTypes', 'sold', 'capacity'));
    }
}
