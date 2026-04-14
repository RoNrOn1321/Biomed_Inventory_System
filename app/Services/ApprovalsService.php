<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\JobRequest;

class ApprovalsService
{
    public function pendingJobRequests(): array
    {
        return JobRequest::query()
            ->where('admin_approval', 'Pending')
            ->with(['acceptedBy:id,name', 'assignedTo:id,name', 'biomedicalServiceDoc'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (JobRequest $jr) => [
                'id' => $jr->id,
                'equipment_name' => $jr->equipment_name,
                'requester_name' => $jr->requester_name,
                'department' => $jr->department,
                'priority' => $jr->priority,
                'accepted_by' => $jr->acceptedBy?->name,
                'assigned_to_name' => $jr->assignedTo?->name,
                'completed_at' => optional($jr->updated_at)->toIso8601String(),
                'repair_outcome' => $jr->repair_outcome,
                'biomedical_service_doc' => $jr->biomedicalServiceDoc,
            ])
            ->all();
    }

    public function pendingEquipment(): array
    {
        return Equipment::query()
            ->where('admin_approval', 'Pending')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Equipment $eq) => [
                'id' => $eq->id,
                'description' => $eq->description,
                'brand' => $eq->brand,
                'model' => $eq->model,
                'serial_number' => $eq->serial_number,
                'tag_number' => $eq->tag_number,
                'location' => $eq->location,
                'pending_action' => $eq->pending_action,
                'updated_at' => optional($eq->updated_at)->toIso8601String(),
            ])
            ->all();
    }

    public function approveJobRequest(JobRequest $jobRequest, int $adminId, ?string $notes): void
    {
        $jobRequest->update([
            'admin_approval' => 'Approved',
            'admin_approval_notes' => $notes,
            'admin_reviewed_at' => now(),
            'admin_reviewed_by' => $adminId,
        ]);

        // Update linked inventory equipment status based on repair outcome
        if ($jobRequest->equipment_id && $jobRequest->repair_outcome) {
            $newStatus = match ($jobRequest->repair_outcome) {
                'Repaired' => 'Functional',
                'Unserviceable' => 'Unserviceable',
                default => null,
            };

            if ($newStatus) {
                Equipment::where('id', $jobRequest->equipment_id)->update(['status' => $newStatus]);
            }
        }
    }

    public function rejectJobRequest(JobRequest $jobRequest, int $adminId, ?string $notes): void
    {
        $jobRequest->update([
            'status' => 'Accepted', // Send back to tech for revision
            'admin_approval' => 'Rejected',
            'admin_approval_notes' => $notes,
            'admin_reviewed_at' => now(),
            'admin_reviewed_by' => $adminId,
            'bio_service_docs_id' => null, // Clear old service doc so tech can re-fill
            'repair_outcome' => null,
        ]);
    }

    public function approveEquipment(Equipment $equipment, int $adminId, ?string $notes): void
    {
        $newStatus = match ($equipment->pending_action) {
            'Restore' => 'Functional',
            'Condemn' => 'Condemned',
            default => $equipment->status,
        };

        $equipment->update([
            'status' => $newStatus,
            'admin_approval' => 'Approved',
            'admin_approval_notes' => $notes,
            'admin_reviewed_at' => now(),
            'admin_reviewed_by' => $adminId,
            'pending_action' => null,
        ]);
    }

    public function rejectEquipment(Equipment $equipment, int $adminId, ?string $notes): void
    {
        $equipment->update([
            'admin_approval' => 'Rejected',
            'admin_approval_notes' => $notes,
            'admin_reviewed_at' => now(),
            'admin_reviewed_by' => $adminId,
            'pending_action' => null,
        ]);
    }
}
