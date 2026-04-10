<?php

namespace App\Services;

use App\Models\DescEquAccessory;
use App\Models\JobRequest;
use App\Models\Repair;
use App\Models\RequestDetail;
use Illuminate\Support\Facades\DB;

class EndUserJobRequestService
{
    public function getHistory(int $userId): array
    {
        return JobRequest::where('user_id', $userId)
            ->with(['acceptedBy:id,name', 'biomedicalServiceDoc', 'requestDetail', 'repair'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'requester_name' => $job->requester_name,
                    'department' => $job->department,
                    'control_no' => $job->control_no,
                    'equipment_name' => $job->equipment_name,
                    'status' => $job->status,
                    'priority' => $job->priority,
                    'issue_summary' => $job->issue_summary,
                    'requested_at' => $job->requested_at
                        ? $job->requested_at->format('Y-m-d H:i:s')
                        : $job->created_at->format('Y-m-d H:i:s'),
                    'accepted_by' => $job->acceptedBy?->name,
                    'biomedicalServiceDoc' => $job->biomedicalServiceDoc,
                    'request_type' => is_string($job->requestDetail?->request_type)
                        ? json_decode($job->requestDetail->request_type, true)
                        : $job->requestDetail?->request_type,
                    'repair_type' => $job->repair?->repair_type,
                    'request_complaints' => $job->request_complaints,
                    'job_report' => $job->job_report,
                    'location' => $job->location,
                    'date' => $job->date,
                ];
            })
            ->all();
    }

    public function getNextControlNumber(): string
    {
        $currentYearMonth = now()->format('Y-m');
        $lastJob = JobRequest::where('control_no', 'like', $currentYearMonth . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastJob && preg_match('/-(\d{4})$/', $lastJob->control_no, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }
        return $currentYearMonth . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function store(array $validated, ?int $userId): void
    {
        DB::transaction(function () use ($validated, $userId) {
            $services = $validated['services_desired'] ?? [];
            if (in_array('Others', $services) && !empty($validated['other_service'])) {
                $services[] = 'Other: ' . $validated['other_service'];
            }

            $detail = RequestDetail::create([
                'request_type' => count($services) > 0 ? json_encode($services) : null,
            ]);

            $finalDepartment = $validated['department'] === 'Other'
                ? $validated['department_other']
                : $validated['department'];

            // Generate control number: YYYY-MM-XXXX
            $currentYearMonth = now()->format('Y-m');
            $lastJob = JobRequest::where('control_no', 'like', $currentYearMonth . '-%')
                ->orderBy('id', 'desc')
                ->first();

            $nextNumber = 1;
            if ($lastJob && preg_match('/-(\d{4})$/', $lastJob->control_no, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }
            $controlNo = $currentYearMonth . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $jobR = JobRequest::create([
                'user_id' => $userId,
                'date' => $validated['date'],
                'control_no' => $controlNo,
                'location' => $validated['location'],
                'request_detail_id' => $detail->id,
                'request_complaints' => $validated['nature_of_work'],
                'job_report' => $validated['problem_description'],
                'requester_name' => $validated['requester_name'],
                'department' => $finalDepartment,
                'equipment_name' => collect($validated['equipments'])->pluck('equipment_name')->filter()->join(', '),
                'issue_summary' => $validated['problem_description'] ?: ($validated['nature_of_work'] ?: 'N/A'),
                'priority' => 'Medium',
                'status' => 'Pending',
                'requested_at' => now(),
            ]);

            foreach ($validated['equipments'] as $eq) {
                DescEquAccessory::create([
                    'job_request_id' => $jobR->id,
                    'name' => $eq['equipment_name'],
                    'brand' => $eq['brand'],
                    'model' => $eq['model'],
                    'serial_number' => $eq['serial_number'],
                    'end_user' => $eq['end_user'],
                ]);
            }
            
            \App\Events\JobRequestCreated::dispatch('New job request created for ' . $jobR->equipment_name, $jobR->id);
        });
    }
}