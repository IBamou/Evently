<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\CancelEventAction;
use App\Actions\Events\PublishEventAction;
use App\Actions\Events\RejectEventAction;
use App\Actions\Events\RestoreEventAction;
use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class EventController extends Controller
{
    /**
     * Display the platform dashboard (design odash as admin).
     *
     * Real platform-wide stats (events, revenue, tickets, check-ins) plus the
     * six most recent bookings. Charts and category bars computed from real
     * platform-wide data, mirroring the organizer dashboard.
     */
    public function dashboard(): View
    {
        $stats = [
            'total' => Event::query()->count(),
            'published' => Event::query()->where('status', EventStatus::Published)->count(),
            'underReview' => Event::query()->where('status', EventStatus::UnderReview)->count(),
        ];

        $revenue = (float) Payment::query()->where('status', PaymentStatus::Succeeded->value)->sum('amount');
        $ticketsIssued = Ticket::query()->count();
        $ticketsChecked = Ticket::query()->whereNotNull('checked_in_at')->count();

        $orders = Booking::query()
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
                    'total' => $booking->total,
                    'status' => match ($booking->status) {
                        BookingStatus::Confirmed => 'Paid',
                        BookingStatus::Pending => 'Pending',
                        BookingStatus::Cancelled => 'Cancelled',
                        default => 'Expired',
                    },
                ];
            });

        // Platform-wide chart and category bars (mirror organizer pattern).
        $chart = $this->chartSeries();
        $catBars = $this->categoryBars();

        $checkInRate = $ticketsIssued > 0
            ? round($ticketsChecked / $ticketsIssued * 100, 1)
            : null;

        $hasEvents = Event::query()->count() > 0;

        return view('admin.dashboard', compact(
            'stats',
            'revenue',
            'ticketsIssued',
            'ticketsChecked',
            'orders',
            'chart',
            'catBars',
            'checkInRate',
            'hasEvents'
        ));
    }

    /**
     * Display a listing of all events (admin).
     */
    public function index(Request $request): View
    {
        $query = Event::query()->with(['organizer:id,name', 'category:id,name,slug']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organizer_id')) {
            $query->where('organizer_id', $request->input('organizer_id'));
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

        $perPage = max(1, min((int) $request->input('per_page', 15), 50));
        $events = $query->paginate($perPage);

        $filters = $request->only(['status', 'organizer_id', 'search', 'city', 'sort', 'per_page']);

        // Stats
        $stats = [
            'total' => Event::count(),
            'published' => Event::where('status', EventStatus::Published)->count(),
            'under_review' => Event::where('status', EventStatus::UnderReview)->count(),
            'drafts' => Event::where('status', EventStatus::Draft)->count(),
            'cancelled' => Event::where('status', EventStatus::Cancelled)->count(),
            'categories' => Category::count(),
        ];

        // Under review events with organizer name
        $underReview = Event::where('status', EventStatus::UnderReview)
            ->with('organizer:id,name')
            ->get();

        // Trashed events for restore
        $trashed = Event::onlyTrashed()
            ->with(['organizer:id,name', 'category:id,name,slug'])
            ->paginate(15, ['*'], 'trashed_page');

        // Organizers for filter select
        $organizers = User::where('role', 'organizer')->select('id', 'name')->get();

        // ── Users tab ──
        $users = User::query()
            ->withCount('bookings')
            ->when($request->filled('user_search'), fn ($q) => $q->where(
                fn ($qq) => $qq->where('name', 'like', '%'.$request->input('user_search').'%')
                    ->orWhere('email', 'like', '%'.$request->input('user_search').'%')
            ))
            ->orderBy('name')
            ->paginate(10, ['*'], 'users_page');

        $userSearch = $request->input('user_search');

        // ── Reports tab ──
        $cityBars = $this->cityBars();
        $reportStats = $this->reportStats();

        return view('admin.index', compact(
            'events',
            'filters',
            'stats',
            'underReview',
            'trashed',
            'organizers',
            'users',
            'userSearch',
            'cityBars',
            'reportStats'
        ));
    }

    /**
     * Build the last-5-weeks revenue/tickets series for the dashboard chart.
     *
     * Platform-wide (no event scope), mirroring organizer's chartSeries().
     *
     * @return array<int, array{label: string, revH: string, tixH: string, revLabel: string, tixLabel: string}>
     */
    private function chartSeries(): array
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
                ->whereBetween('paid_at', [$week['start'], $week['end']])
                ->sum('amount');
            $tickets[] = Ticket::query()
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
                'tixLabel' => number_format($tickets[$i]),
            ];
        })->all();
    }

    /**
     * Aggregate ticket quantities per event category for the dashboard.
     *
     * Platform-wide (no event scope), mirroring organizer's categoryBars().
     *
     * @return array<int, array{label: string, value: string, pct: string, color: string}>
     */
    private function categoryBars(): array
    {
        $colors = ['var(--primary)', 'var(--cyan)', 'var(--teal)', '#7C3AED', '#F59E0B'];

        $rows = BookingItem::query()
            ->whereHas('booking', function ($q): void {
                $q->where('status', BookingStatus::Confirmed->value);
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
     * Top cities by ticket volume for the reports tab.
     *
     * @return array<int, array{label: string, value: int, pct: string}>
     */
    private function cityBars(): array
    {
        $rows = DB::table('tickets')
            ->join('events', 'events.id', '=', 'tickets.event_id')
            ->selectRaw('events.city as city, count(*) as total')
            ->groupBy('events.city')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $grandTotal = $rows->sum('total');

        return $rows->map(function ($row) use ($grandTotal): array {
            return [
                'label' => $row->city ?? 'Unknown',
                'value' => (int) $row->total,
                'pct' => round($row->total / $grandTotal * 100).'%',
            ];
        })->all();
    }

    /**
     * Compute report summary stats for the admin reports tab.
     *
     * @return array{grossVolume: float, activeUsers: int, organizers: int, refundRate: float}
     */
    private function reportStats(): array
    {
        $grossVolume = (float) Payment::query()
            ->where('status', PaymentStatus::Succeeded->value)
            ->sum('amount');

        $activeUsers = User::query()
            ->whereHas('bookings')
            ->count();

        $organizers = User::query()
            ->where('role', 'organizer')
            ->count();

        $totalPayments = Payment::query()->count();
        $refundedPayments = $totalPayments > 0
            ? Payment::query()->where('status', PaymentStatus::Refunded->value)->count()
            : 0;
        $refundRate = $totalPayments > 0
            ? round($refundedPayments / $totalPayments * 100, 2)
            : 0.0;

        return [
            'grossVolume' => $grossVolume,
            'activeUsers' => $activeUsers,
            'organizers' => $organizers,
            'refundRate' => $refundRate,
        ];
    }

    /**
     * Publish an event under review.
     */
    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        try {
            (new PublishEventAction)($event);

            return redirect()->back()->with('success', 'Event published.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject an event under review (back to draft).
     */
    public function reject(Event $event): RedirectResponse
    {
        $this->authorize('reject', $event);

        try {
            (new RejectEventAction)($event);

            return redirect()->back()->with('success', 'Event rejected, returned to draft.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
     * Soft-delete the specified event.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->back()->with('success', 'Event deleted.');
    }

    /**
     * Restore a soft-deleted event.
     */
    public function restore(int $id): RedirectResponse
    {
        $event = Event::withTrashed()->findOrFail($id);

        $this->authorize('restore', $event);

        try {
            (new RestoreEventAction)($event);

            return redirect()->back()->with('success', 'Event restored.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
