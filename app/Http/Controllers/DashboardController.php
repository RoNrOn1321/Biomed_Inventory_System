<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function __invoke()
    {
        if (request()->user()?->account_type === 'End_User') {
            return redirect()->route('end-user.job-request.create');
        }

        return Inertia::render('Dashboard', [
            'stats' => $this->dashboardService->getStats(),
            'recentRequests' => $this->dashboardService->getRecentPendingRequests(),
        ]);
    }
}