<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
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

        // Paginate (max 50)
        $perPage = min((int) $request->input('per_page', 15), 50);
        $events = $query->paginate($perPage);

        // Featured: upcoming published ordered by starts_at, take 3
        $featured = Event::query()
            ->where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(3)
            ->with(['organizer:id,name', 'category:id,name,slug'])
            ->get();

        // Categories with published-event counts (for the sidebar filter)
        $categories = Category::withCount(['events as published_count' => function ($q): void {
            $q->where('status', EventStatus::Published)->whereNull('deleted_at');
        }])->orderBy('name')->get();

        // Distinct city values of published events
        $cities = Event::where('status', EventStatus::Published)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();

        $filters = [
            'search' => $request->input('search'),
            'city' => $request->input('city'),
            'category' => $request->input('category'),
            'format' => $request->input('format'),
            'time' => $request->input('time'),
            'sort' => $request->input('sort', 'starts_at'),
            'per_page' => $perPage,
        ];

        return view('home', compact('events', 'featured', 'categories', 'filters', 'cities'));
    }

    /**
     * Display the specified published event.
     */
    public function show(Event $event): View
    {
        abort_unless($event->isPublished() && ! $event->trashed(), 404);

        $event->load(['organizer:id,name', 'category:id,name,slug']);

        // "You may also like": other upcoming published events in the same category.
        $related = Event::query()
            ->published()
            ->where('category_id', $event->category_id)
            ->whereKeyNot($event->getKey())
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return view('events.show', ['event' => $event, 'related' => $related]);
    }
}
