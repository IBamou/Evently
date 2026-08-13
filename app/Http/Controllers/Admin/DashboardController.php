<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Admin\DashboardService as AdminDashboardService;
use App\Services\Organizer\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly AdminDashboardService $adminDashboardService,
    ) {}

    /**
     * Display the platform dashboard (admin).
     */
    public function dashboard(): View
    {
        $data = $this->dashboardService->buildDashboardData();
        $adminData = $this->adminDashboardService->buildDashboardData();

        $hasEvents = Event::query()->count() > 0;

        return view('admin.dashboard', array_merge($data, $adminData, compact('hasEvents')));
    }
}
