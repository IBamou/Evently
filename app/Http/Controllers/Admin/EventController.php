<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Events\CancelEventAction;
use App\Actions\Events\PublishEventAction;
use App\Actions\Events\RejectEventAction;
use App\Actions\Events\RestoreEventAction;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\IndexEventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class EventController extends Controller
{
    /**
     * Display a listing of all events (admin).
     */
    public function index(IndexEventRequest $request): View
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

        $events = $query->paginate(min((int) $request->input('per_page', 15), 50));

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

        // Categories with event counts
        $categories = Category::withCount('events')->get();

        return view('admin.index', compact('events', 'filters', 'stats', 'underReview', 'trashed', 'organizers', 'categories'));
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
