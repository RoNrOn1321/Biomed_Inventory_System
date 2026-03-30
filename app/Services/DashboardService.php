<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\JobRequest;
use App\Models\User;

class DashboardService
{
    public function getStats(): array
    {
        return [
            'equipment_count' => Equipment::query()->count(),
            'pending_job_requests' => JobRequest::query()->where('status', 'Pending')->count(),
            'accepted_job_requests' => JobRequest::query()->where('status', 'Accepted')->count(),
            'biomed_technicians' => User::query()->where('account_type', 'Biomed_Technician')->count(),
        ];
    }

    public function getRecentPendingRequests(int $limit = 5): array
    {
        return JobRequest::query()
            ->where('status', 'Pending')
            ->latest('requested_at')
            ->limit($limit)
            ->get(['id', 'requester_name', 'department', 'equipment_name', 'priority', 'requested_at'])
            ->map(fn (JobRequest $jobRequest) => [
                'id' => $jobRequest->id,
                'requester_name' => $jobRequest->requester_name,
                'department' => $jobRequest->department,
                'equipment_name' => $jobRequest->equipment_name,
                'priority' => $jobRequest->priority,
                'requested_at' => optional($jobRequest->requested_at)->toIso8601String(),
            ])
            ->all();
    }
}
