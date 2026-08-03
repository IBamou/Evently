<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * List all payments (admin, REQ-PY-008).
     */
    public function index(Request $request): View
    {
        $query = Payment::query()
            ->with(['booking.user', 'booking.event']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('reference')) {
            $query->whereHas('booking', fn ($q) => $q->where('reference', 'like', '%'.$request->input('reference').'%'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        $filters = $request->only(['status', 'reference', 'date_from', 'date_to']);

        return view('admin.payments', compact('payments', 'filters'));
    }
}
