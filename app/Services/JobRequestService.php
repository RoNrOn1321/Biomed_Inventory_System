<?php

namespace App\Services;

use App\Models\JobRequest;
use App\Models\BiomedicalServiceDoc;

class JobRequestService
{
    public function listAll(): array
    {
        return JobRequest::query()
            ->with(['acceptedBy:id,name', 'biomedicalServiceDoc', 'requestDetail', 'repair', 'descEquAccessories'])
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

    public function complete(JobRequest $jobRequest, array $validated): void
    {
        $biomedicalServiceDoc = BiomedicalServiceDoc::create($validated);

        $jobRequest->update([
            'status' => 'Done',
            'bio_service_docs_id' => $biomedicalServiceDoc->id,
        ]);
    }
}
