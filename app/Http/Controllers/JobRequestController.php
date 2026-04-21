<?php

namespace App\Http\Controllers;

use App\Models\JobRequest;
use App\Services\JobRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class JobRequestController extends Controller
{
    public function __construct(private JobRequestService $jobRequestService) {}

    public function index(Request $request): Response
    {
        $this->ensureCanManageRequests($request);

        $user = $request->user();
        $accountType = $user->account_type;
        $technicianUserId = $accountType === 'Biomed_Technician' ? $user->id : null;

        return Inertia::render('JobRequests', [
            'jobRequests' => $this->jobRequestService->listAll($technicianUserId),
            'assignableUsers' => in_array($accountType, ['Admin', 'Moderator'])
                ? $this->jobRequestService->getAssignableUsers($accountType)
                : [],
        ]);
    }

    public function accept(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $user = $request->user();
        $this->jobRequestService->accept($jobRequest, $user->id);

        // Auto-assign to the technician who accepted
        if ($user->account_type === 'Biomed_Technician') {
            $this->jobRequestService->assignUser($jobRequest, $user->id);
        }

        return redirect()->back();
    }

    public function assign(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $accountType = $request->user()?->account_type;
        $allowedTypes = $accountType === 'Admin'
            ? ['Biomed_Technician', 'Moderator']
            : ['Biomed_Technician'];

        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $assignee = \App\Models\User::findOrFail($validated['assigned_to']);
        abort_unless(in_array($assignee->account_type, $allowedTypes, true), 403, 'You cannot assign this user.');

        $this->jobRequestService->assignUser($jobRequest, $validated['assigned_to']);

        return redirect()->back();
    }

    public function setRepairCategory(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $validated = $request->validate([
            'repair_category' => ['required', 'in:Minor,Major'],
        ]);

        $this->jobRequestService->setRepairCategory($jobRequest, $validated['repair_category']);

        return redirect()->back();
    }

    public function complete(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $validated = $request->validate([
            'receive_by' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:255',
            'date_receive' => 'nullable|date',
            'date_performed' => 'required|date',
            'estimated_no_days' => 'required|string|max:50',
            'technician_date_received' => 'nullable|date',
            'date_started' => 'nullable|date',
            'date_finished' => 'required|date',
            'date_returned' => 'required|date',
            'receive_by_end_user' => 'nullable|string|max:255',
            'remarks' => 'required|string',
            'repair_category' => 'nullable|in:Minor,Major',
            'repair_outcome' => 'required|in:Repaired,Unserviceable',
        ]);

        $this->jobRequestService->complete($jobRequest, $validated);

        return redirect()->back();
    }

    private function ensureCanManageRequests(Request $request): void
    {
        abort_unless(in_array($request->user()?->account_type, ['Biomed_Technician', 'Admin', 'Moderator'], true), 403);
    }

    public function export(Request $request, JobRequest $jobRequest)
    {
        $user = $request->user();
        $allowed = ['Biomed_Technician', 'Admin', 'Moderator', 'End_User'];
        abort_unless(in_array($user?->account_type, $allowed, true), 403);

        $jobRequest->load(['acceptedBy:id,name', 'assignedTo:id,name', 'linkedEquipment', 'biomedicalServiceDoc']);

        $data = [
            'job' => $jobRequest,
            'generatedAt' => now(),
        ];

        $filename = sprintf('job-request-%s.pdf', $jobRequest->control_no ?? $jobRequest->id);

        return Pdf::loadView('exports.job-request', $data)
            ->setPaper('legal', 'portrait')
            ->download($filename);
    }
}