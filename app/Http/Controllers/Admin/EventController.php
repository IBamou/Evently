<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\CancelEventAction;
use App\Actions\Events\PublishEventAction;
use App\Actions\Events\RejectEventAction;
use App\Actions\Events\RestoreEventAction;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Traits\FiltersAndSorts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class EventController extends Controller
{
    use FiltersAndSorts;

    /**
     * Display a listing of all events (admin).
     */
    public function index(Request $request): View
    {
        $query = Event::query()->with(['organizer:id,name', 'category:id,name,slug']);

        $this->applyFilters($query, $request, [
            'status' => 'status',
            'organizer_id' => 'organizer_id',
            'city' => 'city',
        ]);

        $this->applySearch($query, $request, ['title', 'description']);

        $this->applySort($query, $request, ['starts_at', 'created_at', 'title'], 'created_at');

        $perPage = $this->perPage($request);
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
