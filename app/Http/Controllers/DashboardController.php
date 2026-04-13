<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function __invoke()
    {
        $user = request()->user();

        if ($user?->account_type === 'End_User') {
            return Inertia::render('EndUserDashboard', [
                'stats' => $this->dashboardService->getEndUserStats($user),
                'recentRequests' => $this->dashboardService->getEndUserRecentRequests($user->id),
            ]);
        }

        return Inertia::render('Dashboard', [
            'stats' => $this->dashboardService->getStats(),
            'recentRequests' => $this->dashboardService->getRecentPendingRequests(),
        ]);
    }
}