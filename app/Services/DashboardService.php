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

    public function getEndUserStats(User $user): array
    {
        return [
            'my_equipment' => Equipment::where('location', $user->name)->count(),
            'my_pending' => JobRequest::where('user_id', $user->id)->where('status', 'Pending')->count(),
            'my_in_progress' => JobRequest::where('user_id', $user->id)->whereIn('status', ['Accepted', 'In Progress'])->count(),
            'my_completed' => JobRequest::where('user_id', $user->id)->where('status', 'Completed')->count(),
        ];
    }

    public function getEndUserRecentRequests(int $userId, int $limit = 5): array
    {
        return JobRequest::where('user_id', $userId)
            ->with('acceptedBy:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (JobRequest $j) => [
                'id' => $j->id,
                'control_no' => $j->control_no,
                'equipment_name' => $j->equipment_name,
                'status' => $j->status,
                'priority' => $j->priority,
                'requested_at' => $j->requested_at
                    ? $j->requested_at->toIso8601String()
                    : $j->created_at->toIso8601String(),
                'accepted_by' => $j->acceptedBy?->name,
            ])
            ->all();
    }

    public function getRecentPendingRequests(int $limit = 5): array
    {
        return JobRequest::query()
            ->with('descEquAccessories')
            ->where('status', 'Pending')
            ->latest('requested_at')
            ->limit($limit)
            ->get(['id', 'requester_name', 'department', 'equipment_name', 'priority', 'requested_at', 'location'])
            ->map(fn (JobRequest $jobRequest) => [
                'id' => $jobRequest->id,
                'requester_name' => $jobRequest->requester_name,
                'department' => $jobRequest->department,
                'equipment_name' => $jobRequest->equipment_name,
                'priority' => $jobRequest->priority,
                'location' => $jobRequest->location,
                'requested_at' => optional($jobRequest->requested_at)->toIso8601String(),
                'end_user' => $jobRequest->descEquAccessories->pluck('end_user')->filter()->join(', '),
            ])
            ->all();
    }
}
