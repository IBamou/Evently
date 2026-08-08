<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Display a listing of published events (public).
     */
    public function index(Request $request): View
    {
        $query = Event::query()
            ->where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->with(['organizer:id,name', 'category:id,name,slug']);

        // Grouped search (title OR description) inside closure so it can NEVER bypass visibility
        $query->when($request->filled('search'), function ($q, $search): void {
            $q->where(function ($sub) use ($search): void {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        // City exact filter
        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        // Category filter (by slug, keeps URL friendly)
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
        }

        // Format filter
        if ($request->filled('format') && in_array($request->input('format'), ['in_person', 'online'], true)) {
            $query->where('format', $request->input('format'));
        }

        // Time-of-day filter (based on starts_at hour)
        if ($request->filled('time')) {
            match ($request->input('time')) {
                'morning' => $query->whereTime('starts_at', '>=', '00:00:00')->whereTime('starts_at', '<', '12:00:00'),
                'afternoon' => $query->whereTime('starts_at', '>=', '12:00:00')->whereTime('starts_at', '<', '18:00:00'),
                'evening' => $query->whereTime('starts_at', '>=', '18:00:00'),
                default => null,
            };
        }

        // Date range filter
        if ($request->filled('starts_from')) {
            $query->where('starts_at', '>=', $request->input('starts_from'));
        }

        if ($request->filled('starts_to')) {
            $query->where('starts_at', '<=', $request->input('starts_to'));
        }

        // Max price filter: match events that have at least one ticket type at or below the price
        if ($request->filled('max_price')) {
            $query->whereHas('ticketTypes', fn ($q) => $q->where('price', '<=', (float) $request->input('max_price')));
        }

        // Whitelisted sort (default starts_at asc)
        $sortOptions = [
            'starts_at' => ['starts_at', 'asc'],
            '-starts_at' => ['starts_at', 'desc'],
            'created_at' => ['created_at', 'asc'],
            '-created_at' => ['created_at', 'desc'],
            'title' => ['title', 'asc'],
            '-title' => ['title', 'desc'],
        ];

        $sort = $request->input('sort', 'starts_at');
        if (isset($sortOptions[$sort])) {
            $query->orderBy($sortOptions[$sort][0], $sortOptions[$sort][1]);
        } else {
            $query->orderBy('starts_at', 'asc');
        }

        // Paginate (max 50, min 1)
        $perPage = max(1, min((int) $request->input('per_page', 15), 50));
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
