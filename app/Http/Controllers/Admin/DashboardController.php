<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Organizer\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Display the platform dashboard (admin).
     */
    public function dashboard(): View
    {
        $data = $this->dashboardService->buildDashboardData();

        $hasEvents = Event::query()->count() > 0;

        return view('admin.dashboard', array_merge($data, compact('hasEvents')));
    }
}
