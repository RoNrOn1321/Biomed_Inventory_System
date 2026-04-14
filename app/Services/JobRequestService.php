<?php

namespace App\Services;

use App\Models\JobRequest;
use App\Models\BiomedicalServiceDoc;
use App\Models\User;

class JobRequestService
{
    public function listAll(?int $assignedToUserId = null): array
    {
        return JobRequest::query()
            ->with(['acceptedBy:id,name', 'assignedTo:id,name', 'biomedicalServiceDoc', 'requestDetail', 'repair', 'descEquAccessories'])
            ->when($assignedToUserId !== null, fn ($q) => $q->where('assigned_to', $assignedToUserId))
            ->orderByRaw("case when status = 'Pending' then 0 when status = 'Accepted' then 1 else 2 end")
            ->orderByDesc('requested_at')
            ->get()
            ->map(fn (JobRequest $jobRequest) => [
                'id' => $jobRequest->id,
                'requester_name' => $jobRequest->requester_name,
                'department' => $jobRequest->department,
                'equipment_name' => $jobRequest->equipment_name,
                'issue_summary' => $jobRequest->issue_summary,
                'priority' => $jobRequest->priority,
                'status' => $jobRequest->status,
                'requested_at' => optional($jobRequest->requested_at)->toIso8601String(),
                'accepted_at' => optional($jobRequest->accepted_at)->toIso8601String(),
                'accepted_by' => $jobRequest->acceptedBy?->name,
                'assigned_to_name' => $jobRequest->assignedTo?->name,
                'repair_category' => $jobRequest->repair_category,
                'admin_approval' => $jobRequest->admin_approval,
                'biomedicalServiceDoc' => $jobRequest->biomedicalServiceDoc,
                'request_type' => is_string($jobRequest->requestDetail?->request_type)
                    ? json_decode($jobRequest->requestDetail->request_type, true)
                    : $jobRequest->requestDetail?->request_type,
                'repair_type' => $jobRequest->repair?->repair_type,
                'request_complaints' => $jobRequest->request_complaints,
                'job_report' => $jobRequest->job_report,
                'control_no' => $jobRequest->control_no,
                'location' => $jobRequest->location,
                'end_user' => $jobRequest->descEquAccessories->pluck('end_user')->filter()->join(', '), 
                'date' => $jobRequest->date,
            ])
            ->all();
    }

    public function getAssignableUsers(string $currentUserType): array
    {
        $allowedTypes = $currentUserType === 'Admin'
            ? ['Biomed_Technician', 'Moderator']
            : ['Biomed_Technician'];

        return User::whereIn('account_type', $allowedTypes)
            ->orderBy('name')
            ->get(['id', 'name', 'account_type'])
            ->toArray();
    }

    public function accept(JobRequest $jobRequest, int $userId): void
    {
        if ($jobRequest->status === 'Pending') {
            $jobRequest->update([
                'status' => 'Accepted',
                'accepted_at' => now(),
                'accepted_by' => $userId,
            ]);
        }
    }

    public function assignUser(JobRequest $jobRequest, int $userId): void
    {
        $jobRequest->update(['assigned_to' => $userId]);
    }

    public function setRepairCategory(JobRequest $jobRequest, string $category): void
    {
        $jobRequest->update(['repair_category' => $category]);
    }

    public function complete(JobRequest $jobRequest, array $validated): void
    {
        $repairOutcome = $validated['repair_outcome'] ?? null;
        $docData = array_diff_key($validated, array_flip(['repair_outcome']));

        $biomedicalServiceDoc = BiomedicalServiceDoc::create($docData);

        // Auto-link to inventory equipment by matching name (case-insensitive)
        $equipmentId = $jobRequest->equipment_id;
        if (!$equipmentId) {
            $matched = \App\Models\Equipment::whereRaw('LOWER(description) = ?', [strtolower($jobRequest->equipment_name)])->first();
            $equipmentId = $matched?->id;
        }

        $jobRequest->update([
            'status' => 'Done',
            'bio_service_docs_id' => $biomedicalServiceDoc->id,
            'repair_outcome' => $repairOutcome,
            'equipment_id' => $equipmentId,
            'admin_approval' => 'Pending',
        ]);
    }
}
