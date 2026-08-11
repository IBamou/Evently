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
use App\Services\Organizer\DashboardService;
use App\Traits\Queryable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class EventController extends Controller
{
    use Queryable;

    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Display a listing of the organizer's events.
     */
    public function index(IndexEventRequest $request): View
    {
        $user = Auth::user();
        $query = $user->events()->with('category:id,name,slug');

        $this->applyFilters($query, $request, [
            'status' => 'status',
            'city' => 'city',
            'starts_from' => fn ($q, $v) => $q->where('starts_at', '>=', $v),
            'starts_to' => fn ($q, $v) => $q->whereDate('starts_at', '<=', $v),
        ]);

        $this->applySearch($query, $request, ['title', 'description']);

        $this->applySort($query, $request, ['starts_at', 'created_at', 'title'], 'created_at');

        $events = $query->paginate($this->perPage($request));

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
        $user = Auth::user();

        $data = $this->dashboardService->buildDashboardData($user);

        return view('organizer.dashboard', $data);
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
