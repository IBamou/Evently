<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Events\CancelEventAction;
use App\Actions\Events\CreateEventAction;
use App\Actions\Events\SubmitEventAction;
use App\Actions\Events\UpdateEventAction;
use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\IndexEventRequest;
use App\Http\Requests\Organizer\StoreEventRequest;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Traits\FiltersAndSorts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class EventController extends Controller
{
    use FiltersAndSorts;

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

        $this->applySearch($query, $request, ['title', 'description']);

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('starts_from')) {
            $query->where('starts_at', '>=', $request->input('starts_from'));
        }

        if ($request->filled('starts_to')) {
            $query->where('starts_at', '<=', $request->input('starts_to'));
        }

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
        /** @var User $user */
        $user = Auth::user();
        $events = $user->events()->get();
        $eventIds = $events->pluck('id');

        // Real event counts.
        $stats = [
            'total' => $events->count(),
            'published' => $events->where('status', EventStatus::Published)->count(),
            'underReview' => $events->where('status', EventStatus::UnderReview)->count(),
            'drafts' => $events->where('status', EventStatus::Draft)->count(),
            'cancelled' => $events->where('status', EventStatus::Cancelled)->count(),
        ];

        // Real sales figures scoped to this organizer's events.
        $revenue = (float) Payment::query()
            ->where('status', PaymentStatus::Succeeded->value)
            ->whereHas('booking', fn ($q) => $q->whereIn('event_id', $eventIds))
            ->sum('amount');

        $ticketsIssued = Ticket::query()
            ->whereIn('event_id', $eventIds)
            ->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value])
            ->count();

        $ticketsChecked = Ticket::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', TicketStatus::Used->value)
            ->count();

        $checkInRate = $ticketsIssued > 0 ? (int) round($ticketsChecked / $ticketsIssued * 100) : 0;

        $orders = Booking::query()
            ->whereIn('event_id', $eventIds)
            ->with(['user:id,name', 'event:id,title'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Booking $booking): array {
                $name = $booking->user->name ?? 'Unknown';

                return [
                    'buyer' => $name,
                    'initial' => collect(explode(' ', $name))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
                        ->join(''),
                    'event' => $booking->event->title ?? '—',
                    'qty' => $booking->items()->sum('quantity'),
                    'total' => (float) $booking->total,
                    'status' => match ($booking->status) {
                        BookingStatus::Confirmed => 'Paid',
                        BookingStatus::Pending => 'Pending',
                        BookingStatus::Cancelled => 'Cancelled',
                        default => 'Expired',
                    },
                ];
            });

        // Weekly revenue + tickets for the last 5 calendar weeks.
        $chart = $this->chartSeries($eventIds);

        // Real sales by event category (confirmed bookings only).
        $catBars = $this->categoryBars($eventIds);

        return view('organizer.dashboard', compact(
            'stats',
            'revenue',
            'ticketsIssued',
            'ticketsChecked',
            'checkInRate',
            'orders',
            'chart',
            'catBars'
        ));
    }

    /**
     * Build the last-5-weeks revenue/tickets series for the dashboard chart.
     *
     * @param  Collection<int, int>  $eventIds
     * @return array<int, array{label: string, revH: string, tixH: string, revLabel: string, tixLabel: string}>
     */
    private function chartSeries(Collection $eventIds): array
    {
        $weeks = collect(range(4, 0))->map(
            fn (int $offset): array => [
                'start' => now()->startOfWeek()->subWeeks($offset),
                'end' => now()->startOfWeek()->subWeeks($offset - 1),
            ]
        );

        $revenue = [];
        $tickets = [];
        foreach ($weeks as $week) {
            $revenue[] = (float) Payment::query()
                ->where('status', PaymentStatus::Succeeded->value)
                ->whereHas('booking', fn ($q) => $q->whereIn('event_id', $eventIds))
                ->whereBetween('paid_at', [$week['start'], $week['end']])
                ->sum('amount');
            $tickets[] = Ticket::query()
                ->whereIn('event_id', $eventIds)
                ->whereBetween('created_at', [$week['start'], $week['end']])
                ->count();
        }

        $maxRevenue = collect($revenue)->max() ?: 1.0;
        $maxTickets = collect($tickets)->max() ?: 1;

        return $weeks->values()->map(function (array $week, int $i) use ($revenue, $tickets, $maxRevenue, $maxTickets): array {
            $label = 'W'.($i + 1);

            return [
                'label' => $label,
                'revH' => max(2, (int) round($revenue[$i] / $maxRevenue * 100)).'%',
                'tixH' => max(2, (int) round($tickets[$i] / $maxTickets * 100)).'%',
                'revLabel' => number_format($revenue[$i]).' MAD',
                'tixLabel' => number_format($tickets[$i]).' tickets',
            ];
        })->all();
    }

    /**
     * Aggregate ticket quantities per event category for the dashboard.
     *
     * @param  Collection<int, int>  $eventIds
     * @return array<int, array{label: string, value: string, pct: string, color: string}>
     */
    private function categoryBars(Collection $eventIds): array
    {
        $colors = ['var(--primary)', 'var(--cyan)', 'var(--teal)', '#7C3AED', '#F59E0B'];

        $rows = BookingItem::query()
            ->whereHas('booking', function ($q) use ($eventIds): void {
                $q->whereIn('event_id', $eventIds)
                    ->where('status', BookingStatus::Confirmed->value);
            })
            ->with('booking.event.category:id,name')
            ->get()
            ->groupBy(fn (BookingItem $item): string => $item->booking->event->category->name ?? 'Uncategorized')
            ->map(fn ($items): int => $items->sum('quantity'))
            ->sortDesc()
            ->take(5);

        if ($rows->isEmpty()) {
            return [];
        }

        $total = $rows->sum();

        $labels = $rows->keys()->all();

        return $rows->values()->map(function (int $qty, int $i) use ($labels, $total, $colors): array {
            return [
                'label' => $labels[$i],
                'value' => number_format($qty).' tix',
                'pct' => round($qty / $total * 100).'%',
                'color' => $colors[$i % count($colors)],
            ];
        })->all();
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
