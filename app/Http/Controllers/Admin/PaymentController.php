<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Traits\FiltersAndSorts;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use FiltersAndSorts;

    /**
     * List all payments (admin, REQ-PY-008).
     */
    public function index(Request $request): View
    {
        $query = Payment::query()
            ->with(['booking.user', 'booking.event']);

        $this->applyFilters($query, $request, [
            'status' => 'status',
            'reference' => fn ($q, $v) => $q->whereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$v}%")),
            'date_from' => fn ($q, $v) => $q->where('created_at', '>=', $v),
            'date_to' => fn ($q, $v) => $q->where('created_at', '<=', $v),
        ]);

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        $filters = $request->only(['status', 'reference', 'date_from', 'date_to']);

        return view('admin.payments', compact('payments', 'filters'));
    }
}
