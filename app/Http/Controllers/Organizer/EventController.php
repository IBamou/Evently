<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Events\CancelEventAction;
use App\Actions\Events\CreateEventAction;
use App\Actions\Events\SubmitEventAction;
use App\Actions\Events\UpdateEventAction;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\IndexEventRequest;
use App\Http\Requests\Organizer\StoreEventRequest;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class EventController extends Controller
{
    /**
     * Display a listing of the organizer's events.
     */
    public function index(IndexEventRequest $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $query = $user->events()->with('category:id,name,slug');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('starts_from')) {
            $query->where('starts_at', '>=', $request->input('starts_from'));
        }

        if ($request->filled('starts_to')) {
            $query->where('starts_at', '<=', $request->input('starts_to'));
        }

        $sortOptions = [
            'starts_at' => ['starts_at', 'asc'],
            '-starts_at' => ['starts_at', 'desc'],
            'created_at' => ['created_at', 'asc'],
            '-created_at' => ['created_at', 'desc'],
            'title' => ['title', 'asc'],
            '-title' => ['title', 'desc'],
        ];

        $sort = $request->input('sort', 'created_at');
        if (isset($sortOptions[$sort])) {
            $query->orderBy($sortOptions[$sort][0], $sortOptions[$sort][1]);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $events = $query->paginate(min((int) $request->input('per_page', 15), 50));

        $filters = $request->only(['status', 'search', 'city', 'sort', 'per_page']);

        $statuses = collect(EventStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]);

        // Status counts for the filter pills
        $counts = ['all' => $user->events()->count()];
        foreach (EventStatus::cases() as $status) {
            $counts[$status->value] = $user->events()->where('status', $status->value)->count();
        }

        return view('organizer.events', compact('events', 'filters', 'statuses', 'counts'));
    }

    /**
     * Display the organizer's dashboard.
     */
    public function dashboard(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $events = $user->events()->get();

        // Real event counts; the rest of the dashboard is design sample data
        // (revenue, tickets, check-in) until the bookings domain exists.
        $stats = [
            'total' => $events->count(),
            'published' => $events->where('status', EventStatus::Published)->count(),
            'underReview' => $events->where('status', EventStatus::UnderReview)->count(),
            'drafts' => $events->where('status', EventStatus::Draft)->count(),
            'cancelled' => $events->where('status', EventStatus::Cancelled)->count(),
        ];

        return view('organizer.dashboard', ['stats' => $stats]);
    }

    /**
     * Show the form for creating a new event.
     */
    public function create(): View
    {
        $categories = Category::all();

        return view('organizer.events.create', compact('categories'));
    }

    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        /** @var User $user */
        $user = Auth::user();
        $data = $request->validated();

        (new CreateEventAction)($user, $data);

        return redirect()->route('organizer.events.index')
            ->with('success', 'Event created. Submit it for review when ready.');
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        $categories = Category::all();

        return view('organizer.events.edit', compact('event', 'categories'));
    }

    /**
     * Update the specified event.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        try {
            $data = $request->validated();

            (new UpdateEventAction)($event, $data);

            return redirect()->back()->with('success', 'Event updated successfully.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Soft-delete the specified event.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->back()->with('success', 'Event deleted.');
    }

    /**
     * Cancel a published event.
     */
    public function cancel(Event $event): RedirectResponse
    {
        $this->authorize('cancel', $event);

        try {
            (new CancelEventAction)($event);

            return redirect()->back()->with('success', 'Event cancelled.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit an event for admin review.
     */
    public function submit(Event $event): RedirectResponse
    {
        $this->authorize('submit', $event);

        try {
            (new SubmitEventAction)($event);

            return redirect()->back()->with('success', 'Event submitted for review.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
