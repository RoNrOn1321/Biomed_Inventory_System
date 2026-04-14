<?php

namespace App\Http\Controllers;

use App\Models\JobRequest;
use App\Services\JobRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobRequestController extends Controller
{
    public function __construct(private JobRequestService $jobRequestService) {}

    public function index(Request $request): Response
    {
        $this->ensureCanManageRequests($request);

        $user = $request->user();
        $accountType = $user->account_type;
        $filterByUser = $accountType === 'Biomed_Technician' ? $user->id : null;

        return Inertia::render('JobRequests', [
            'jobRequests' => $this->jobRequestService->listAll($filterByUser),
            'assignableUsers' => in_array($accountType, ['Admin', 'Moderator'])
                ? $this->jobRequestService->getAssignableUsers($accountType)
                : [],
        ]);
    }

    public function accept(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $this->jobRequestService->accept($jobRequest, $request->user()->id);

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
            'date_performed' => 'nullable|date',
            'estimated_no_days' => 'nullable|integer',
            'technician_date_received' => 'nullable|date',
            'date_started' => 'nullable|date',
            'date_finished' => 'nullable|date',
            'date_returned' => 'nullable|date',
            'receive_by_end_user' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
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
}